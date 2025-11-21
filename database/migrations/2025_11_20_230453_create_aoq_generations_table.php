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
        Schema::create('aoq_generations', function (Blueprint $table) {
            $table->id();
            $table->string('aoq_reference_number')->unique(); // e.g., AOQ-2025-0001
            $table->foreignId('purchase_request_id')->constrained('purchase_requests');
            $table->foreignId('generated_by')->constrained('users'); // Who generated the AOQ
            $table->string('document_hash')->nullable(); // SHA256 hash of exported document for tamper detection
            $table->json('exported_data_snapshot')->nullable(); // JSON snapshot of data at time of generation
            $table->string('file_path')->nullable(); // Path to stored AOQ document
            $table->string('file_format')->default('docx'); // docx or pdf
            $table->integer('total_items')->default(0); // Number of items in the AOQ
            $table->integer('total_suppliers')->default(0); // Number of suppliers evaluated
            $table->text('generation_notes')->nullable(); // Any notes or comments
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('aoq_generations');
    }
};
