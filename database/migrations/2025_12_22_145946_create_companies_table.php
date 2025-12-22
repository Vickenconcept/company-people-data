<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('domain')->nullable()->unique();
            $table->string('website')->nullable();
            $table->string('industry')->nullable();
            $table->string('country')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->text('description')->nullable();
            $table->integer('employee_count')->nullable();
            $table->decimal('revenue', 15, 2)->nullable();
            $table->string('founded_year')->nullable();
            $table->json('technologies')->nullable();
            $table->json('keywords')->nullable();
            $table->text('icp_profile')->nullable(); // Ideal Customer Profile JSON
            $table->json('metadata')->nullable(); // Additional data from APIs
            $table->string('data_source')->nullable(); // Which API provided this (clearbit, apollo, etc.)
            $table->string('external_id')->nullable(); // ID from external API
            $table->timestamps();
            
            $table->index('industry');
            $table->index('country');
            $table->index('employee_count');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('companies');
    }
};
