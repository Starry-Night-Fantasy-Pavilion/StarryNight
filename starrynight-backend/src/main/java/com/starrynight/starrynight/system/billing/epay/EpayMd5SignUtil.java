package com.starrynight.starrynight.system.billing.epay;

import java.nio.charset.StandardCharsets;
import java.security.MessageDigest;
import java.security.NoSuchAlgorithmException;
import java.util.ArrayList;
import java.util.Collections;
import java.util.List;
import java.util.Locale;
import java.util.Map;
import java.util.TreeMap;

/**
 * 标准易支付 / 彩虹易支付 V1（submit.php）常见 MD5 签名：参数名 ASCII 升序，剔除 sign、sign_type 与空值，拼接为 {@code k=v&} 后追加商户密钥再 MD5（小写十六进制）。
 */
public final class EpayMd5SignUtil {

    private EpayMd5SignUtil() {}

    public static String buildSignString(Map<String, String> params) {
        TreeMap<String, String> sorted = new TreeMap<>();
        for (Map.Entry<String, String> e : params.entrySet()) {
            String k = e.getKey();
            String v = e.getValue();
            if (k == null || k.isEmpty()) {
                continue;
            }
            if ("sign".equalsIgnoreCase(k) || "sign_type".equalsIgnoreCase(k)) {
                continue;
            }
            if (v == null || v.isEmpty()) {
                continue;
            }
            sorted.put(k, v);
        }
        List<String> keys = new ArrayList<>(sorted.keySet());
        Collections.sort(keys);
        StringBuilder sb = new StringBuilder();
        for (int i = 0; i < keys.size(); i++) {
            String key = keys.get(i);
            if (i > 0) {
                sb.append('&');
            }
            sb.append(key).append('=').append(sorted.get(key));
        }
        return sb.toString();
    }

    public static String sign(Map<String, String> params, String merchantKey) {
        return md5Lower(buildSignString(params) + merchantKey);
    }

    public static boolean verify(Map<String, String> params, String merchantKey) {
        String expect = params.get("sign");
        if (expect == null || expect.isEmpty()) {
            return false;
        }
        String calc = sign(params, merchantKey);
        return calc.equalsIgnoreCase(expect.trim());
    }

    public static String md5Lower(String input) {
        try {
            MessageDigest md = MessageDigest.getInstance("MD5");
            byte[] digest = md.digest(input.getBytes(StandardCharsets.UTF_8));
            StringBuilder sb = new StringBuilder(digest.length * 2);
            for (byte b : digest) {
                sb.append(String.format(Locale.ROOT, "%02x", b));
            }
            return sb.toString();
        } catch (NoSuchAlgorithmException e) {
            throw new IllegalStateException("MD5", e);
        }
    }
}
