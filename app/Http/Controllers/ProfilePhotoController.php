<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProfilePhotoRequest;
use App\Models\User;
use App\Services\AuditLogger;
use App\Services\ImageProcessor;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProfilePhotoController extends Controller
{
    /** Photos live on the PRIVATE disk and are served only to the owner or an admin. */
    public function show(User $user)
    {
        abort_unless($user->avatar, 404);
        abort_unless(auth()->id() === $user->id || $this->isAdmin(), 403);

        $disk = Storage::disk('local');
        abort_unless($disk->exists($user->avatar), 404);

        return $disk->response($user->avatar, null, [
            'Cache-Control'          => 'private, max-age=86400',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function store(StoreProfilePhotoRequest $request, ImageProcessor $images)
    {
        $user = $request->user();
        $path = 'avatars/' . $user->id . '-' . Str::random(24) . '.jpg';

        Storage::disk('local')->put($path, $images->squareJpeg($request->file('photo')->getRealPath()));

        if ($user->avatar) {
            Storage::disk('local')->delete($user->avatar);
        }

        $user->forceFill(['avatar' => $path])->save();

        AuditLogger::log('profile.photo_updated', $user);

        // AJAX uploads update the shared state on the current page without a reload.
        if ($request->wantsJson()) {
            return response()->json([
                'avatar_url' => $user->avatar_url,
                'name'       => $user->name,
            ]);
        }

        return back()->with('success', 'Profile photo updated.');
    }

    public function destroy(Request $request)
    {
        $user = $request->user();

        if ($user->avatar) {
            Storage::disk('local')->delete($user->avatar);
            $user->forceFill(['avatar' => null])->save();
            AuditLogger::log('profile.photo_removed', $user);
        }

        if ($request->wantsJson()) {
            return response()->json([
                'avatar_url' => null,
                'name'       => $user->name,
            ]);
        }

        return back()->with('success', 'Profile photo removed.');
    }
}
