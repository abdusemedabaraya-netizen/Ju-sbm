<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Categories are managed by admins (see the categories table), so the
     * reports.category column can no longer be a fixed enum. It stores the
     * category slug as a plain string.
     */
    public function up(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->string('category', 50)->change();
        });
    }

    public function down(): void
    {
        Schema::table('reports', function (Blueprint $table) {
            $table->enum('category', [
                'bribery',
                'fraud',
                'abuse_of_power',
                'nepotism',
                'resource_misuse',
                'harassment',
                'other',
            ])->change();
        });
    }
};
