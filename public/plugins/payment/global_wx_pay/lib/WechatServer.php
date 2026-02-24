<?php
namespace gateways\global_wx_pay\lib;

class WechatServer extends \think\Controller
{
    public function savePlatformCertificate($serialNo, $merchantId, $platformCertSerialNoPath, $platformCertPath, $platformCertNo, $aesKey)
    {
        $certificate = $this->getCertificates($serialNo, $merchantId);
        if ($certificate !== false) {
            $aesUtilObj = new AesUtil($aesKey);
            foreach ($certificate as $k => $v) {
                if ($v["serial_no"] == $platformCertNo) {
                    $certContent = $aesUtilObj->decryptToString($v["encrypt_certificate"]["associated_data"], $v["encrypt_certificate"]["nonce"], $v["encrypt_certificate"]["ciphertext"]);
                    if (!empty($certContent)) {
                        $res1 = file_put_contents($platformCertPath, $certContent);
                        $res2 = file_put_contents($platformCertSerialNoPath, $v["serial_no"]);
                        return $res1 && $res2;
                    }
                }
            }
        }
        return false;
    }
    public function getCertificates($serialNo, $merchantId)
    {
        $url = "https://api.mch.weixin.qq.com/v3/certificates";
        $class = new \gateways\global_wx_pay\GlobalWxPayPlugin();
        $config = $class->Config();
        $certConfig = ["apiclientKeyPath" => $config["apiclient_key"], "apiclientCertPath" => $config["apiclient_cert"], "platformCertSerialNoPath" => $config["platformCertSerialNoPath"], "platformCertPath" => $config["platformCertficate"]];
        $authorizationObj = new Authorization();
        $authorizationObj->setHttpMethod("GET");
        $authorizationObj->setTimestamp(time());
        $authorizationObj->setUrl($url);
        $authorizationObj->setSerialNo($serialNo);
        $authorizationObj->setMerchantId($merchantId);
        $authorizationObj->setNonce();
        $authorizationObj->setCertFilePath($certConfig["apiclientKeyPath"]);
        $body = [];
        $authorizationObj->setRequestBody($body);
        $authorization = $authorizationObj->getAuthorization();
        $header = ["Accept: application/json", "Accept-Language: zh-CN", "Authorization: " . $authorization, "Content-Type: application/json", "Postman-Token: 989014e6-af63-4188-95b1-0635365a38ff", "cache-control: no-cache", "User-Agent:" . $_SERVER["HTTP_USER_AGENT"]];
        $http = new Http();
        $result = $http->curl($url, "GET", $body, 30, $header);
        $data = json_decode($result, true);
        if (isset($data["data"][0]["serial_no"])) {
            return $data["data"];
        }
        return false;
    }
    public function verifySign($timestamp, $nonce, $body, $signature, $platformCertPath)
    {
        $data = $timestamp . "\n" . $nonce . "\n" . $body . "\n";
        $signature = base64_decode($signature);
        $publicKeyResource = openssl_get_publickey(file_get_contents($platformCertPath));
        return openssl_verify($data, $signature, $publicKeyResource, "sha256WithRSAEncryption");
    }
}

?>