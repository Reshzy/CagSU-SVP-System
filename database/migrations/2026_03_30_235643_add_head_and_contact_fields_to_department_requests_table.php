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
        Schema::table('department_requests', function (Blueprint $table) {
            $table->string('head_name')->nullable()->after('description');
            $table->string('contact_email')->nullable()->after('head_name');
            $table->string('contact_phone')->nullable()->after('contact_email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('department_requests', function (Blueprint $table) {
            $table->dropColumn(['head_name', 'contact_email', 'contact_phone']);
        });
    }
};
