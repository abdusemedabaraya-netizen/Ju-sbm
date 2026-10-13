<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            // Secret access code shown to the reporter once; only the hash is kept.
            if (! Schema::hasColumn('reports', 'access_code_hash')) {
                $table->string('access_code_hash')->nullable()->after('tracking_number');
            }
        });
    }

    public function down(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            if (Schema::hasColumn('reports', 'access_code_hash')) {
                $table->dropColumn('access_code_hash');
            }
        });
    }
};
