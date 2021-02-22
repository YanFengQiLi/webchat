<?php

namespace App\Listeners;

use Hhxsv5\LaravelS\Swoole\Task\Listener;
use Illuminate\Support\Facades\Log;

/**
 * 创建消息监听器, 对 App\Event\WsMessageReceived 事件进行处理
 * Class WsMessageReceivedListener
 * @package App\Listeners
 *
 * 文档见:
 * https://gitee.com/hhxsv5/laravel-s?_from=gitee_search#%E8%87%AA%E5%AE%9A%E4%B9%89%E7%9A%84%E5%BC%82%E6%AD%A5%E4%BA%8B%E4%BB%B6
 */
class WsMessageReceivedListener extends Listener
{
    //  处理被监听的消息事件
    public function handle()
    {
        $message = $this->event->getData();

        Log::info(__CLASS__ . ': 开始处理', $message->toArray());

        if ($message && $message->user_id && $message->room_id && ($message->msg || $message->img)) {
            $message->save();
            Log::info(__CLASS__ . ': 处理完毕');
        } else {
            Log::error(__CLASS__ . ': 消息字段缺失，无法保存');
        }
    }
}
