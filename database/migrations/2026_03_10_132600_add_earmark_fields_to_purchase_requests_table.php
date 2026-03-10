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
            $table->string('legal_basis')->nullable()->after('earmark_id');
            $table->text('earmark_programs_activities')->nullable()->after('legal_basis');
            $table->string('earmark_responsibility_center')->nullable()->after('earmark_programs_activities');
            $table->date('earmark_date_to')->nullable()->after('earmark_responsibility_center');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_requests', function (Blueprint $table) {
            $table->dropColumn([
                'legal_basis',
                'earmark_programs_activities',
                'earmark_responsibility_center',
                'earmark_date_to',
            ]);
        });
    }
};
