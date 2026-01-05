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
        Schema::create('purchase_request_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_request_id')->constrained('purchase_requests')->onDelete('cascade');
            $table->foreignId('user_id')->nullable()->constrained('users')->onDelete('set null');

            // Action type
            $table->enum('action', [
                'created',
                'submitted',
                'status_changed',
                'returned',
                'rejected',
                'approved',
                'replacement_created',
                'notes_added',
                'assigned',
                'updated',
            ]);

            // Values for tracking changes
            $table->json('old_value')->nullable();
            $table->json('new_value')->nullable();

            // Human-readable description
            $table->text('description');

            // Metadata for auditing
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();

            $table->timestamp('created_at');

            // Indexes for performance
            $table->index(['purchase_request_id', 'created_at']);
            $table->index(['user_id', 'created_at']);
            $table->index('action');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('purchase_request_activities');
    }
};
