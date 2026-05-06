package com.starrynight.starrynight.framework.common.util;

import org.apache.commons.lang3.StringUtils;

import java.util.regex.Pattern;

public class ValidationUtil {

    private static final Pattern USERNAME_PATTERN = Pattern.compile("^[a-zA-Z0-9_]{4,20}$");
    private static final Pattern EMAIL_PATTERN = Pattern.compile("^[A-Za-z0-9+_.-]+@[A-Za-z0-9.-]+$");
    private static final Pattern PHONE_PATTERN = Pattern.compile("^1[3-9]\\d{9}$");
    /** 大陆 18 位居民身份证号（与公安核验常用格式一致，不含港澳台居住证等） */
    private static final Pattern MAINLAND_ID_18 = Pattern.compile(
            "^[1-9]\\d{5}(18|19|20)\\d{2}(0[1-9]|1[0-2])(0[1-9]|[12]\\d|3[01])\\d{3}[0-9Xx]$");

    public static boolean isValidUsername(String username) {
        return StringUtils.isNotBlank(username) && USERNAME_PATTERN.matcher(username).matches();
    }

    public static boolean isValidEmail(String email) {
        return StringUtils.isNotBlank(email) && EMAIL_PATTERN.matcher(email).matches();
    }

    public static boolean isValidPhone(String phone) {
        return StringUtils.isNotBlank(phone) && PHONE_PATTERN.matcher(phone).matches();
    }

    public static boolean isValidPassword(String password) {
        return StringUtils.isNotBlank(password) && password.length() >= 6 && password.length() <= 32;
    }

    public static boolean isLikelyMainlandIdCard18(String s) {
        return StringUtils.isNotBlank(s) && MAINLAND_ID_18.matcher(s.trim()).matches();
    }
}

