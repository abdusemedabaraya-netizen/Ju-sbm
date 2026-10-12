<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\AuditLogger;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ProfilePhotoController extends Controller
{
    private const SIZE = 256; // final photo is 256 x 256

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

    public function store(Request $request)
    {
        $request->validate([
            'photo' => ['required', 'file', 'mimes:jpg,jpeg,png', 'max:2048'],
        ]);

        $user = $request->user();
        $path = 'avatars/' . $user->id . '-' . Str::random(24) . '.jpg';

        Storage::disk('local')->put($path, $this->process($request->file('photo')->getRealPath()));

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

    /**
     * Re-encodes the picture as a square JPEG. This removes EXIF data
     * (GPS position, camera, date) and any hidden content in the file.
     */
    private function process(string $file): string
    {
        if (! extension_loaded('gd')) {
            throw ValidationException::withMessages(['photo' => 'Image processing is unavailable (enable the PHP GD extension).']);
        }

        $info = @getimagesize($file);
        if (! $info || ($info[0] * $info[1]) > 25_000_000) {   // also blocks huge "image bombs"
            throw ValidationException::withMessages(['photo' => 'That image is not valid or is too large.']);
        }

        $src = $info[2] === IMAGETYPE_PNG ? @imagecreatefrompng($file) : @imagecreatefromjpeg($file);
        if (! $src) {
            throw ValidationException::withMessages(['photo' => 'That image could not be read.']);
        }

        // Keep the picture upright before the orientation tag is lost.
        if ($info[2] === IMAGETYPE_JPEG && function_exists('exif_read_data')) {
            $orientation = @exif_read_data($file)['Orientation'] ?? 1;
            $angle = [3 => 180, 6 => -90, 8 => 90][$orientation] ?? 0;
            if ($angle) {
                $src = imagerotate($src, $angle, 0) ?: $src;
            }
        }

        // Centre square crop, then resize.
        $w    = imagesx($src);
        $h    = imagesy($src);
        $side = min($w, $h);
        $x    = intdiv($w - $side, 2);
        $y    = intdiv($h - $side, 2);

        $dst = imagecreatetruecolor(self::SIZE, self::SIZE);
        imagefill($dst, 0, 0, imagecolorallocate($dst, 255, 255, 255)); // white behind transparent PNGs
        imagecopyresampled($dst, $src, 0, 0, $x, $y, self::SIZE, self::SIZE, $side, $side);

        ob_start();
        imagejpeg($dst, null, 85);
        $data = ob_get_clean();

        imagedestroy($src);
        imagedestroy($dst);

        return $data;
    }
}
