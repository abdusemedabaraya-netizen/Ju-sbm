<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Align evidences table with EvidenceStorage service implementation.
     * Renames legacy columns and adds missing security fields.
     */
    public function up(): void
    {
        Schema::table('evidences', function (Blueprint $table) {
            // Rename legacy columns if they exist
            if (Schema::hasColumn('evidences', 'file_name') && ! Schema::hasColumn('evidences', 'original_name')) {
                $table->renameColumn('file_name', 'original_name');
            }
            if (Schema::hasColumn('evidences', 'file_path') && ! Schema::hasColumn('evidences', 'stored_path')) {
                $table->renameColumn('file_path', 'stored_path');
            }
            if (Schema::hasColumn('evidences', 'file_type') && ! Schema::hasColumn('evidences', 'mime_type')) {
                $table->renameColumn('file_type', 'mime_type');
            }
            if (Schema::hasColumn('evidences', 'file_size') && ! Schema::hasColumn('evidences', 'size')) {
                $table->renameColumn('file_size', 'size');
            }

            // Add missing columns
            if (! Schema::hasColumn('evidences', 'uploaded_by')) {
                $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete()->after('report_id');
            }
            if (! Schema::hasColumn('evidences', 'sha256')) {
                $table->string('sha256', 64)->nullable()->after('size');
            }
            if (! Schema::hasColumn('evidences', 'metadata_stripped')) {
                $table->boolean('metadata_stripped')->default(false)->after('sha256');
            }
            if (! Schema::hasColumn('evidences', 'created_at')) {
                $table->timestamps();
            }
        });
    }

    public function down(): void
    {
        Schema::table('evidences', function (Blueprint $table) {
            if (Schema::hasColumn('evidences', 'original_name') && ! Schema::hasColumn('evidences', 'file_name')) {
                $table->renameColumn('original_name', 'file_name');
            }
            if (Schema::hasColumn('evidences', 'stored_path') && ! Schema::hasColumn('evidences', 'file_path')) {
                $table->renameColumn('stored_path', 'file_path');
            }
            if (Schema::hasColumn('evidences', 'mime_type') && ! Schema::hasColumn('evidences', 'file_type')) {
                $table->renameColumn('mime_type', 'file_type');
            }
            if (Schema::hasColumn('evidences', 'size') && ! Schema::hasColumn('evidences', 'file_size')) {
                $table->renameColumn('size', 'file_size');
            }

            if (Schema::hasColumn('evidences', 'uploaded_by')) {
                $table->dropForeign(['uploaded_by']);
                $table->dropColumn('uploaded_by');
            }
            if (Schema::hasColumn('evidences', 'sha256')) {
                $table->dropColumn('sha256');
            }
            if (Schema::hasColumn('evidences', 'metadata_stripped')) {
                $table->dropColumn('metadata_stripped');
            }
            if (Schema::hasColumn('evidences', 'created_at')) {
                $table->dropTimestamps();
            }
        });
    }
};
