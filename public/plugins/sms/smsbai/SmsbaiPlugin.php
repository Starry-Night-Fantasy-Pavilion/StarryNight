<?php
namespace sms\Smsbai;

// 防止类重复声明
if (class_exists(__NAMESPACE__ . '\SmsbaiPlugin')) {
    return;
}

class SmsbaiPlugin extends \app\admin\lib\Plugin
{
    public $info = ["name" => "Smsbai", "title" => "天迹云通信", "description" => "天迹云通信", "status" => 1, "author" => "天迹", "version" => "2.3.0", "help_url" => "https://smsimp.com/"];

    /**
     * 获取插件信息（实现 Plugin 抽象方法）
     */
    public function getInfo(): array
    {
        return $this->info;
    }

    public function smsbaiidcsmartauthorize()
    {
    }
    public function install()
    {
        $smsTemplate = [];
        if (file_exists(__DIR__ . "/config/smsTemplate.php")) {
            $smsTemplate = (require __DIR__ . "/config/smsTemplate.php");
        }
        return $smsTemplate;
    }
    public function uninstall()
    {
        return true;
    }
    public function description()
    {
        return file_get_contents(__DIR__ . "/config/description.html");
    }
    public function getCnTemplate($params)
    {
        $data["status"] = "success";
        $data["template"]["template_status"] = 2;
        return $data;
    }
    public function createCnTemplate($params)
    {
        $data["status"] = "success";
        $data["template"]["template_status"] = 2;
        return $data;
    }
    public function putCnTemplate($params)
    {
        $data["status"] = "success";
        $data["template"]["template_status"] = 2;
        return $data;
    }
    public function deleteCnTemplate($params)
    {
        $data["status"] = "success";
        return $data;
    }
    public function sendCnSms($params)
    {
        $content = $this->templateParam($params["content"], $params["templateParam"]);
        $param["content"] = $this->templateSign($params["config"]["sign"]) . $content;
        $param["mobile"] = trim($params["mobile"]);
        $resultTemplate = $this->APIHttpRequestCURL("cn", $param, $params["config"]);
        if ($resultTemplate["status"] == "success") {
            $data["status"] = "success";
            $data["content"] = $content;
        } else {
            $data["status"] = "error";
            $data["content"] = $content;
            $data["msg"] = $resultTemplate["msg"];
        }
        return $data;
    }
    public function getGlobalTemplate($params)
    {
        $data["status"] = "success";
        $data["template"]["template_status"] = 2;
        return $data;
    }
    public function createGlobalTemplate($params)
    {
        $data["status"] = "success";
        $data["template"]["template_status"] = 2;
        return $data;
    }
    public function putGlobalTemplate($params)
    {
        $data["status"] = "success";
        $data["template"]["template_status"] = 2;
        return $data;
    }
    public function deleteGlobalTemplate($params)
    {
        $data["status"] = "success";
        return $data;
    }
    public function sendGlobalSms($params)
    {
        $content = $this->templateParam($params["content"], $params["templateParam"]);
        $param["content"] = $this->templateSign($params["config"]["sign"]) . $content;
        $param["mobile"] = trim($params["mobile"]);
        $resultTemplate = $this->APIHttpRequestCURL("global", $param, $params["config"]);
        if ($resultTemplate["status"] == "success") {
            $data["status"] = "success";
            $data["content"] = $content;
        } else {
            $data["status"] = "error";
            $data["content"] = $content;
            $data["msg"] = $resultTemplate["msg"];
        }
        return $data;
    }
    private function APIHttpRequestCURL($sms_type = "cn", $params, $config)
    {
        if ($sms_type == "cn") {
            $url = "https://smsimp.com/api/index/Sms";
        } else {
            if ($sms_type == "global") {
                $url = "https://smsimp.com/api/index/wms";
            }
        }
        $statusStr = ["短信发送成功", "未知原因", "18446744073709551614" => "参数不全", "30" => "错误KEY", "40" => "该平台账户不存在", "41" => "该平台账户额度不足", "42" => "内容中无短信签名", "43" => "该平台账户已被禁用", "44" => "签名不存在或不是您的签名", "45" => "签名未通过审核", "46" => "账户未通过实名认证", "50" => "内容含有敏感词", "51" => "手机号码不正确"];
        $user = $config["user"];
        $pass = md5($config["pass"]);
        $content = $params["content"];
        $phone = $params["mobile"];
        $sendurl = $url . "?u=" . $user . "&p=" . $pass . "&m=" . $phone . "&c=" . urlencode($content);
        $result = file_get_contents($sendurl);
        if ($result == "0") {
            return ["status" => "success", "msg" => $statusStr[$result]];
        }
        return ["status" => "error", "msg" => $statusStr[$result] . ". Code: " . $result];
    }
    private function templateParam($content, $templateParam)
    {
        foreach ($templateParam as $key => $para) {
            $content = str_replace("{" . $key . "}", $para, $content);
        }
        return $content;
    }
    private function templateSign($sign)
    {
        $sign = str_replace("【", "", $sign);
        $sign = str_replace("】", "", $sign);
        $sign = "【" . $sign . "】";
        return $sign;
    }
}

?>