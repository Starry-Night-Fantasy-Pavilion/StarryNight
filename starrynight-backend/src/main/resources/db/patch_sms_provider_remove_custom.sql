-- 短信服务商仅保留阿里云 / 腾讯云；历史若填 custom 则改回 aliyun
UPDATE system_config
SET description = 'aliyun 或 tencent',
    config_value = CASE WHEN LOWER(TRIM(config_value)) = 'custom' THEN 'aliyun' ELSE config_value END
WHERE config_key = 'sms.provider';
