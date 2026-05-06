package com.starrynight.starrynight.system.notification.service;

import com.aliyun.dysmsapi20170525.Client;
import com.aliyun.teaopenapi.models.Config;
import com.fasterxml.jackson.databind.ObjectMapper;
import com.starrynight.starrynight.framework.common.exception.BusinessException;
import com.starrynight.starrynight.system.system.service.RuntimeConfigService;
import com.tencentcloudapi.common.Credential;
import com.tencentcloudapi.common.profile.ClientProfile;
import com.tencentcloudapi.common.profile.HttpProfile;
import com.tencentcloudapi.sms.v20210111.SmsClient;
import com.tencentcloudapi.sms.v20210111.models.SendSmsRequest;
import com.tencentcloudapi.sms.v20210111.models.SendSmsResponse;
import com.tencentcloudapi.sms.v20210111.models.SendStatus;
import org.slf4j.Logger;
import org.slf4j.LoggerFactory;
import org.springframework.stereotype.Service;
import org.springframework.util.StringUtils;

import java.util.Map;

/**
 * 短信发送：阿里云、腾讯云。
 * <p>阿里云验证码模板：请在控制台将模板变量命名为 {@code code}（全小写），与本服务下发的 JSON
 * {@code {"code":"六位数字"}} 完全一致，否则发送会失败。
 */
@Service
public class SmsSendService {

    private static final Logger log = LoggerFactory.getLogger(SmsSendService.class);

    private final RuntimeConfigService runtime;
    private final ObjectMapper objectMapper = new ObjectMapper();

    public SmsSendService(RuntimeConfigService runtime) {
        this.runtime = runtime;
    }

    public boolean canSend() {
        if (!runtime.getBoolean("sms.enabled", false)) {
            return false;
        }
        String provider = runtime.getString("sms.provider", "aliyun").trim().toLowerCase();
        boolean keysOk = StringUtils.hasText(runtime.getString("sms.access-key-id", ""))
                && StringUtils.hasText(runtime.getProperty("sms.access-key-secret"))
                && StringUtils.hasText(runtime.getString("sms.sign-name", ""))
                && StringUtils.hasText(runtime.getString("sms.template.verification", ""));
        if (!keysOk) {
            return false;
        }
        return switch (provider) {
            case "aliyun" -> true;
            case "tencent" -> StringUtils.hasText(runtime.getString("sms.tencent.sdk-app-id", ""));
            default -> false;
        };
    }

    /**
     * 发送验证码类短信。
     */
    public void sendVerificationCode(String rawPhone, String code) {
        if (!runtime.getBoolean("sms.enabled", false)) {
            throw new BusinessException("短信发送未启用");
        }
        String phone = normalizeMainlandPhone(rawPhone);
        if (!StringUtils.hasText(phone)) {
            throw new BusinessException("手机号无效");
        }

        String provider = runtime.getString("sms.provider", "aliyun").trim().toLowerCase();
        switch (provider) {
            case "aliyun" -> sendAliyun(phone, code);
            case "tencent" -> sendTencent(phone, code);
            default -> throw new BusinessException("不支持的短信服务商: " + provider);
        }
    }

