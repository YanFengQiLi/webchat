<?php


namespace App\Services\WebSocket;

use Illuminate\Support\Facades\App;

/**
 * Class Parse
 * @package App\Services\WebSocket
 * 数据解析器
 * 因为我们前端使用的 socket.io-client 作为 websocket 客户端, 但是 laravels 这个包与 socket.io 不兼容, 但是还有一个包 laravel-swoole 是与之兼容的, 文档见:
 * https://github.com/swooletw/laravel-swoole/wiki/7.-Websocket , 这里我们照搬了 laravel-swoole 里 src/Websocket/Parser.php 的代码
 *
 *
 */
abstract class Parser
{
    /**
     * Strategy classes need to implement handle method.
     */
    protected $strategies = [];

    /**
     * Execute strategies before decoding payload.
     * If return value is true will skip decoding.
     *
     * @param \Swoole\WebSocket\Server $server
     * @param \Swoole\WebSocket\Frame $frame
     *
     * @return boolean
     */
    public function execute($server, $frame)
    {
        $skip = false;

        foreach ($this->strategies as $strategy) {
            $result = App::call(
                $strategy . '@handle',
                [
                    'server' => $server,
                    'frame' => $frame,
                ]
            );
            if ($result === true) {
                $skip = true;
                break;
            }
        }

        return $skip;
    }

    /**
     * Encode output payload for websocket push.
     *
     * @param string $event
     * @param mixed $data
     *
     * @return mixed
     */
    abstract public function encode(string $event, $data);

    /**
     * Input message on websocket connected.
     * Define and return event name and payload data here.
     *
     * @param \Swoole\Websocket\Frame $frame
     *
     * @return array
     */
    abstract public function decode($frame);
}
