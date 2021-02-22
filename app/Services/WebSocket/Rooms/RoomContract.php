<?php


namespace App\Services\WebSocket\Rooms;

/**
 * Interface RoomContract
 * @package App\Services\WebSocket\Rooms
 *
 * 照搬 laravel-swoole 的 RoomContract, 见: https://github.com/swooletw/laravel-swoole/blob/master/src/Websocket/Rooms/RoomContract.php
 */
interface RoomContract
{
    const ROOMS_KEY = 'rooms';

    /**
     * Descriptors key
     *
     * @const string
     */
    const DESCRIPTORS_KEY = 'fds';

    /**
     * Do some init stuffs before workers started.
     *
     * @return \SwooleTW\Http\Websocket\Rooms\RoomContract
     */
    public function prepare(): RoomContract;

    /**
     * Add multiple socket fds to a room.
     *
     * @param int fd
     * @param array|string rooms
     */
    public function add(int $fd, $rooms);

    /**
     * Delete multiple socket fds from a room.
     *
     * @param int fd
     * @param array|string rooms
     */
    public function delete(int $fd, $rooms);

    /**
     * Get all sockets by a room key.
     *
     * @param string room
     *
     * @return array
     */
    public function getClients(string $room);

    /**
     * Get all rooms by a fd.
     *
     * @param int fd
     *
     * @return array
     */
    public function getRooms(int $fd);
}
