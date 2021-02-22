<?php


namespace App\Services\WebSocket\SocketIO\Strategies;

use App\Services\WebSocket\SocketIO\Packet;

/**
 * Class HeartbeatStrategy
 * @package App\Services\WebSocket\SocketIO\Strategies
 *
 * 心跳连接策略类
 *
 * 照搬 laravel-swoole 里 SocketIOParser 的代码, 见:
 * https://github.com/swooletw/laravel-swoole/blob/master/src/Websocket/SocketIO/Strategies/HeartbeatStrategy.php
 *
 */
class HeartbeatStrategy
{
    /**
     * If return value is true will skip decoding.
     *
     * @param \Swoole\WebSocket\Server $server
     * @param \Swoole\WebSocket\Frame $frame
     *
     * @return boolean
     */
    public function handle($server, $frame)
    {
        $packet = $frame->data;
        $packetLength = strlen($packet);
        $payload = '';

        if (Packet::getPayload($packet)) {
            return false;
        }

        if ($isPing = Packet::isSocketType($packet, 'ping')) {
            $payload .= Packet::PONG;
        }

        if ($isPing && $packetLength > 1) {
            $payload .= substr($packet, 1, $packetLength - 1);
        }

        if ($isPing) {
            $server->push($frame->fd, $payload);
        }

        return true;
    }
}
