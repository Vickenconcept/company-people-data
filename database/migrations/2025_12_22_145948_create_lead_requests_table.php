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
        Schema::create('lead_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('reference_company_name');
            $table->string('reference_company_url')->nullable();
            $table->text('reference_company_content')->nullable(); // Scraped content
            $table->json('icp_profile')->nullable(); // Generated ICP from AI
            $table->json('search_criteria')->nullable(); // Industry, country, size, etc.
            $table->integer('target_count')->default(10);
            $table->string('country', 2)->nullable();
            $table->json('target_job_titles')->nullable(); // ['CEO', 'CFO', etc.]
            $table->enum('status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
            $table->text('error_message')->nullable();
            $table->integer('companies_found')->default(0); // Track how many companies found
            $table->integer('contacts_found')->default(0); // Track how many contacts found
            $table->timestamps();
            
            $table->index('user_id');
            $table->index('status');
            $table->index('country');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lead_requests');
    }
};
