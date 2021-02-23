<?php

use Swoole\Http\Request;
use App\Services\WebSocket\WebSocket;
use App\Services\WebSocket\Facade\Websocket as WebsocketProxy;

/**
 *   Websocket Routes 事件路由
 *
 *  之所以叫事件路由，是因为这些路由都是根据客户端传递的事件名称来匹配调用的，这里我们初始化了 connect 和 login 这两个事件路由的闭包实现，
 *  这里的 WebsocketProxy 对应的是 Websocket 门面类，所以静态 on 方法调用最终还是落到 Websocket 的 on 方法去执行，
 *  即注册某个事件对应的业务逻辑，在闭包实现参数中，$websocket 对应的是 call 方法中传递过来的 $this 对象，
 *  $data 则是经过 Parser 实现类解析的消息数据。因此，在闭包函数中，我们可以调用 Websocket 类的任何方法，最后再通过 emit 方法将消息发送给客户端。
 */


WebsocketProxy::on('connect', function (WebSocket $websocket, Request $request) {
    // 发送欢迎信息
    $websocket->setSender($request->fd);
    $websocket->emit('connect', '欢迎访问聊天室');

});

WebsocketProxy::on('disconnect', function (WebSocket $websocket) {
    // called while socket on disconnect
});

WebsocketProxy::on('login', function (WebSocket $websocket, $data) {
    if (!empty($data['token']) && ($user = \App\User::where('api_token', $data['token'])->first())) {
        $websocket->loginUsing($user);
        // 获取未读消息
        $rooms = [];
        foreach (\App\Count::$ROOMLIST as $roomid) {
            // 循环所有房间
            $result = \App\Count::where('user_id', $user->id)->where('room_id', $roomid)->first();
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
