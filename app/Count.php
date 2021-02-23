<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Count extends Model
{
    //  这里为了简化逻辑, 先将房间ID写死, 只有 1,2
    public static $ROOMLIST = [1, 2];

    public $timestamps = false;
}
