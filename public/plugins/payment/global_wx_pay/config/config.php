<?php
$domain = configuration("domain");
return ["app_id" => "", "serial_no" => "", "wx_pay_category_code" => "", "mch_id" => "", "app_secret" => "", "api_v3_secret" => "", "notify_url" => $domain . "/gateway/global_wx_pay/index/notify_handle", "return_url" => $domain . "/gateway/global_wx_pay/index/return_handle", "charset" => "UTF-8", "sign_type" => "RSA2", "apiclient_key" => "./cert/apiclient_key.pem", "apiclient_cert" => "./cert/apiclient_cert.pem", "apiclient_cert_p12" => "./cert/apiclient_cert.pi2", "platformCertificate" => "./cert/platformCertificate.pem", "platformCertSerialNoPath" => "./cert/platformCertSerialNoPath.txt", "gateway_url" => "https://api.mch.weixin.qq.com/hk/v3/transactions/native"];

?>