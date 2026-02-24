<?php
namespace sms\sms007idc;

// 防止类重复声明
if (class_exists(__NAMESPACE__ . '\Sms007idcPlugin')) {
    return;
}

class Sms007idcPlugin extends \app\admin\lib\Plugin
{
    public $info = ["name" => "Sms007idc", "title" => "零零七短信平台", "description" => "零零七短信平台", "status" => 1, "author" => "零零七云计算", "version" => "1.1", "help_url" => "#"];

    /**
     * 获取插件信息（实现 Plugin 抽象方法）
     */
    public function getInfo(): array
    {
        return $this->info;
    }

    public function sms007idcidcsmartauthorize()
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
            $url = "http://8.130.73.63:9090/sms/batch/v1";
        } else {
            if ($sms_type == "global") {
                $url = "http://8.130.73.63:9090/sms/batch/v1";
            }
        }
        $statusStr = ["00000" => "短信发送成功", "F0001" => "参数appkey未填写", "F0002" => "参数appcode未填写", "F0003" => "参数phone未填写", "F0004" => "参数sign未填写", "F0005" => "参数timestamp未填写", "F0006" => "appkey不存在", "F0007" => "账号已经关闭", "F0008" => "sign检验错误", "F0009" => "账号下没有业务", "F0010" => "业务不存在", "F0011" => "手机号码超过1000个", "F0012" => "timestamp不是数字", "F0013" => "timestamp过期超过5分钟", "F0014" => "请求ip不在白名单内", "F0015" => "余额不足", "F0016" => "手机号码无效", "F0017" => "没有可用的业务", "F0022" => "参数msg未填写", "F0023" => "msg超过了1000个字", "F0024" => "extend不是纯数字", "F0025" => "内容签名未报备/无签名", "F0039" => "参数sms未填写", "F0040" => "参数sms格式不正确", "F0041" => "短信条数超过1000条", "F0050" => "无数据", "F0100" => "未知错误"];
        $time = explode(" ", microtime());
        $time = $time[1] . $time[0] * 1000;
        $time2 = explode(".", $time);
        $time = $time2[0];
        $appkey = $config["user"];
        $appcode = $config["pass"];
        $content = $params["content"];
        $appsecret = $config["secret"];
        $signcode = md5($appkey . $appsecret . $time);
        $arr = ["appkey" => $appkey, "appcode" => $appcode, "sign" => $signcode, "phone" => $params["mobile"], "msg" => $content, "timestamp" => $time];
        $data_string = json_encode($arr);
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data_string);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ["Content-Type: application/json", "Content-Length: " . strlen($data_string)]);
        $json = curl_exec($ch);
        $json_Array = json_decode($json, true);
        $result = $json_Array["code"];
        if ($result == "00000") {
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