    /**
     * 请求体为 {@code {"code":"六位数字"}}，阿里云控制台对应模板的变量名必须填写为 {@code code}。
     */
    private void sendAliyun(String phone, String code) {
        String accessKeyId = runtime.getString("sms.access-key-id", "");
        String accessKeySecret = runtime.getProperty("sms.access-key-secret");
        String signName = runtime.getString("sms.sign-name", "");
        String templateCode = runtime.getString("sms.template.verification", "");

        if (!StringUtils.hasText(accessKeyId) || accessKeySecret == null || !StringUtils.hasText(accessKeySecret)) {
            throw new BusinessException("短信 AccessKey 未配置");
        }
        if (!StringUtils.hasText(signName) || !StringUtils.hasText(templateCode)) {
            throw new BusinessException("短信签名或模板编码未配置（sms.sign-name、sms.template.verification）");
        }

        try {
            String templateParam = objectMapper.writeValueAsString(Map.of("code", code));
            Config config = new Config()
                    .setAccessKeyId(accessKeyId.trim())
                    .setAccessKeySecret(accessKeySecret.trim())
                    .setEndpoint("dysmsapi.aliyuncs.com");
            Client client = new Client(config);

            com.aliyun.dysmsapi20170525.models.SendSmsRequest request =
                    new com.aliyun.dysmsapi20170525.models.SendSmsRequest()
                    .setPhoneNumbers(phone)
                    .setSignName(signName.trim())
                    .setTemplateCode(templateCode.trim())
                    .setTemplateParam(templateParam);

            com.aliyun.dysmsapi20170525.models.SendSmsResponse response = client.sendSms(request);
            String respCode = response.getBody() != null ? response.getBody().getCode() : null;
            if (!"OK".equals(respCode)) {
                String msg = response.getBody() != null ? response.getBody().getMessage() : "unknown";
                log.warn("Aliyun SMS failed code={} message={}", respCode, msg);
                String extra = "";
                if (msg != null && (msg.contains("模板") || msg.contains("变量") || msg.contains("parameter"))) {
                    extra = "（请核对：阿里云控制台该模板变量名须为 code）";
                }
                throw new BusinessException("短信发送失败: " + msg + extra);
            }
            log.debug("Aliyun SMS sent to {}", phone);
        } catch (BusinessException e) {
            throw e;
        } catch (Exception e) {
            log.warn("Aliyun SMS error: {}", e.toString());
            throw new BusinessException("短信发送失败: " + e.getMessage());
        }
    }

    private void sendTencent(String phone11, String code) {
        String secretId = runtime.getString("sms.access-key-id", "");
        String secretKey = runtime.getProperty("sms.access-key-secret");
        String sdkAppId = runtime.getString("sms.tencent.sdk-app-id", "");
        String signName = runtime.getString("sms.sign-name", "");
        String templateId = runtime.getString("sms.template.verification", "");
        String region = runtime.getString("sms.tencent.region", "ap-guangzhou").trim();

        if (!StringUtils.hasText(secretId) || secretKey == null || !StringUtils.hasText(secretKey)) {
            throw new BusinessException("短信 SecretId/SecretKey 未配置（sms.access-key-id / sms.access-key-secret）");
        }
        if (!StringUtils.hasText(sdkAppId)) {
            throw new BusinessException("腾讯云需配置 sms.tencent.sdk-app-id（短信应用 SdkAppId）");
        }
        if (!StringUtils.hasText(signName) || !StringUtils.hasText(templateId)) {
            throw new BusinessException("短信签名或模板 ID 未配置（sms.sign-name、sms.template.verification，腾讯云为数字模板 ID）");
        }

        try {
            Credential cred = new Credential(secretId.trim(), secretKey.trim());
            HttpProfile httpProfile = new HttpProfile();
            httpProfile.setEndpoint("sms.tencentcloudapi.com");
            ClientProfile clientProfile = new ClientProfile();
            clientProfile.setHttpProfile(httpProfile);
            SmsClient client = new SmsClient(cred, region, clientProfile);

            SendSmsRequest req = new SendSmsRequest();
            req.setSmsSdkAppId(sdkAppId.trim());
            req.setSignName(signName.trim());
            req.setTemplateId(templateId.trim());
            req.setTemplateParamSet(new String[]{code});
            req.setPhoneNumberSet(new String[]{"+86" + phone11});

            SendSmsResponse resp = client.SendSms(req);
            SendStatus[] statuses = resp.getSendStatusSet();
            if (statuses != null && statuses.length > 0) {
                String c = statuses[0].getCode();
                if (!"Ok".equals(c)) {
                    String msg = statuses[0].getMessage();
                    log.warn("Tencent SMS failed code={} message={}", c, msg);
                    throw new BusinessException("短信发送失败: " + (msg != null ? msg : c));
                }
            }
            log.debug("Tencent SMS sent to {}", phone11);
        } catch (BusinessException e) {
            throw e;
        } catch (Exception e) {
            log.warn("Tencent SMS error: {}", e.toString());
            throw new BusinessException("短信发送失败: " + e.getMessage());
        }
    }

    /** 支持 11 位国内号或带 +86 前缀。 */
    public static String normalizeMainlandPhone(String raw) {
        if (raw == null) {
            return "";
        }
        String p = raw.trim().replaceAll("\\s+", "");
        if (p.startsWith("+86")) {
            p = p.substring(3);
        }
        if (p.startsWith("86") && p.length() == 13) {
            p = p.substring(2);
        }
        if (p.matches("^1[3-9]\\d{9}$")) {
            return p;
        }
        return "";
    }
}
