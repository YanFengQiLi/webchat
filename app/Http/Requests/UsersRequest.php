<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UsersRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        return [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|between:6,12'
        ];
    }

    public function messages()
    {
        return [
            'name.required' => '请填写用户名',
            'name.string' => '用户名格式错误',
            'name.max' => '请填写用户名长度为 1 ~ 255个字符',
            'email.required' => '请填写邮箱',
            'email.string' => '邮箱格式错误',
            'email.email' => '邮箱格式错误',
            'email.max' => '邮箱格式错误',
            'email.unique' => '邮箱已被注册',
            'password.required' => '请填写密码',
            'password.string' => '密码格式错误',
            'password.between' => '密码长度为 6 ~ 12 个字符',
        ];
    }
}
