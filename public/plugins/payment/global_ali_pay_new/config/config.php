<?php
$domain = configuration("domain");
$info = parse_url($domain);
if ($info["schema"] == "https") {
    $protocol = "https";
} else {
    $protocol = "http";
}
return ["partner" => "2088621935295134", "key" => "fk03jzhvxqf2ulwzmflyw7ysied2g6tq", "notify_url" => $domain . "/gateway/global_ali_pay_new/index/notify_handle", "return_url" => $domain . "/gateway/global_ali_pay_new/index/return_handle", "refer_url" => $domain, "sign_type" => strtoupper("MD5"), "input_charset" => strtoupper("UTF-8"), "cacert" => getcwd() . "/cacert.pem", "transport" => $protocol, "service" => "create_forex_trade"];

?>