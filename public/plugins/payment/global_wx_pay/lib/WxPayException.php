<?php
namespace gateways\global_wx_pay\lib;

class WxPayException extends \think\Exception
{
    public function errorMessage()
    {
        return $this->getMessage();
    }
}

?>