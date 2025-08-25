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
		Schema::create('bac_meetings', function (Blueprint $table) {
			$table->id();
			$table->foreignId('purchase_request_id')->nullable()->constrained('purchase_requests')->nullOnDelete();
			$table->dateTime('meeting_datetime');
			$table->string('location')->nullable();
			$table->string('status')->default('scheduled'); // scheduled, completed, cancelled
			$table->string('title')->nullable();
			$table->text('agenda')->nullable();
			$table->text('minutes')->nullable();
			$table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
			$table->timestamps();
		});

		Schema::create('bac_meeting_attendees', function (Blueprint $table) {
			$table->id();
			$table->foreignId('bac_meeting_id')->constrained('bac_meetings')->cascadeOnDelete();
			$table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
			$table->string('role_at_meeting')->nullable(); // Chair, Member, Secretariat, Guest
			$table->boolean('attended')->default(false);
			$table->text('remarks')->nullable();
			$table->timestamps();
			$table->unique(['bac_meeting_id', 'user_id']);
		});
	}

	/**
	 * Reverse the migrations.
	 */
	public function down(): void
	{
		Schema::dropIfExists('bac_meeting_attendees');
		Schema::dropIfExists('bac_meetings');
	}
};


