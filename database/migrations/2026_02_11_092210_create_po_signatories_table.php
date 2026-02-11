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
        Schema::create('po_signatories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->nullable()->constrained()->onDelete('cascade');
            $table->string('manual_name')->nullable();
            $table->enum('position', [
                'ceo',
                'chief_accountant',
            ]);
            $table->string('prefix')->nullable();
            $table->string('suffix')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['position', 'is_active'], 'po_signatories_position_active_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('po_signatories');
    }
};
