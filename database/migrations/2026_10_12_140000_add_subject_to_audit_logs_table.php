<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * AuditLogger::record() has always written subject_type / subject_id,
     * but the columns never existed, so Eloquent silently discarded them.
     * This links each audit entry to the model it concerns.
     */
    public function up(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            if (! Schema::hasColumn('audit_logs', 'subject_type')) {
                $table->string('subject_type')->nullable()->after('action');
            }
            if (! Schema::hasColumn('audit_logs', 'subject_id')) {
                $table->unsignedBigInteger('subject_id')->nullable()->after('subject_type');
                $table->index(['subject_type', 'subject_id']);
            }
        });
    }

    public function down(): void
    {
        Schema::table('audit_logs', function (Blueprint $table) {
            if (Schema::hasColumn('audit_logs', 'subject_id')) {
                $table->dropIndex(['subject_type', 'subject_id']);
                $table->dropColumn('subject_id');
            }
            if (Schema::hasColumn('audit_logs', 'subject_type')) {
                $table->dropColumn('subject_type');
            }
        });
    }
};
