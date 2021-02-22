<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Class AlterUsersAddApiToken
 * 为 users 表中新增令牌字段, 这里我们选用 laravel 自带的 token 驱动来实现 API 认证
 * config/auth.php 中可以看到 guards 数组中的 api 数组, 这就是 laravel 默认的 token 驱动
 */
class AlterUsersAddApiToken extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('api_token')
                ->after('remember_token')
                ->unique()
                ->nullable()
                ->default(null);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('api_token');
        });
    }
}
