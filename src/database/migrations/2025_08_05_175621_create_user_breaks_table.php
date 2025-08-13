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
        Schema::create('user_breaks', function (Blueprint $table) { // breaks から user_breaks に変更
            $table->id(); // unsigned bigint, PRIMARY KEY, NOT NULL
            $table->foreignId('attendance_id')->constrained('attendances')->onDelete('cascade'); // unsigned bigint, NOT NULL, FOREIGN KEY to attendances table
            $table->timestamp('break_start'); // timestamp, NOT NULL
            $table->timestamp('break_end')->nullable(); // timestamp, NULLABLE (休憩がまだ終わっていない場合があるため)

            $table->timestamps(); // created_at, updated_at
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_breaks'); // breaks から user_breaks に変更
    }
};
