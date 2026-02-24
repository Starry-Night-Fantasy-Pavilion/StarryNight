<?php
namespace gateways\global_ali_pay_new;

class GlobalAliPayNewPlugin extends \app\admin\lib\Plugin
{
    public $info = ["name" => "GlobalAliPayNew", "title" => "支付宝国际支付(新)", "description" => "支付宝国际支付(新)", "status" => 1, "author" => "顺戴网络", "version" => "1.0", "module" => "gateways"];
    public $hasAdmin = 0;
    public function install()
    {
        return true;
    }
    public function uninstall()
    {
        return true;
    }
    public function globalAliPayNewHandle($param)
    {
        $alipay_config = $this->Config();
        $data = ["service" => $alipay_config["service"], "partner" => $alipay_config["partner"], "notify_url" => $alipay_config["notify_url"], "return_url" => $alipay_config["return_url"], "refer_url" => $alipay_config["refer_url"]];
        $aliValidate = new validate\AliPayValidate();
        if (!$aliValidate->check($param)) {
            return json(["status" => 400, "msg" => $aliValidate->getError()]);
        }
        $data["body"] = str_replace("服务费", " Service Fee", $param["product_name"]);
        $data["out_trade_no"] = $param["out_trade_no"];
        $data["subject"] = $data["body"];
        if ($param["fee_type"] === "CNY") {
            $data["currency"] = "HKD";
            $data["rmb_fee"] = $param["total_fee"];
        } else {
            $data["currency"] = $param["fee_type"];
            $data["total_fee"] = $param["total_fee"];
        }
        $data["product_code"] = "NEW_WAP_OVERSEAS_SELLER";
        $trade_information = [];
        $trade_information["business_type"] = 5;
        $trade_information["other_business_type"] = $data["body"];
        $trade_information = json_encode($trade_information);
        $data["trade_information"] = $trade_information;
        $data["_input_charset"] = trim(strtolower($alipay_config["input_charset"]));
        $data["qr_pay_mode"] = 4;
        $data["qrcode_width"] = 200;
        $alipaySubmit = new lib\AlipaySubmit($alipay_config);
        $url = $alipaySubmit->buildRequestParaToString($data);
        $reData = ["type" => "insert", "data" => $url];
        return $reData;
    }
    public function Config()
    {
        $config = db("plugin")->where("name", $this->info["name"])->value("config");
        if (!empty($config) && $config != "null") {
            $config = json_decode($config, true);
            $con = (require dirname(__DIR__) . "/global_ali_pay_new/config/config.php");
            $config = array_merge($con, $config);
            return $config;
        }
        return json(["msg" => "请先将支付宝相关信息配置收入", "status" => 400]);
    }
}

?>