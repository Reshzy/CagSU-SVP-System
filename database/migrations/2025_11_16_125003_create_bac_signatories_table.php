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
        Schema::create('bac_signatories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->enum('position', [
                'bac_chairman',
                'bac_vice_chairman',
                'bac_member',
                'head_bac_secretariat',
                'ceo'
            ]);
            $table->string('prefix')->nullable(); // Dr., Atty., Engr., Prof., etc.
            $table->string('suffix')->nullable(); // Ph.D., M.A., CPA, etc.
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            // Ensure one user can have multiple positions if needed
            $table->unique(['user_id', 'position']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('bac_signatories');
    }
};
