<?php

use Aliyun\OTS\OTSClient;

if(!function_exists('result')) {
    /**
     * 返回数据
     *
     * @param string|array  $data  数据
     * @param int  $status  状态码
     * @return \Illuminate\Http\Response
     */
    function result($data = null, int $status = 200) {
        return response()->json(
            is_null($data) ? ['code' => 1] : ['code' => 1, 'data' => $data],
            $status
        );
    }
}

if(!function_exists('error')) {
    /**
     * 返回错误
     *
     * @param string  $msg  错误信息
     * @param int  $status  状态码
     * @return \Illuminate\Http\Response
     */
    function error($msg = '参数错误', int $status = 200) {
        return response()->json(
            ['code' => 0, 'message' => $msg],
            $status
        );
    }
}

if(!function_exists('get_ots_client')) {
    function get_ots_client() {
        $client = new OTSClient(config('config.ots'));

        $client->getClientConfig()->errorLogHandler = null;
        $client->getClientConfig()->debugLogHandler = null;

        return $client;
    }
}
