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
        Schema::create('stamp_correction_requests', function (Blueprint $table) {
            $table->id(); // unsigned bigint, PRIMARY KEY, NOT NULL
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // unsigned bigint, NOT NULL, FOREIGN KEY to users table
            $table->foreignId('attendance_id')->unique()->constrained()->onDelete('cascade'); // unsigned bigint, UNIQUE KEY, NOT NULL, FOREIGN KEY to attendances table (1つの勤怠記録に対し1つの申請)
            $table->timestamp('requested_clock_in'); // timestamp, NOT NULL
            $table->timestamp('requested_clock_out')->nullable(); // timestamp, NULLABLE
            $table->json('requested_breaks_data')->nullable(); // json, NULLABLE (休憩時間の修正データをJSONで保存)
            $table->text('remarks')->nullable(); // text, NULLABLE
            $table->string('status')->default('pending'); // string, NOT NULL (pending, approved, rejected)

            $table->timestamps(); // created_at, updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stamp_correction_requests');
    }
};
