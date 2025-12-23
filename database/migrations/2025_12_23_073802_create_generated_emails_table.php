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
        Schema::create('generated_emails', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_result_id')->constrained()->onDelete('cascade');
            $table->foreignId('person_id')->constrained()->onDelete('cascade');
            $table->string('subject');
            $table->text('body');
            $table->text('custom_context')->nullable(); // Store the context used for generation
            $table->timestamps();
            
            $table->index('lead_result_id');
            $table->index('person_id');
            $table->unique('lead_result_id'); // One generated email per lead result
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('generated_emails');
    }
};
