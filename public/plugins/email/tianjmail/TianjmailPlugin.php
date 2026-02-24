<?php
namespace mail\tianjmail;

// 防止类重复声明
if (class_exists(__NAMESPACE__ . '\TianjmailPlugin')) {
    return;
}

class TianjmailPlugin extends \app\admin\lib\Plugin
{
    public $info = ["name" => "Tianjmail", "title" => "天迹邮件接口服务", "description" => "天迹企业邮服务", "status" => 1, "author" => "天迹云", "version" => "1.0.6", "help_url" => "https://mail.tji0.com"];

    /**
     * 获取插件信息（实现抽象方法）
     * @return array
     */
    public function getInfo(): array
    {
        return $this->info;
    }

    public function tianjmailidcsmartauthorize()
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
        $config = $this->getConfig();
        $param = ["email" => $params["email"], "title" => $params["subject"], "content" => $params["content"], "name" => $config["name"], "u" => $config["username"], "p" => md5($config["Key"])];
        $result = $this->curl_post_request("https://mail.tji0.com/api/index/Mail", $param);
        if ($result == "0") {
            $data["status"] = "success";
        } else {
            $data["status"] = "error";
            $data["msg"] = "服务端错误";
        }
        return $data;
    }
    private function curl_post_request($url, $data = NULL)
    {
        if (is_array($data)) {
            $data = http_build_query($data);
        }
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_POST, 1);
        if ($data != NULL) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        }
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        if (strpos($url, "https://") !== false) {
            curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
            curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
            $UserAgent = "Mozilla/4.0 (compatible; MSIE 7.0; Windows NT 6.0; SLCC1; .NET CLR 2.0.50727; .NET CLR 3.0.04506; .NET CLR 3.5.21022; .NET CLR 1.0.3705; .NET CLR 1.1.4322)";
            curl_setopt($ch, CURLOPT_USERAGENT, $UserAgent);
        }
        $data = curl_exec($ch);
        curl_close($ch);
        if ($data === false) {
            $data = "curl Error:" . curl_error($ch);
        }
        return $data;
    }
}

?>