<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'photo_path') && ! Schema::hasColumn('users', 'avatar')) {
                // Single source of truth for the profile image: the storage path.
                $table->renameColumn('photo_path', 'avatar');
            } elseif (! Schema::hasColumn('users', 'avatar')) {
                $table->string('avatar')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'avatar') && ! Schema::hasColumn('users', 'photo_path')) {
                $table->renameColumn('avatar', 'photo_path');
            }
        });
    }
};
