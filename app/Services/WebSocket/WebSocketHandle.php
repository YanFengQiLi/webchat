<?php


namespace App\Services\WebSocket;


use App\Events\WsMessageReceived;
use App\Services\WebSocket\SocketIO\SocketIOParser;
use App\User;
use Hhxsv5\LaravelS\Swoole\Task\Event;
use Hhxsv5\LaravelS\Swoole\WebSocketHandlerInterface;
use Illuminate\Support\Facades\Log;
use Swoole\Http\Request;
use Swoole\WebSocket\Frame;
use Swoole\WebSocket\Server;
use App\Services\WebSocket\SocketIO\Packet;

class WebSocketHandle implements WebSocketHandlerInterface
{
    protected $websocket;

    protected $parser;

    public function __construct()
    {
        $this->websocket = app(WebSocket::class);
        $this->parser = app(SocketIOParser::class);
    }

    //  监听 websocket 连接事件
    public function onOpen(Server $server, Request $request)
    {
        //  判断请求数据中是否包含 sid 字段，没有包含需要将连接初始化信息发送给客户端, 以便成功建立 WebSocket 连接
        if (!request()->input('sid')) {
            // 初始化连接信息适配 socket.io-client，这段代码不能省略，否则无法建立连接
            $payload = json_encode([
                'sid' => base64_encode(uniqid()),
                'upgrades' => [],
                'pingInterval' => config('laravels.swoole.heartbeat_idle_time') * 1000,
                'pingTimeout' => config('laravels.swoole.heartbeat_check_interval') * 1000,
            ]);
            $initPayload = Packet::OPEN . $payload;
            $connectPayload = Packet::MESSAGE . Packet::CONNECT;
            $server->push($request->fd, $initPayload);
            $server->push($request->fd, $connectPayload);
        }

        Log::info('WebSocket 连接建立:' . $request->fd);

        $payload = [
            'sender'    => $request->fd,
            'fds'       => [$request->fd],
            'broadcast' => false,
            'assigned'  => false,
            'event'     => 'message',
            'message'   => '欢迎访问聊天室',
        ];
        $pusher = Pusher::make($payload, $server);
        $pusher->push($this->parser->encode($pusher->getEvent(), $pusher->getMessage()));
    }

    //  监听 websocket 收到消息事件
    public function onMessage(Server $server, Frame $frame)
    {
        Log::info("从 {$frame->fd} 接收到的数据: {$frame->data}");

        //  判断是否是心跳连接，如果是心跳连接的话跳过不做处理，否则的话将收到的信息进行解码
        if ($this->parser->execute($server, $frame)) {
            return;
        }

        //  解析客户端消息
        $payload = $this->parser->decode($frame);

        list($event, $data) = $payload;

        $payload = [
            'sender' => $frame->fd,
            'fds'    => [$frame->fd],
            'broadcast' => false,
            'assigned'  => false,
            'event'     => $event,
            'message'   => $data,
        ];
        $pusher = Pusher::make($payload, $server);

        $pusher->push($this->parser->encode($pusher->getEvent(), $pusher->getMessage()));
    }

    //  监听 websocket 关闭事件
    public function onClose(Server $server, $fd, $reactorId)
    {
        Log::info('WebSocket 连接关闭:' . $fd);
    }
}
