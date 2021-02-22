<?php


namespace App\Services\WebSocket;

/**
 * Class WebSocket
 * @package App\Services\WebSocket
 *
 * websocket 服务类
 *
 * 因为聊天室在实际业务中诸多功能的复杂性,所以有必要去写一个完备的服务类去实现它, 比如房间的加入和退出、用户的认证和获取、数据的发送和广播等等
 * 这里为了方便实现一个基本的聊天室场景, 所以此类就预先定义出来, 后期在做拓展
 */
class WebSocket
{
    const PUSH_ACTION = 'push';
    const EVENT_CONNECT = 'connect';
    const USER_PREFIX = 'uid_';

    /**
     * Determine if to broadcast.
     *
     * @var boolean
     */
    protected $isBroadcast = false;

    /**
     * Scoket sender's fd.
     *
     * @var integer
     */
    protected $sender;

    /**
     * Recepient's fd or room name.
     *
     * @var array
     */
    protected $to = [];

    /**
     * Websocket event callbacks.
     *
     * @var array
     */
    protected $callbacks = [];
}
