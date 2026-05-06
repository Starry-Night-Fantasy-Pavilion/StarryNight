package com.starrynight.starrynight.framework.common.exception;

public class BusinessException extends BaseException {

    public BusinessException(String message) {
        super(400, message);
    }

    public BusinessException(Integer code, String message) {
        super(code, message);
    }
}

