<?php

namespace App\Http\Controllers;

use App\Http\Requests\UsersRequest;
use App\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Class AuthController
 * @package App\Http\Controllers
 * API 认证控制器
 */
class AuthController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:api')->only('logout');
    }

    /**
     * @param UsersRequest $usersRequest
     * @return mixed
     * 注册
     */
    public function register(UsersRequest $usersRequest)
    {
        $validated = $usersRequest->validated();

        $data = array_merge($validated, [
            'api_token' => Str::random(60),
            'avatar' => request('src')
        ]);

        $user = User::create($data);

        unset($user->password);

        return api_response(0, ['user' => $user], '注册成功');
    }


    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * 登录
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string'
        ], [
            'email.required' => '请输入邮箱',
            'email.string' => '邮箱格式错误',
            'email.email' => '邮箱格式错误',
            'password.required' => '请输入密码',
            'password.string' => '密码格式错误',
        ]);

        $email = $request->input('email');

        $password = $request->input('password');

        $user = User::where('email', $email)->first();

        if ($user && Hash::check($password, $user->password)) {
            $user->api_token = Str::random(60);

            $user->save();

            $data = $user->makeHidden(['password']);

            return api_response(0, ['user' => $data], '登录成功');
        }

        return api_response(1, [], '登录失败, 请检查你的邮箱 / 密码');
    }

    /**
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     * 退出
     */
    public function logout(Request $request)
    {
        $user = Auth::guard('auth:api')->user();

        $userModel = User::find($user->id);

        $userModel->api_token = null;

        $userModel->save();

        return api_response(0, [], '退出成功');
    }
}
