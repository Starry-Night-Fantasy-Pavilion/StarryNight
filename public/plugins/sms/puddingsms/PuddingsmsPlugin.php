<?php
namespace sms\puddingsms;

// 防止类重复声明
if (class_exists(__NAMESPACE__ . '\PuddingsmsPlugin')) {
    return;
}

class PuddingsmsPlugin extends \app\admin\lib\Plugin
{
    public $info = ["name" => "Puddingsms", "title" => "布丁云短信v3", "description" => "布丁云插件", "status" => 1, "author" => "布丁", "version" => "3.0.5", "help_url" => "https://sms.idcbdy.cn/"];
    
    /**
     * 获取插件信息（实现 Plugin 抽象方法）
     */
    public function getInfo(): array
    {
        return $this->info;
    }
    
    public function Smsfyidcsmartauthorize()
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
        $resultTemplate = $this->APIHttpRequestCURL($param, $params["config"]);
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
    private function APIHttpRequestCURL($params, $config)
    {
        if ($config["apiurl"] == "cn") {
            $url = "https://sms.idcbdy.cn/sendApi";
        } else {
            if ($config["apiurl"] == "mg") {
                $url = "https://sms.idcbdy.cn/sendApi";
            } else {
                if ($config["apiurl"] == "xg") {
                    $url = "https://sms.idcbdy.cn/sendApi";
                }
            }
        }
        $statusStr = ["短信发送成功", "18446744073709551615" => "参数不齐", "18446744073709551614" => "服务器空间不支持,请确认支持curl或者fsocket，联系您的空间商解决或者更换空间", "30" => "密码错误", "40" => "账号不存在", "41" => "余额不足", "42" => "账户已过期", "43" => "IP地址限制", "50" => "内容含有敏感词"];
        $user = $config["user"];
        $pass = md5($config["pass"]);
        $g = $config["g"];
        $content = $params["content"];
        $phone = $params["mobile"];
        $sendurl = $url . "?channel=" . $g . "&username=" . $user . "&key=" . $pass . "&phone=" . $phone . "&content=" . urlencode($content);
        $result = json_decode(file_get_contents($sendurl), true);
        if ($result["code"] == "1") {
            return ["status" => "success", "msg" => $result["msg"]];
        }
        return ["status" => "error", "msg" => $result["msg"] . ". Code: " . $result["code"]];
    }
    private function stateStr($a)
    {
        $statusStr = ["短信发送成功", "18446744073709551615" => "参数不齐", "18446744073709551614" => "服务器空间不支持,请确认支持curl或者fsocket，联系您的空间商解决或者更换空间", "30" => "密码错误", "40" => "账号不存在", "41" => "余额不足", "42" => "账户已过期", "43" => "IP地址限制", "50" => "内容含有敏感词"];
        return $statusStr[$a];
    }
    private function get_curl($url, $post = NULL, $referer = 0, $cookie = 0, $header = 0, $ua = 0, $nobaody = 0, $addheader = [])
    {
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        $httpheader[] = "Accept: */*";
        $httpheader[] = "Accept-Encoding: gzip,deflate,sdch";
        $httpheader[] = "Accept-Language: zh-CN,zh;q=0.8";
        $httpheader[] = "Connection: keep-alive";
        if (!empty($addheader) && is_array($addheader)) {
            $httpheader = array_merge($httpheader, $addheader);
        }
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        if ($post) {
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $post);
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, $httpheader);
        if ($header) {
            curl_setopt($ch, CURLOPT_HEADER, true);
        }
        if ($cookie) {
            curl_setopt($ch, CURLOPT_COOKIE, $cookie);
        }
        if ($referer) {
            if ($referer == 1) {
                curl_setopt($ch, CURLOPT_REFERER, "http://www.baidu.com/");
            } else {
                curl_setopt($ch, CURLOPT_REFERER, $referer);
            }
        }
        if ($ua) {
            curl_setopt($ch, CURLOPT_USERAGENT, $ua);
        } else {
            curl_setopt($ch, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 10.0; WOW64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/55.0.2883.87 Safari/537.36");
        }
        if ($nobaody) {
            curl_setopt($ch, CURLOPT_NOBODY, 1);
        }
        curl_setopt($ch, CURLOPT_ENCODING, "gzip");
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        $ret = curl_exec($ch);
        if (curl_errno($ch)) {
            $code = 400;
            $ret = curl_error($ch);
        }
        curl_close($ch);
        return $ret;
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