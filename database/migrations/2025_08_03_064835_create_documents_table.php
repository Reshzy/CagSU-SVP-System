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
        Schema::create('documents', function (Blueprint $table) {
            $table->id();
            $table->string('document_number')->unique(); // DOC-2025-0001
            
            // Polymorphic relationship - can be attached to PR, PO, Quotations, etc.
            $table->morphs('documentable'); // documentable_type, documentable_id
            
            // Document details
            $table->enum('document_type', [
                'purchase_request',
                'ppmp',
                'earmark_document', 
                'bac_resolution',
                'abstract_of_quotation',
                'purchase_order',
                'quotation_file',
                'delivery_receipt',
                'inspection_report',
                'ris', // Requisition Issue Slip
                'ics', // Inventory Custodian Slip  
                'par', // Property Acknowledgment Receipt
                'other'
            ]);
            
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('file_name'); // Original filename
            $table->string('file_path'); // Storage path
            $table->string('file_extension', 10);
            $table->bigInteger('file_size'); // In bytes
            $table->string('mime_type');
            
            // Version control
            $table->integer('version')->default(1);
            $table->foreignId('previous_version_id')->nullable()->constrained('documents');
            $table->boolean('is_current_version')->default(true);
            
            // Access control
            $table->foreignId('uploaded_by')->constrained('users');
            $table->boolean('is_public')->default(false); // Can all stakeholders see this?
            $table->json('visible_to_roles')->nullable(); // Array of role names
            
            // Status
            $table->enum('status', [
                'draft',
                'pending_review',
                'approved',
                'rejected', 
                'archived'
            ])->default('draft');
            
            $table->timestamps();
            
            // Indexes (morphs already creates documentable index)
            $table->index(['document_type', 'status']);
            $table->index(['uploaded_by', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
