<?php
namespace gateways\global_wx_pay;

require_once __DIR__ . "/lib/WxPayData.php";
class GlobalWxPayPlugin extends \app\admin\lib\Plugin
{
    protected $failException = true;
    public $info = ["name" => "GlobalWxPay", "title" => "微信国际支付", "description" => "微信国际支付", "status" => 1, "author" => "顺戴网络", "version" => "1.0", "module" => "gateways"];
    public $hasAdmin = 0;
    public function install()
    {
        return true;
    }
    public function uninstall()
    {
        return true;
    }
    public function globalWxPayHandle($param = [])
    {
        $config = $this->Config();
        $total_fee = (int) ($param["total_fee"] * 100);
        if ($total_fee < 2) {
            $total_fee = 2;
        }
        $requestBody = ["mchid" => $config["mch_id"], "appid" => $config["app_id"], "description" => $param["product_name"], "notify_url" => $config["notify_url"], "out_trade_no" => $this->out_trade_no($param["out_trade_no"]), "attach" => json_encode(["out_trade_no" => $param["out_trade_no"]]), "trade_type" => "NATIVE", "merchant_category_code" => $config["wx_pay_category_code"], "amount" => ["total" => $total_fee, "currency" => $param["fee_type"]]];
        $input = new lib\Authorization();
        $input->setHttpMethod("POST");
        $input->setTimestamp(time());
        $input->setUrl($config["gateway_url"]);
        $input->setSerialNo($config["serial_no"]);
        $input->setMerchantId($config["mch_id"]);
        $input->setNonce();
        $input->setCertFilePath($config["apiclient_key"]);
        $input->setRequestBody($requestBody);
        $authorization = $input->getAuthorization();
        $header = ["Accept: application/json", "Accept-Language: zh-CN", "Authorization: " . $authorization, "Content-Type: application/json", "Postman-Token: 989014e6-af63-4188-95b1-0635365a38ff", "cache-control: no-cache", "User-Agent:" . $_SERVER["HTTP_USER_AGENT"]];
        $http = new lib\Http();
        $result = $http->curl($config["gateway_url"], "POST", $requestBody, 30, $header, true);
        try {
            $arr = json_decode($result, true);
            $url = $arr["code_url"];
        } catch (\Exception $e) {
            return ["type" => "url", "data" => ""];
        }
        $reData = ["type" => "url", "data" => $url];
        return $reData;
    }
    public function Config()
    {
        $config = db("plugin")->where("name", $this->info["name"])->value("config");
        if (!empty($config) && $config != "null") {
            $config = json_decode($config, true);
            $con = (require dirname(__DIR__) . "/global_wx_pay/config/config.php");
            $config = array_merge($con, $config);
            return $config;
        }
        return json(["msg" => "请先将微信国际相关信息配置收入", "status" => 400]);
    }
    private function out_trade_no($out_trade_no)
    {
        $ret = "GWP" . uniqid() . randStr(16 - strlen($out_trade_no)) . $out_trade_no;
        return $ret;
    }
}

?>