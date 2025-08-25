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
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('supplier_code')->unique(); // Auto-generated supplier code
            $table->string('business_name'); // Official business name
            $table->string('trade_name')->nullable(); // Trade/brand name if different
            $table->enum('business_type', ['sole_proprietorship', 'partnership', 'corporation', 'cooperative']);
            
            // Contact Information
            $table->string('contact_person');
            $table->string('position')->nullable();
            $table->string('email')->unique();
            $table->string('phone');
            $table->string('mobile')->nullable();
            $table->string('fax')->nullable();
            
            // Address Information
            $table->text('address');
            $table->string('city');
            $table->string('province');
            $table->string('postal_code', 10)->nullable();
            
            // Business Details
            $table->string('tin', 20)->nullable(); // Tax Identification Number
            $table->string('business_permit')->nullable();
            $table->date('permit_expiry')->nullable();
            $table->string('philgeps_registration')->nullable();
            
            // System Fields
            $table->enum('status', ['active', 'inactive', 'blacklisted', 'pending_verification'])->default('pending_verification');
            $table->text('specialization')->nullable(); // What they supply (office supplies, IT equipment, etc.)
            $table->decimal('performance_rating', 3, 2)->default(0.00); // 0.00 to 5.00
            $table->integer('total_contracts')->default(0);
            $table->decimal('total_contract_value', 15, 2)->default(0.00);
            
            // Authentication for supplier portal
            $table->string('password')->nullable(); // For supplier login
            $table->timestamp('email_verified_at')->nullable();
            $table->string('remember_token')->nullable();
            $table->timestamp('last_login_at')->nullable();
            
            $table->timestamps();
            
            // Indexes for performance
            $table->index(['status', 'business_name']);
            $table->index('email');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('suppliers');
    }
};
