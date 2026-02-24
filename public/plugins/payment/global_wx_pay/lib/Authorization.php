<?php
namespace gateways\global_wx_pay\lib;

/**
 * wechatpay-api-v3 签名授权认证
 */
class Authorization
{
    private $http_method;
    private $timestamp;
    private $url;
    private $nonce;
    private $serial_no;
    private $merchant_id;
    private $certFilePath;
    private $requestBody;
    private $schema = "WECHATPAY2-SHA256-RSA2048";
    public function setHttpMethod($http_method)
    {
        $this->http_method = $http_method;
    }
    public function setTimestamp($timestamp)
    {
        $this->timestamp = $timestamp;
    }
    public function setUrl($url)
    {
        $this->url = $url;
    }
    public function setNonce($nonce = "")
    {
        if (empty($nonce)) {
            $this->nonce = $this->getNonceStr();
        } else {
            $this->nonce = $nonce;
        }
    }
    public function setSerialNo($serial_no)
    {
        $this->serial_no = $serial_no;
    }
    public function getMerchantId()
    {
        return $this->merchant_id;
    }
    public function setMerchantId($merchant_id)
    {
        $this->merchant_id = $merchant_id;
    }
    public function setCertFilePath($certFilePath)
    {
        $this->certFilePath = $certFilePath;
    }
    public function setRequestBody($body = [])
    {
        if (empty($body)) {
            $this->requestBody = "";
        } else {
            $this->requestBody = json_encode($body);
        }
    }
    public function setSchema($schema)
    {
        $this->schema = $schema;
    }
    private function getSign()
    {
        $url_parts = parse_url($this->url);
        $canonical_url = $url_parts["path"] . (!empty($url_parts["query"]) ? "?" . $url_parts["query"] : "");
        $message = $this->http_method . "\n" . $canonical_url . "\n" . $this->timestamp . "\n" . $this->nonce . "\n" . $this->requestBody . "\n";
        $mch_private_key = $this->getPrivateKey($this->certFilePath);
        openssl_sign($message, $raw_sign, $mch_private_key, "sha256WithRSAEncryption");
        $sign = base64_encode($raw_sign);
        return $sign;
    }
    private function getToken()
    {
        $token = sprintf("mchid=\"%s\",nonce_str=\"%s\",signature=\"%s\",timestamp=\"%d\",serial_no=\"%s\"", $this->merchant_id, $this->nonce, $this->getSign(), $this->timestamp, $this->serial_no);
        return $token;
    }
    public function getAuthorization()
    {
        return $this->schema . " " . $this->getToken();
    }
    private function getNonceStr($length = 32)
    {
        $chars = "abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789";
        $str = "";
        for ($i = 0; $i < $length; $i++) {
            $str .= substr($chars, mt_rand(0, strlen($chars) - 1), 1);
        }
        return $str;
    }
    private function getPrivateKey($filepath)
    {
        return openssl_get_privatekey(file_get_contents($filepath));
    }
}

?>