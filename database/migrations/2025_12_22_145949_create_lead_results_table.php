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
        Schema::create('lead_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_request_id')->constrained()->onDelete('cascade');
            $table->foreignId('company_id')->constrained()->onDelete('cascade');
            $table->foreignId('person_id')->nullable()->constrained()->onDelete('set null');
            $table->decimal('similarity_score', 5, 2)->nullable(); // AI similarity score
            $table->enum('status', ['pending', 'contacted', 'responded', 'converted', 'rejected'])->default('pending');
            $table->timestamps();
            
            $table->index('lead_request_id');
            $table->index('company_id');
            $table->index('person_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lead_results');
    }
};
