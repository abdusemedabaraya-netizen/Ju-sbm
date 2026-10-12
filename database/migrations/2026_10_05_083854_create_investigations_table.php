<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('investigations', function (Blueprint $table) {


            $table->id();

            $table->foreignId('report_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('investigator_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->longText('findings')->nullable();

            $table->longText('recommendation')->nullable();

            $table->date('investigation_date')->nullable();

            $table->enum('status',[
                'pending',
                'ongoing',
                'completed'
            ])->default('pending');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('investigations');
    }
};
