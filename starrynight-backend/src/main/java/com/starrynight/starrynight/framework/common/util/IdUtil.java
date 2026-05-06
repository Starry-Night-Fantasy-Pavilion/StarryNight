package com.starrynight.starrynight.framework.common.util;

import java.util.UUID;

public class IdUtil {

    public static String generateId() {
        return UUID.randomUUID().toString().replace("-", "");
    }

    public static String generateShortId() {
        return UUID.randomUUID().toString().substring(0, 8);
    }
}

