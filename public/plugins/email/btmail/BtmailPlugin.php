<?php
namespace mail\btmail;

// 防止类重复声明
if (class_exists(__NAMESPACE__ . '\BtmailPlugin')) {
    return;
}

class BtmailPlugin extends \app\admin\lib\Plugin
{
    public $info = ["name" => "Btmail", "title" => "宝塔邮局", "description" => "宝塔邮局", "status" => 1, "author" => "Lincry", "version" => "1.0", "help_url" => ""];
    const ATTACHMENTS_ADDRESS = "./upload/common/email/";

    /**
     * 获取插件信息（实现抽象方法）
     * @return array
     */
    public function getInfo(): array
    {
        return $this->info;
    }

    public function btmailidcsmartauthorize()
    {
    }
    public function install()
    {
        return true;
    }
    public function uninstall()
    {
        return true;
    }
    public function send($params)
    {
        $mail = $this->getMail($params["config"]);
        $mail_to = $params["email"];
        $content = $params["content"];
        $pdata["mail_from"] = $mail["Username"];
        $pdata["password"] = $mail["Password"];
        $pdata["mail_to"] = $mail_to;
        $pdata["subtype"] = "html";
        $pdata["subject"] = $params["subject"];
        $pdata["content"] = $content;
        $result = $this->requests($mail["Host"], $pdata);
        $result = json_decode($result, true);
        if ($result["status"] !== true) {
            return ["status" => "error", "msg" => $result["msg"]];
        }
        return ["status" => "success"];
    }
    public function getMail($config)
    {
        $mail = [];
        $mail["Host"] = $config["host"] . "/mail_sys/send_mail_http.json";
        $mail["Username"] = $config["username"];
        $mail["Password"] = $config["password"];
        return $mail;
    }
    public function requests($url, array $data)
    {
        $header = ["User-Agent: Apifox/1.0.0 (https://www.apifox.cn)", "Accept: */*"];
        $curl = curl_init();
        curl_setopt_array($curl, [CURLOPT_URL => $url, CURLOPT_POSTFIELDS => http_build_query($data), CURLOPT_POST => true, CURLOPT_RETURNTRANSFER => true, CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1, CURLOPT_HTTPHEADER => $header, CURLOPT_SSL_VERIFYHOST => false, CURLOPT_SSL_VERIFYPEER => false]);
        $result = curl_exec($curl);
        
        if (curl_errno($curl)) {
            $error = curl_error($curl);
            curl_close($curl);
            throw new \Exception("CURL请求失败: " . $error);
        }
        
        curl_close($curl);
        return $result;
    }
}

?>