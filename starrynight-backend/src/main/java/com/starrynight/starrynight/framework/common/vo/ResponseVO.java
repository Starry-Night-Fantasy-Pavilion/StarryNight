package com.starrynight.starrynight.framework.common.vo;

import lombok.Data;

@Data
public class ResponseVO<T> {

    private Integer code;
    private String message;
    private T data;

    public static <T> ResponseVO<T> success() {
        return success(null);
    }

    public static <T> ResponseVO<T> success(T data) {
        ResponseVO<T> response = new ResponseVO<>();
        response.setCode(200);
        response.setMessage("Success");
        response.setData(data);
        return response;
    }

    public static <T> ResponseVO<T> success(String message, T data) {
        ResponseVO<T> response = new ResponseVO<>();
        response.setCode(200);
        response.setMessage(message);
        response.setData(data);
        return response;
    }

    public static <T> ResponseVO<T> fail(Integer code, String message) {
        ResponseVO<T> response = new ResponseVO<>();
        response.setCode(code);
        response.setMessage(message);
        return response;
    }

    public static <T> ResponseVO<T> fail(String message) {
        return fail(500, message);
    }

    /** 与历史代码中的 {@code ResponseVO.error(msg)} 对齐 */
    public static <T> ResponseVO<T> error(String message) {
        return fail(500, message);
    }
}

