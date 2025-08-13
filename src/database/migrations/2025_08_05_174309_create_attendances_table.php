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
        Schema::create('attendances', function (Blueprint $table) {
            $table->id(); // unsigned bigint, PRIMARY KEY, NOT NULL
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // unsigned bigint, NOT NULL, FOREIGN KEY to users table
            $table->date('date'); // date, NOT NULL
            $table->timestamp('clock_in'); // timestamp, NOT NULL
            $table->timestamp('clock_out')->nullable(); // timestamp, NULLABLE

            $table->timestamps(); // created_at, updated_at

            // user_id と date の組み合わせで一意制約を設定 (1日1回出勤制限のため)
            $table->unique(['user_id', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
