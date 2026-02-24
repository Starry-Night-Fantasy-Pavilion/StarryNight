<?php
namespace gateways\global_wx_pay\controller;

/**
 * Class IndexController.
 */
class IndexController extends \think\Controller
{
    public function notify_handle()
    {
        trace("回调开始标记_input:" . json_encode(file_get_contents("php://input")), "info_wx_notice_log");
        trace("回调开始标记_server:" . json_encode($_SERVER), "info_wx_notice_log");
        $class = new \gateways\global_wx_pay\GlobalWxPayPlugin();
        $config = $class->Config();
        $input = file_get_contents("php://input");
        $headers = ["timestamp" => isset($_SERVER["HTTP_WECHATPAY_TIMESTAMP"]) ? $_SERVER["HTTP_WECHATPAY_TIMESTAMP"] : 0, "nonce" => isset($_SERVER["HTTP_WECHATPAY_NONCE"]) ? $_SERVER["HTTP_WECHATPAY_NONCE"] : "", "serialNo" => isset($_SERVER["HTTP_WECHATPAY_SERIAL"]) ? $_SERVER["HTTP_WECHATPAY_SERIAL"] : "", "signature" => isset($_SERVER["HTTP_WECHATPAY_SIGNATURE"]) ? $_SERVER["HTTP_WECHATPAY_SIGNATURE"] : ""];
        $data = json_decode($input, true);
        if (isset($data["id"]) && isset($data["event_type"]) && $data["event_type"] == "TRANSACTION.SUCCESS" && isset($data["resource_type"]) && $data["resource_type"] == "encrypt-resource") {
            $needUpdateCert = true;
            $certConfig = ["apiclientKeyPath" => $config["apiclient_key"], "apiclientCertPath" => $config["apiclient_cert"], "platformCertSerialNoPath" => $config["platformCertSerialNoPath"], "platformCertificate" => $config["platformCertificate"]];
            if (file_exists($certConfig["platformCertSerialNoPath"])) {
                $platformCertSerialNo = file_get_contents($certConfig["platformCertSerialNoPath"]);
                if ($platformCertSerialNo == $headers["serialNo"]) {
                    $needUpdateCert = false;
                }
            }
            $wechatObj = new \gateways\global_wx_pay\lib\WechatServer();
            if ($needUpdateCert) {
                $result = $wechatObj->savePlatformCertificate($config["serial_no"], $config["mch_id"], $certConfig["platformCertSerialNoPath"], $certConfig["platformCertificate"], $headers["serialNo"], $config["api_v3_secret"]);
                if (!$result) {
                    header("HTTP/1.1 404 Not Found");
                    exit;
                }
            }
            $verify = $wechatObj->verifySign($headers["timestamp"], $headers["nonce"], $input, $headers["signature"], $certConfig["platformCertificate"]);
            if ($verify === 1) {
                $aesUtilObj = new \gateways\global_wx_pay\lib\AesUtil($config["api_v3_secret"]);
                $resourceString = $aesUtilObj->decryptToString($data["resource"]["associated_data"], $data["resource"]["nonce"], $data["resource"]["ciphertext"]);
                $resource = json_decode($resourceString, true);
                trace("解析结果:" . $resourceString, "info_wx_notice_log");
                if (isset($resource["trade_state"]) && $resource["trade_state"] == "SUCCESS") {
                    $this->orderHandle(["invoice_id" => json_decode($resource["attach"], true)["out_trade_no"], "amount_in" => $resource["amount"]["total"] / 100, "trans_id" => $resource["out_trade_no"], "currency" => $resource["amount"]["currency"], "paid_time" => $resource["success_time"], "payment" => "GlobalWxPay"]);
                    $response = ["code" => "SUCCESS", "message" => "处理成功"];
                    header("Content-type: application/json");
                    echo json_encode($response, 256);
                    exit;
                }
            }
        }
        header("HTTP/1.1 404 Not Found");
        exit;
    }
    private function orderHandle($data)
    {
        trace("wx_data_start" . json_encode($data));
        $Order = new \app\home\controller\OrderController();
        $Order->orderPayHandle($data);
    }
}

?>