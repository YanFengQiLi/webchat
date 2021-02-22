<?php


namespace App\Services\WebSocket\SocketIO;


use App\Services\WebSocket\Parser;
use App\Services\WebSocket\SocketIO\Strategies\HeartbeatStrategy;

/**
 * Class SocketIOParser
 * @package App\Services\WebSocket\SocketIO
 *
 * socket.io 客户端对应的数据解析器
 *
 * 照搬 laravel-swoole 里 SocketIOParser 的代码, 见:
 * https://github.com/swooletw/laravel-swoole/blob/master/src/Websocket/SocketIO/SocketIOParser.php
 */
class SocketIOParser extends Parser
{
    /**
     * Strategy classes need to implement handle method.
     */
    protected $strategies = [
        HeartbeatStrategy::class,
    ];

    /**
     * Encode output payload for websocket push.
     *
     * @param string $event
     * @param mixed $data
     *
     * @return mixed
     */
    public function encode(string $event, $data)
    {
        $packet = Packet::MESSAGE . Packet::EVENT;
        $shouldEncode = is_array($data) || is_object($data);
        $data = $shouldEncode ? json_encode($data) : $data;
        $format = $shouldEncode ? '["%s",%s]' : '["%s","%s"]';

        return $packet . sprintf($format, $event, $data);
    }

    /**
     * Decode message from websocket client.
     * Define and return payload here.
     *
     * @param \Swoole\Websocket\Frame $frame
     *
     * @return array
     */
    public function decode($frame)
    {
        $payload = Packet::getPayload($frame->data);

        return [
            'event' => $payload['event'] ?? null,
            'data' => $payload['data'] ?? null,
        ];
    }
}
