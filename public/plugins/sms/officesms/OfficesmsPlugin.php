<?php
namespace sms\officesms;

// 防止类重复声明
if (class_exists(__NAMESPACE__ . '\OfficesmsPlugin')) {
    return;
}

class OfficesmsPlugin extends \app\admin\lib\Plugin
{
    private $_messageId;
    public $error;
    public $api_error = "";
    public $base_url = "";
    public $datas = [];
    public $headers = [];
    public $info = ["name" => "Officesms", "title" => "第二办公室短信", "description" => "第二办公室短信插件", "status" => 1, "author" => "云外科技", "version" => "1.1", "help_url" => "https://www.2office.cn/"];
    
    /**
     * 获取插件信息（实现 PluginBase 抽象方法）
     */
    public function getInfo(): array
    {
        return $this->info;
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
        $timestamp = time();
        $content = $this->templateParam($params["content"], $params["templateParam"]);
        $param["to"] = trim($params["mobile"]);
        $param["content"] = $this->templateSign($params["config"]["signature"]) . $content;
        $this->setMessageId($timestamp);
        $this->setParams($param["to"], $param["content"], $params["config"], $timestamp);
        $this->setHeaders($params["config"], $timestamp);
        $api_url = "/SendSms?sign=" . md5($params["config"]["account"] . $params["config"]["authCode"] . $timestamp);
        $this->base_url = "http://open.2office.cn/Accounts/" . $params["config"]["account"] . "/Sms";
        $api = $this->base_url . $api_url;
        $resultTemplate = $this->sendPost($api, $this->datas, $this->headers);
        $resultTemplate = $this->processSendResult($resultTemplate);
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
    public function createCnProTemplate($params)
    {
        $data["status"] = "success";
        $data["template"]["template_status"] = 2;
        return $data;
    }
    public function putCnProTemplate($params)
    {
        $data["status"] = "success";
        $data["template"]["template_status"] = 2;
        return $data;
    }
    public function deleteCnProTemplate($params)
    {
        $data["status"] = "success";
        return $data;
    }
    public function sendCnProSms($params)
    {
        $timestamp = time();
        $content = $this->templateParam($params["content"], $params["templateParam"]);
        $param["to"] = trim($params["mobile"]);
        $param["content"] = $this->templateSign($params["config"]["signature"]) . $content;
        $this->setMessageId($timestamp);
        $this->setParams($param["to"], $param["content"], $params["config"], $timestamp);
        $this->setHeaders($params["config"], $timestamp);
        $api_url = "/SendSms?sign=" . md5($params["config"]["account"] . $params["config"]["authCode"] . $timestamp);
        $this->base_url = "http://open.2office.cn/Accounts/" . $params["config"]["account"] . "/Sms";
        $api = $this->base_url . $api_url;
        $resultTemplate = $this->sendPost($api, $this->datas, $this->headers);
        $resultTemplate = $this->processSendResult($resultTemplate);
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
    public function sendPost($url, $params, $header = NULL)
    {
        if (empty($url)) {
            return false;
        }
        if (is_array($params)) {
            $params = json_encode($params);
        }
        $length = strlen($params);
        $header[] = "Content-Length: " . $length;
        $options = [CURLOPT_URL => $url, CURLOPT_RETURNTRANSFER => true, CURLOPT_HTTPHEADER => $header, CURLOPT_POST => true, CURLOPT_POSTFIELDS => $params, CURLOPT_SSL_VERIFYHOST => false, CURLOPT_SSL_VERIFYPEER => false, CURLOPT_TIMEOUT => 20];
        $ch = curl_init();
        $setRes = curl_setopt_array($ch, $options);
        if (!$setRes) {
            $error = "curl error " . curl_errno($ch) . ": " . curl_error($ch);
            curl_close($ch);
            throw new \Exception("CURL设置失败: " . $error);
        }
        $result = curl_exec($ch);
        
        if (curl_errno($ch)) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new \Exception("CURL请求失败: " . $error);
        }
        
        curl_close($ch);
        return $result;
    }
    private function _generateMessageId()
    {
        $this->_messageId = time();
    }
    public function setMessageId($id)
    {
        $this->_messageId = $id;
    }
    public function getMessageId()
    {
        if (empty($this->_messageId)) {
            $this->_generateMessageId();
        }
        return $this->_messageId;
    }
    public function processSendResult($result)
    {
        if (is_bool($result) && !$result) {
            $data["msg"] = "接口不通";
        }
        $datas = json_decode($result, true);
        if (isset($datas["msg"]) && $datas["code"] != "0000000") {
            $data["msg"] = $datas["msg"];
        }
        $datas["code"] !== "0000000" ? $data["status"] : $data["status"];
        return $data;
    }
    public function setParams($mobile, $content, $config, $timestamp)
    {
        $params = ["appId" => $config["appid"], "mobile" => $mobile, "content" => $content, "channel" => $config["channel"], "smsid" => $this->getMessageId(), "sendType" => "1", "timestamp" => $timestamp];
        $this->datas = $params;
    }
    public function setHeaders($config, $timestamp)
    {
        $smgWhiteListIp = NULL;
        if ($smgWhiteListIp) {
            $headers = ["X-Forwarded-For: " . $smgWhiteListIp, "CLIENT_IP: " . $smgWhiteListIp, "VIA: " . $smgWhiteListIp, "REMOTE_ADDR: " . $smgWhiteListIp, "Content-Type: application/json;charset=UTF-8", "Connection: Keep-Alive", "Cache-Control: no-cache", "Pragma: no-cache", "Authorization:" . strtoupper(base64_encode($config["account"] . ":" . $timestamp))];
        } else {
            $headers = ["Content-Type: application/json;charset=UTF-8", "Connection: Keep-Alive", "Cache-Control: no-cache", "Pragma: no-cache", "Authorization:" . strtoupper(base64_encode($config["account"] . ":" . $timestamp))];
        }
        $this->headers = $headers;
    }
    private function templateParam($content, $templateParam)
    {
        foreach ($templateParam as $key => $para) {
            $content = str_replace("@var(" . $key . ")", $para, $content);
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