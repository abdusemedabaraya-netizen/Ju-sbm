<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Case notes (case_comments) are internal staff notes. The flag makes the
     * intent explicit and lets the reporter-facing code filter them out.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('case_comments', 'is_internal')) {
            Schema::table('case_comments', function (Blueprint $table) {
                $table->boolean('is_internal')->default(true)->after('comment');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('case_comments', 'is_internal')) {
            Schema::table('case_comments', function (Blueprint $table) {
                $table->dropColumn('is_internal');
            });
        }
    }
};
