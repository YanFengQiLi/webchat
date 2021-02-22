<?php

namespace App\Services\WebSocket;

use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use InvalidArgumentException;

/**
 * Trait Authenticatable
 * @package App\Services\WebSocket
 *
 * 房间是静态的空间，在房间里聊天的是动态的用户，因此，我们还要编写用户认证相关实现代码，以便唯一区分不同用户以及管理不同房间、不同用户的聊天信息
 *
 * 照搬 laravel-swoole 的 用户房间认证 Authenticatable , 见: https://github.com/swooletw/laravel-swoole/blob/master/src/Websocket/Authenticatable.php
 */
trait Authenticatable
{
    protected $userId;

    /**
     * Login using current user.
     *
     * @param \Illuminate\Contracts\Auth\Authenticatable $user
     *
     * @return mixed
     */
    public function loginUsing(AuthenticatableContract $user)
    {
        return $this->loginUsingId($user->getAuthIdentifier());
    }

    /**
     * Login using current userId.
     *
     * @param $userId
     *
     * @return mixed
     */
    public function loginUsingId($userId)
    {
        return $this->join(static::USER_PREFIX . $userId);
    }

    /**
     * Logout with current sender's fd.
     *
     * @return mixed
     */
    public function logout()
    {
        if (is_null($userId = $this->getUserId())) {
            return null;
        }

        return $this->leave(static::USER_PREFIX . $userId);
    }

    /**
     * Set multiple recepients' fds by users.
     *
     * @param $users
     *
     * @return Authenticatable
     */
    public function toUser($users)
    {
        $users = is_object($users) ? func_get_args() : $users;

        $userIds = array_map(function (AuthenticatableContract $user) {
            $this->checkUser($user);

            return $user->getAuthIdentifier();
        }, $users);

        return $this->toUserId($userIds);
    }

    /**
     * Set multiple recepients' fds by userIds.
     *
     * @param $userIds
     *
     * @return Authenticatable
     */
    public function toUserId($userIds)
    {
        $userIds = is_string($userIds) || is_integer($userIds) ? func_get_args() : $userIds;

        foreach ($userIds as $userId) {
            $fds = $this->room->getClients(static::USER_PREFIX . $userId);
            $this->to($fds);
        }

        return $this;
    }

    /**
     * Get current auth user id by sender's fd.
     */
    public function getUserId()
    {
        if (! is_null($this->userId)) {
            return $this->userId;
        }

        $rooms = $this->room->getRooms($this->getSender());

        foreach ($rooms as $room) {
            if (count($explode = explode(static::USER_PREFIX, $room)) === 2) {
                $this->userId = $explode[1];
            }
        }

        return $this->userId;
    }

    /**
     * Check if a user is online by given userId.
     *
     * @param $userId
     *
     * @return bool
     */
    public function isUserIdOnline($userId)
    {
        return ! empty($this->room->getClients(static::USER_PREFIX . $userId));
    }

    /**
     * Check if user object implements AuthenticatableContract.
     *
     * @param $user
     */
    protected function checkUser($user)
    {
        if (! $user instanceOf AuthenticatableContract) {
            throw new InvalidArgumentException('user object must implement ' . AuthenticatableContract::class);
        }
    }
}
