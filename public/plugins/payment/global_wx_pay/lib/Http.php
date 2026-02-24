<?php
namespace gateways\global_wx_pay\lib;

/**
 *
 *
 * @category   默认
 * @package    PSR
 * @subpackage Documentation\API
 * @author     菜鸟  <894298959@qq.com>
 * @ctime:     2020/7/2 9:08
 */
class Http
{
    public function curl($url, $method = "get", $params = [], $timeout = 30, $headers = [], $is_json = false)
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, strtoupper($method));
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $timeout);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLINFO_HEADER_OUT, 1);
        if (!empty($headers)) {
            curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        }
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        if (strtoupper($method) !== "GET") {
            if ($is_json) {
                curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
            } else {
                curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
            }
        }
        $data = curl_exec($ch);
        if ($data) {
            curl_close($ch);
            return $data;
        }
        $error = curl_errno($ch);
        curl_close($ch);
        throw new WxPayException("curl出错，错误码:" . $error);
    }
}

?>