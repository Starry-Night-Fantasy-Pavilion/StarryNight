<?php
namespace gateways\global_ali_pay_new\controller;

class IndexController extends \think\Controller
{
    protected $gatewaymodule = "global_ali_pay";
    public function notify_handle()
    {
        trace(json_encode($_POST), $this->gatewaymodule . "_info");
        $class = new \gateways\global_ali_pay_new\GlobalAliPayNewPlugin();
        $alipay_config = $class->Config();
        $alipayNotify = new \gateways\global_ali_pay_new\lib\AlipayNotify($alipay_config);
        $verify_result = $alipayNotify->verifyNotify();
        if ($verify_result) {
            $this->orderHandle($_POST);
            echo "success";
        } else {
            trace("支付宝支付失败", "info");
            echo "fail";
        }
    }
    private function orderHandle($data)
    {
        trace("order_start" . json_encode($data), $this->gatewaymodule . "_info");
        $newData = ["invoice_id" => $data["out_trade_no"], "payment" => "GlobalAliPayNew", "paid_time" => $data["notify_time"], "trans_id" => $data["trade_no"], "amount_in" => $data["total_fee"], "currency" => $data["currency"]];
        trace("order_start" . json_encode($newData), $this->gatewaymodule . "_info");
        check_pay($newData);
    }
    public function return_handle()
    {
        return redirect(config("return_url"));
    }
    public function aliCheck($params)
    {
        return true;
    }
    public function getPayment($payCode)
    {
        $payment = $this->where("enabled=1 AND payCode='" . $payCode . "' AND isOnline=1")->find();
        $payConfig = json_decode($payment["payConfig"]);
        foreach ($payConfig as $key => $value) {
            $payment[$key] = $value;
        }
        return $payment;
    }
}

?>