package com.starrynight.starrynight.framework.common.exception;

public class ResourceNotFoundException extends BaseException {

    public ResourceNotFoundException(String resource, Long id) {
        super(404, String.format("%s with id %d not found", resource, id));
    }

    public ResourceNotFoundException(String message) {
        super(404, message);
    }
}

