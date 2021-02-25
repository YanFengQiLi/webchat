<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Class MessageResource
 * @package App\Http\Resources
 * 定义 message Api 资源
 * API 资源,文档见: https://learnku.com/docs/laravel/6.x/eloquent-resources/5180
 */
class MessageResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     *
     * 注意 : 我们要返回的目标结构必须与前端消息对象属性字段名保持一致，这样才可以在前端正常渲染后端返回的消息数据
     */
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'userid' => $this->user->email,
            'username' => $this->user->name,
            'src' => $this->user->avatar,
            'msg' => $this->msg,
            'img' => $this->img,
            'roomid' => $this->room_id,
            'time' => $this->created_at
        ];
    }
}
