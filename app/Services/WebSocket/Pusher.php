<?php
namespace App\Services\WebSocket;

/**
 * Class Pusher
 * @package App\Services\WebSocket
 *
 * 通信数据发送类
 * 用于数据处理后发送给客户端的业务逻辑处理，包括数据解析和统一封装、是否广播等
 *
 * 重构后的 WebSocketHandler 类将只承担路由和控制器功能，涉及业务逻辑的相关服务都会被剥离到独立的业务单元中完成，
 * 包括数据发送，因为我们需要对其进行统一的封装处理，以便能够被客户端解析。
 *
 * 照搬 laravel-swoole 的 websocket 发送, 见:
 * https://github.com/swooletw/laravel-swoole/blob/master/src/Websocket/Pusher.php
 */
class Pusher
{
    /**
     * @var \Swoole\Websocket\Server
     */
    protected $server;

    /**
     * @var int
     * 发送数据内容的格式 1-文本数据 2-二进制数据
     */
    protected $opcode;

    /**
     * @var int
     */
    protected $sender;

    /**
     * @var array
     */
    protected $descriptors;

    /**
     * @var bool
     */
    protected $broadcast;

    /**
     * @var bool
     */
    protected $assigned;

    /**
     * @var string
     */
    protected $event;

    /**
     * @var mixed|null
     */
    protected $message;

    /**
     * Push constructor.
     *
     * @param int $opcode
     * @param int $sender
     * @param array $descriptors
     * @param bool $broadcast
     * @param bool $assigned
     * @param string $event
     * @param mixed|null $message
     * @param \Swoole\Websocket\Server
     */
    protected function __construct(
        int $opcode,
        int $sender,
        array $descriptors,
        bool $broadcast,
        bool $assigned,
        string $event,
        $message = null,
        $server
    )
    {
        $this->opcode = $opcode;
        $this->sender = $sender;
        $this->descriptors = $descriptors;
        $this->broadcast = $broadcast;
        $this->assigned = $assigned;
        $this->event = $event;
        $this->message = $message;
        $this->server = $server;
    }

    /**
     * Static constructor
     *
     * @param array $data
     * @param \Swoole\Websocket\Server $server
     *
     * @return Pusher
     */
    public static function make(array $data, $server)
    {
        return new static(
            $data['opcode'] ?? 1,
            $data['sender'] ?? 0,
            $data['fds'] ?? [],
            $data['broadcast'] ?? false,
            $data['assigned'] ?? false,
            $data['event'] ?? null,
            $data['message'] ?? null,
            $server
        );
    }

    /**
     * @return int
     */
    public function getOpcode(): int
    {
        return $this->opcode;
    }

    /**
     * @return int
     */
    public function getSender(): int
    {
        return $this->sender;
    }

    /**
     * @return array
     */
    public function getDescriptors(): array
    {
        return $this->descriptors;
    }

    /**
     * @param int $descriptor
     *
     * @return self
     */
    public function addDescriptor($descriptor): self
    {
        return $this->addDescriptors([$descriptor]);
    }

    /**
     * @param array $descriptors
     *
     * @return self
     */
    public function addDescriptors(array $descriptors): self
    {
        $this->descriptors = array_values(
            array_unique(
                array_merge($this->descriptors, $descriptors)
            )
        );

        return $this;
    }

    /**
     * @param int $descriptor
     *
     * @return bool
     */
    public function hasDescriptor(int $descriptor): bool
    {
        return in_array($descriptor, $this->descriptors);
    }

    /**
     * @return bool
     */
    public function isBroadcast(): bool
    {
        return $this->broadcast;
    }

    /**
     * @return bool
     */
    public function isAssigned(): bool
    {
        return $this->assigned;
    }

    /**
     * @return string
     */
    public function getEvent(): string
    {
        return $this->event;
    }

    /**
     * @return mixed|null
     */
    public function getMessage()
    {
        return $this->message;
    }

    /**
     * @return \Swoole\Websocket\Server
     */
    public function getServer()
    {
        return $this->server;
    }

    /**
     * @return bool
     */
    public function shouldBroadcast(): bool
    {
        return $this->broadcast && empty($this->descriptors) && ! $this->assigned;
    }

    /**
     * Returns all descriptors that are websocket
     *
     * @param \Swoole\Connection\Iterator $descriptors
     *
     * @return array
     */
    protected function getWebsocketConnections(): array
    {
        return array_filter(iterator_to_array($this->server->connections), function ($fd) {
            return $this->server->isEstablished($fd);
        });
    }

    /**
     * @param int $fd
     *
     * @return bool
     */
    public function shouldPushToDescriptor(int $fd): bool
    {
        if (! $this->server->isEstablished($fd)) {
            return false;
        }

        return $this->broadcast ? $this->sender !== (int) $fd : true;
    }

    /**
     * Push message to related descriptors
     *
     * @param mixed $payload
     *
     * @return void
     */
    public function push($payload): void
    {
        // attach sender if not broadcast
        if (! $this->broadcast && $this->sender && ! $this->hasDescriptor($this->sender)) {
            $this->addDescriptor($this->sender);
        }

        // check if to broadcast to other clients
        if ($this->shouldBroadcast()) {
            $this->addDescriptors($this->getWebsocketConnections());
        }

        // push message to designated fds
        foreach ($this->descriptors as $descriptor) {
            if ($this->shouldPushToDescriptor($descriptor)) {
                $this->server->push($descriptor, $payload, $this->opcode);
            }
        }
    }
}
