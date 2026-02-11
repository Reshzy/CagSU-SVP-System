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
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->string('tin')->nullable()->after('supplier_id');
            $table->string('supplier_name_override')->nullable()->after('tin');
            $table->string('funds_cluster')->nullable()->after('supplier_name_override');
            $table->decimal('funds_available', 15, 2)->nullable()->after('funds_cluster');
            $table->string('ors_burs_no')->nullable()->after('funds_available');
            $table->date('ors_burs_date')->nullable()->after('ors_burs_no');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_orders', function (Blueprint $table) {
            $table->dropColumn([
                'tin',
                'supplier_name_override',
                'funds_cluster',
                'funds_available',
                'ors_burs_no',
                'ors_burs_date',
            ]);
        });
    }
};
