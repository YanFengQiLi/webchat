<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Class SocketIOController
 * @package App\Http\Controllers
 *
 *  基于 Socket.io 客户端发送心跳连接的方式保持长连接（客户端发送 2，服务端返回 3 作为应答）
 *   5，表示切换传输协议之前（比如升级到 Websocket），会测试服务器和客户端是否可以通过此传输进行通信，如果测试成功，
 *  客户端将发送升级数据包，请求服务器刷新旧传输上的缓存并切换到新传输。
 *
 *   其中 97 表示返回数据的长度，0 表示开启新的连接，然后是返回的负载数据 $payload：
 *   sid 表示本次通信的会话 ID；
 *   upgrades 表示升级的协议类型，这里是 websocket；
 *   pingInterval 表示 ping 的间隔时长，可以理解为保持长连接的心跳时间；
 *   pingTimeout 表示本次连接超时时间，长连接并不意味着永远不会销毁，否则系统资源就永远不能释放了，在心跳连接发起后，超过该超时时间没有任何通信则长连接会自动断开。
 *   再往后 2 表示客户端发出，服务端应该返回包含相同数据的 packet 进行应答（服务端返回数据以 3 作为前缀，表示应答，
 * 比如客户端发送 2probe 服务端返回 3probe，客户端发送 2，服务端返回 3，后者就是心跳连接），最后 40 中的 4 表示的是消息数据，0 表示消息以字节流返回。
 */
class SocketIOController extends Controller
{
    protected $transports = ['polling', 'websocket'];

    public function upgrade(Request $request)
    {
        if (! in_array($request->input('transport'), $this->transports)) {
            return response()->json(
                [
                    'code' => 0,
                    'message' => 'Transport unknown',
                ],
                400
            );
        }

        if ($request->has('sid')) {
            return '1:6';
        }

        $payload = json_encode([
            'sid' => base64_encode(uniqid()),
            'upgrades' => ['websocket'],
            'pingInterval' => config('laravels.swoole.heartbeat_idle_time') * 1000,
            'pingTimeout' => config('laravels.swoole.heartbeat_check_interval') * 1000
        ]);

//        Log::info('97:0' . $payload . '2:40');

        //  这里的返回数据可能看起来有点怪，这是遵循 Socket.io 通信协议的格式，以便客户端可以识别并作出正确的处理。我们简单介绍下这里返回的数据字段
        return response('97:0' . $payload . '2:40');
    }

    public function ok()
    {
        return response('ok');
    }
}
