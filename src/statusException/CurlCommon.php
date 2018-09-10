<?php
/**
 * Created by PhpStorm.
 * User: Administrator
 * Date: 2018/9/10 18:37
 */

namespace Gkcosapi\Cospackage\statusException;


class CurlCommon
{
    /**
     * curl请求
     * @param $url
     * @param string $method
     * @param array $header
     * @param array $body
     * @return mixed
     */
    public static function requestWithHeader($url, $method = 'POST', $header = array(), $body = array())
    {
        //array_push($header, 'Accept:application/json');
        //array_push($header, 'Content-Type:application/json');
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 60);
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        switch ($method) {
            case "GET" :
                curl_setopt($ch, CURLOPT_HTTPGET, true);
                break;
            case "POST" :
                curl_setopt($ch, CURLOPT_POST, true);
                break;
            case "PUT" :
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
                break;
            case "DELETE" :
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
                break;
        }
        curl_setopt($ch, CURLOPT_USERAGENT, 'SSTS Browser/1.0');
        curl_setopt($ch, CURLOPT_ENCODING, 'gzip');
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);
        if (isset($body{3}) > 0) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $body);
        }
        if (count($header) > 0) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $header);
        }
        $ret = curl_exec($ch);
        curl_close($ch);

        $data = json_decode($ret, true);
        return $data;
    }

    /**
     * 事务异常错误日志输出
     * @param string $content
     */
    public static function logUnusualError(\Exception $exception)
    {
        Log::error('**************************');
        Log::error(print_r($exception->getFile(), true));
        Log::error(print_r($exception->getLine(), true));
        Log::error(print_r($exception->getMessage(), true));
        Log::error('**************************');
    }
}
