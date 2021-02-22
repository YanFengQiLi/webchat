<?php

namespace App\Events;

use App\Listeners\WsMessageReceivedListener;
use App\Message;
use Hhxsv5\LaravelS\Swoole\Task\Event;
use Illuminate\Support\Carbon;

/**
 * 创建消息接收事件
 * Class WsMessageReceived
 * @package App\Events
 * 由于操作数据库是一个涉及到网络 IO 的耗时操作，所以这里我们通过 Swoole 提供的异步事件监听机制将其转交给 Task Worker 去处理，从而提高 WebSocket 服务器的通信性能。
 *
 * 文档见:
 * https://gitee.com/hhxsv5/laravel-s?_from=gitee_search#%E8%87%AA%E5%AE%9A%E4%B9%89%E7%9A%84%E5%BC%82%E6%AD%A5%E4%BA%8B%E4%BB%B6
 */
class WsMessageReceived extends Event
{

    //  消息对象
    private $message;
    //  用户ID
    private $userId;
    //  定义该事件的监听类
    protected $listeners = [
        WsMessageReceivedListener::class
    ];

    /**
     * WsMessageReceived constructor.
     * @param $message
     * @param int $userId
     */
    public function __construct($message, $userId = 0)
    {
        $this->message = $message;

        $this->userId = $userId;
    }

    /**
     * 格式化数据, 返回 Message 实例
     *
     * @return Message
     */
    public function getData()
    {
        $model = new Message();

        $model->user_id = $this->userId;

        $model->room_id = $this->message->room_id;

        $model->msg = $this->message->type == 'text' ? $this->message->content : '';

        $model->img = $this->message->type == 'image' ? $this->message->image : '';

        $model->created_at = Carbon::now();

        return $model;
    }
}
