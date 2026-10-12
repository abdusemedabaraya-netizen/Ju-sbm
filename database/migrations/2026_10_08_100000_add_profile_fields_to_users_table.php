<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (! Schema::hasColumn('users', 'phone')) {
                $table->string('phone', 30)->nullable();
            }
            if (! Schema::hasColumn('users', 'photo_path')) {
                $table->string('photo_path')->nullable();      // private disk, never public
            }
            if (! Schema::hasColumn('users', 'job_title')) {
                $table->string('job_title', 100)->nullable();
            }
            if (! Schema::hasColumn('users', 'bio')) {
                $table->string('bio', 500)->nullable();
            }
            if (! Schema::hasColumn('users', 'department_id')) {
                $table->foreignId('department_id')->nullable()->constrained()->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            if (Schema::hasColumn('users', 'department_id')) {
                $table->dropConstrainedForeignId('department_id');
            }
            $table->dropColumn(array_values(array_filter(
                ['photo_path', 'job_title', 'bio'],
                fn ($c) => Schema::hasColumn('users', $c)
            )));
        });
    }
};
