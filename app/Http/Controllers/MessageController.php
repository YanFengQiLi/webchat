<?php

namespace App\Http\Controllers;

use App\Http\Resources\MessageResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Message;

/**
 * Class MessageController
 * @package App\Http\Controllers
 * 消息控制器
 */
class MessageController extends Controller
{
    public function history(Request $request)
    {
        $roomId = intval($request->get('roomid'));
        $current = intval($request->get('current'));
        if ($roomId <= 0 || $current <= 0) {
            Log::error('history-error', '无效的房间和页面信息');

            return api_response(1, [], '房间信息错误');
        }
        // 获取消息总数
        $messageTotal = Message::where('room_id', $roomId)->count();
        $limit = 20;  // 每页显示20条消息
        $skip = ($current - 1) * 20;  // 从第多少条消息开始
        // 分页查询消息
        // 分页查询消息
        $messages = Message::where('room_id', $roomId)->skip($skip)->take($limit)->orderBy('created_at', 'asc')->get();
        $messagesData = [];
        if ($messages) {
            // 基于 API 资源类做 JSON 数据结构的自动转化
            $messagesData = MessageResource::collection($messages);
        }
        // 返回响应信息
        return api_response(0, [
            'data' => $messagesData,
            'total' => $messageTotal,
            'current' => $current
        ], '获取成功');
    }
}
