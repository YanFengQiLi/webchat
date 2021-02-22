<?php

use Illuminate\Http\Request;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

/**
 *  laravel 自带的 API 令牌认证路由
 *
 *  laravel 自带的权限中间件,具体参考:
 *      1.中间件传参     https://learnku.com/docs/laravel/6.x/middleware/5136#middleware-parameters
 *      2.保护路由      https://learnku.com/docs/laravel/6.x/authentication/5151#protecting-routes
 *
 *  auth 是 laravel 自带的权限中间件, 而 auth:api 则是 内置的可以自动验证输入请求中的 API 令牌的认证守卫
 *  这个 api 正是我们在 config/auth.php 里 guard 数组里, 配置的 api 守卫
 *
 *
 *  使用方式, 在请求的 Authorization 头中,  以 Bearer 令牌的方式提供他们的 API
 *  即 'Bearer ' . $token, 这个 token 就是登录获取的 api_token
 *
 *  postman 测试:
 *      选择 Authorization -> Bearer token , 复制通过登录接口返回的 api_token
 */
Route::middleware('auth:api')->get('/user', function (Request $request) {
    return $request->user();
});

//  注册
Route::post('/register', 'AuthController@register');
//  登录
Route::post('/login', 'AuthController@login');

