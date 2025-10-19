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
        Schema::table('purchase_requests', function (Blueprint $table) {
            // Make these fields nullable since Budget Office will fill them during earmarking
            $table->date('date_needed')->nullable()->change();
            $table->enum('procurement_type', [
                'supplies_materials',
                'equipment',
                'infrastructure',
                'services',
                'consulting_services'
            ])->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_requests', function (Blueprint $table) {
            $table->date('date_needed')->nullable(false)->change();
            $table->enum('procurement_type', [
                'supplies_materials',
                'equipment',
                'infrastructure',
                'services',
                'consulting_services'
            ])->nullable(false)->change();
        });
    }
};
