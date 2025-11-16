<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // For MySQL, we need to use raw SQL to modify the enum
        DB::statement("ALTER TABLE documents MODIFY COLUMN document_type ENUM(
            'purchase_request',
            'ppmp',
            'earmark_document',
            'bac_resolution',
            'bac_rfq',
            'abstract_of_quotation',
            'purchase_order',
            'quotation_file',
            'delivery_receipt',
            'inspection_report',
            'ris',
            'ics',
            'par',
            'other'
        )");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove bac_rfq from the enum
        DB::statement("ALTER TABLE documents MODIFY COLUMN document_type ENUM(
            'purchase_request',
            'ppmp',
            'earmark_document',
            'bac_resolution',
            'abstract_of_quotation',
            'purchase_order',
            'quotation_file',
            'delivery_receipt',
            'inspection_report',
            'ris',
            'ics',
            'par',
            'other'
        )");
    }
};

