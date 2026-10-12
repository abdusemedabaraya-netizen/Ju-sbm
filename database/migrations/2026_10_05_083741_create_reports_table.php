<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reports', function (Blueprint $table) {

            $table->id();

            $table->string('tracking_number')->unique();
            $table->string('access_code_hash')->nullable();

            $table->foreignId('user_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->foreignId('department_id')->constrained()->restrictOnDelete();

            $table->string('subject');
            $table->longText('description');
            $table->date('incident_date')->nullable();
            $table->string('location')->nullable();

            $table->enum('category', [
                'bribery', 'fraud', 'abuse_of_power', 'nepotism',
                'resource_misuse', 'harassment', 'other',
            ]);

            $table->enum('priority', [
                'low', 'medium', 'high', 'critical',
            ])->default('medium');

            $table->enum('status', [
                'submitted', 'under_review', 'assigned', 'investigating',
                'resolved', 'closed', 'rejected',
            ])->default('submitted');

            $table->boolean('anonymous')->default(false);

            $table->timestamps();
            $table->softDeletes();   // BR-10
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reports');
    }
};