<?php
namespace sms\officesms;

class BaseSms
{
    private $_messageId;
    public $error;
    public $api_error = "";
    private function _generateMessageId()
    {
        $this->_messageId = microtime(true) * 10000;
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
    public function error()
    {
        return $this->api_error ? $this->api_error : $this->error;
    }
    public function sendPost($url, $params, $header = NULL)
    {
        if (empty($url)) {
            return false;
        }
        if (!is_string($params)) {
            $params = http_build_query($params);
        }
        $length = strlen($params);
        $header[] = "Content-Length: " . $length;
        $options = [CURLOPT_URL => $url, CURLOPT_RETURNTRANSFER => true, CURLOPT_HTTPHEADER => $header, CURLOPT_POST => true, CURLOPT_POSTFIELDS => $params, CURLOPT_SSL_VERIFYHOST => false, CURLOPT_SSL_VERIFYPEER => false, CURLOPT_TIMEOUT => 20];
        $ch = curl_init();
        $setRes = curl_setopt_array($ch, $options);
        if (!$setRes) {
            $error = "curl error " . curl_errno($ch) . ": " . curl_error($ch);
            trigger_error($error);
        }
        $result = curl_exec($ch);
        curl_close($ch);
        return $result;
    }
}

?>