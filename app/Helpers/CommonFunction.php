<?php
if (!function_exists('api_response'))
{
    /**
     * @param $code -状态码 0-没有错误 1-存在错误
     * @param $data -数据
     * @param $msg -错误信息
     * @return \Illuminate\Http\JsonResponse
     * API 数据返回格式
     */
    function api_response($code, $data, $msg)
    {
        return response()->json([
            'errno' => $code,
            'data' => $data ?: [],
            'message' => $msg
        ]);
    }
}
