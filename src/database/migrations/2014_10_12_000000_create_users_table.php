<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use App\Models\User;

class CreateUsersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('email')->unique();
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');
            $table->rememberToken();
            $table->timestamps();
        });

        Schema::table('users', function (Blueprint $table) {
            // roleカラムが既に存在しないことを確認してから追加
            if (!Schema::hasColumn('users', 'role')) {
                $table->string('role')->default(User::ROLE_GENERAL)->after('password'); // passwordカラムの後にroleを追加し、デフォルトを'general'に設定
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('users');
        
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('role');
        });
    }
}
