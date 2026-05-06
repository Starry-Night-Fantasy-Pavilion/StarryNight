package com.starrynight.starrynight.framework.common.util;

public class CsvUtil {

    private CsvUtil() {
    }

    public static String escape(String value) {
        if (value == null) {
            return "";
        }
        String v = value.replace("\"", "\"\"");
        if (v.contains(",") || v.contains("\n") || v.contains("\r") || v.contains("\"")) {
            return "\"" + v + "\"";
        }
        return v;
    }
}
