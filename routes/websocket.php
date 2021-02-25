<?php

use Swoole\Http\Request;
use App\Services\WebSocket\WebSocket;
use App\Services\WebSocket\Facade\Websocket as WebsocketProxy;
use App\Count;
use App\User;
use App\Message;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

/**
 *   Websocket Routes 事件路由
 *
 *  之所以叫事件路由，是因为这些路由都是根据客户端传递的事件名称来匹配调用的，这里我们初始化了 connect 和 login 这两个事件路由的闭包实现，
 *  这里的 WebsocketProxy 对应的是 Websocket 门面类，所以静态 on 方法调用最终还是落到 Websocket 的 on 方法去执行，
 *  即注册某个事件对应的业务逻辑，在闭包实现参数中，$websocket 对应的是 call 方法中传递过来的 $this 对象，
 *  $data 则是经过 Parser 实现类解析的消息数据。因此，在闭包函数中，我们可以调用 Websocket 类的任何方法，最后再通过 emit 方法将消息发送给客户端。
 *
 *  注意:
 *      1. socket.io 允许你发送和接收自定义事件，除了connect、message 和 disconnect，你都可以发送自定义事件
 */

//  监听 websocket 链接事件
WebsocketProxy::on('connect', function (WebSocket $websocket, Request $request) {
    $websocket->setSender($request->fd);
    $websocket->emit('connect', '欢迎访问聊天室');
});

//  监听 websocket 断开链接事件 实际与退出房间事件是一致的
WebsocketProxy::on('disconnect', function (WebSocket $websocket, $data) {
    roomout($websocket, $data);
});

//  监听 websocket 登录 对应客户端 socket.on('login')
WebsocketProxy::on('login', function (WebSocket $websocket, $data) {
    if (isset($data['api_token']) && ($user = User::where('api_token', $data['api_token'])->first())) {
        $websocket->loginUsing($user);
        // 获取未读消息
        $rooms = [];
        foreach (Count::$ROOMLIST as $roomid) {
            // 循环所有房间
            $result = Count::where('user_id', $user->id)->where('room_id', $roomid)->first();
            $roomid = 'room' . $roomid;
            if ($result) {
                $rooms[$roomid] = $result->count;
            } else {
                $rooms[$roomid] = 0;
            }
        }
        $websocket->toUser($user)->emit('count', $rooms);
    } else {
        $websocket->emit('login', '登录后才能进入聊天室');
    }
});

//  监听 websocket 进入房间事件, 对应客户端 socket.on('room')
WebsocketProxy::on('room', function (WebSocket $websocket, $data) {
    if (isset($data['api_token']) && ($user = User::where('api_token', $data['api_token'])->first())) {
        // 从请求数据中获取房间ID
        if (empty($data['roomid'])) {
            return;
        }
        $roomId = $data['roomid'];
        // 重置用户与fd关联  redis hash的 hset 指令:  hset KEY FIELD VALUE
        Redis::command('hset', ['socket_id', $user->id, $websocket->getSender()]);
        // 将该房间下用户未读消息清零
        $count = Count::where('user_id', $user->id)->where('room_id', $roomId)->first();
        $count->count = 0;
        $count->save();
        // 将用户加入指定房间
        $room = Count::$ROOMLIST[$roomId];
        $websocket->join($room);
        // 打印日志
        Log::info($user->name . '进入房间：' . $room);
        // 更新在线用户信息
        $roomUsersKey = 'online_users_' . $room;
        $onelineUsers = Cache::get($roomUsersKey);
        $user->src = $user->avatar;
        if ($onelineUsers) {
            $onelineUsers[$user->id] = $user;
            Cache::forever($roomUsersKey, $onelineUsers);
        } else {
            $onelineUsers = [
                $user->id => $user
            ];
            Cache::forever($roomUsersKey, $onelineUsers);
        }
        // 广播消息给房间内所有用户
        $websocket->to($room)->emit('room', $onelineUsers);
    } else {
        $websocket->emit('login', '登录后才能进入聊天室');
    }
});

//  监听退出房间事件    对应客户端 socket.on('roomout')
WebsocketProxy::on('roomout', function (WebSocket $websocket, $data) {
    roomout($websocket, $data);
});


//  监听接受消息事件 对象客户端 socket.on('message')
WebsocketProxy::on('message', function (WebSocket $websocket, $data) {
    if (isset($data['api_token']) && ($user = User::where('api_token', $data['api_token'])->first())) {
        // 获取消息内容
        $msg = $data['msg'];
        $img = $data['img'];
        $roomId = intval($data['roomid']);
        $time = $data['time'];
        // 消息内容（含图片）或房间号不能为空
        if((empty($msg)  && empty($img))|| empty($roomId)) {
            return;
        }
        // 记录日志
        Log::info($user->name . '在房间' . $roomId . '中发布消息: ' . $msg);
        // 将消息保存到数据库
        if (empty($img)) {
            $message = new Message();
            $message->user_id = $user->id;
            $message->room_id = $roomId;
            $message->msg = $msg;  // 文本消息
            $message->img = '';  // 图片消息留空
            $message->created_at = Carbon::now();
            $message->save();
        }
        // 将消息广播给房间内所有用户
        // 将消息广播给房间内所有用户
        $room = Count::$ROOMLIST[$roomId];
        $messageData = [
            'userid' => $user->email,
            'username' => $user->name,
            'src' => $user->avatar,
            'msg' => $msg,
            'img' => $img,
            'roomid' => $roomId,
            'time' => $time
        ];
        $websocket->to($room)->emit('message', $messageData);
        // 更新所有用户本房间未读消息数
        $userIds = Redis::hgetall('socket_id');
        foreach ($userIds as $userId => $socketId) {
            // 更新每个用户未读消息数并将其发送给对应在线用户
            $result = Count::where('user_id', $userId)->where('room_id', $roomId)->first();
            if ($result) {
                $result->count += 1;
                $result->save();
                $rooms[$room] = $result->count;
            } else {
                // 如果某个用户未读消息数记录不存在，则初始化它
                $count = new Count();
                $count->user_id = $user->id;
                $count->room_id = $roomId;
                $count->count = 1;
                $count->save();
                $rooms[$room] = 1;
            }
            $websocket->to($socketId)->emit('count', $rooms);
        }
    } else {
        $websocket->emit('login', '登录后才能进入聊天室');
    }

});


/**
 * @param WebSocket $websocket
 * @param $data
 * 退出房间
 */
function roomout(WebSocket $websocket, $data) {
    if (isset($data['api_token']) && ($user = User::where('api_token', $data['api_token'])->first())) {
        if (empty($data['roomid'])) {
            return;
        }
        $roomId = $data['roomid'];
        $room = Count::$ROOMLIST[$roomId];
        // 更新在线用户信息
        $roomUsersKey = 'online_users_' . $room;
        $onelineUsers = Cache::get($roomUsersKey);
        if (!empty($onelineUsers[$user->id])) {
            unset($onelineUsers[$user->id]);
            Cache::forever($roomUsersKey, $onelineUsers);
        }
        //  更新在线用户信息（将当前用户剔除），最后通过如下代码离开房间并将更新后的用户信息广播给客户端所有在线用户（包括自己）
        $websocket->to($room)->emit('roomout', $onelineUsers);
        Log::info($user->name . '退出房间: ' . $room);
        $websocket->leave([$room]);
    } else {
        $websocket->emit('login', '登录后才能进入聊天室');
    }
}
