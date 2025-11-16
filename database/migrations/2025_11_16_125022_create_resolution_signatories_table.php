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
        Schema::create('resolution_signatories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_request_id')->constrained()->onDelete('cascade');
            $table->enum('position', [
                'bac_chairman',
                'bac_vice_chairman',
                'bac_member_1',
                'bac_member_2',
                'bac_member_3',
                'head_bac_secretariat',
                'ceo'
            ]);
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('set null'); // If selected from list
            $table->string('name')->nullable(); // If manually entered
            $table->string('prefix')->nullable(); // Dr., Atty., Engr., Prof., etc.
            $table->string('suffix')->nullable(); // Ph.D., M.A., CPA, etc.
            $table->timestamps();
            
            // Ensure each position is unique per purchase request
            $table->unique(['purchase_request_id', 'position']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('resolution_signatories');
    }
};
