package com.starrynight.starrynight.framework.common.exception;

import com.starrynight.starrynight.framework.common.vo.ResponseVO;
import org.slf4j.Logger;
import org.slf4j.LoggerFactory;
import org.springframework.http.HttpStatus;
import org.springframework.http.converter.HttpMessageNotReadableException;
import org.springframework.validation.BindException;
import org.springframework.web.bind.MethodArgumentNotValidException;
import org.springframework.dao.DataAccessException;
import org.springframework.web.bind.annotation.ExceptionHandler;
import org.springframework.web.bind.annotation.ResponseStatus;
import org.springframework.web.bind.annotation.RestControllerAdvice;

@RestControllerAdvice
public class GlobalExceptionHandler {

    private static final Logger log = LoggerFactory.getLogger(GlobalExceptionHandler.class);

    @ExceptionHandler(ResourceNotFoundException.class)
    @ResponseStatus(HttpStatus.NOT_FOUND)
    public ResponseVO<Void> handleResourceNotFound(ResourceNotFoundException ex) {
        log.warn("resource_not_found: {}", ex.getMessage());
        return ResponseVO.fail(ex.getCode(), ex.getMessage());
    }

    @ExceptionHandler(BusinessException.class)
    @ResponseStatus(HttpStatus.BAD_REQUEST)
    public ResponseVO<Void> handleBusiness(BusinessException ex) {
        log.warn("business_error: {}", ex.getMessage());
        return ResponseVO.fail(ex.getCode(), ex.getMessage());
    }

    @ExceptionHandler(BaseException.class)
    @ResponseStatus(HttpStatus.INTERNAL_SERVER_ERROR)
    public ResponseVO<Void> handleBase(BaseException ex) {
        log.error("base_error: {}", ex.getMessage(), ex);
        return ResponseVO.fail(ex.getCode(), ex.getMessage());
    }

    @ExceptionHandler(MethodArgumentNotValidException.class)
    @ResponseStatus(HttpStatus.BAD_REQUEST)
    public ResponseVO<Void> handleValidation(MethodArgumentNotValidException ex) {
        String message = ex.getBindingResult().getFieldErrors().stream()
                .map(error -> error.getField() + ": " + error.getDefaultMessage())
                .reduce((a, b) -> a + "; " + b)
                .orElse("Validation failed");
        log.warn("validation_error: {}", message);
        return ResponseVO.fail(400, message);
    }

    @ExceptionHandler(BindException.class)
    @ResponseStatus(HttpStatus.BAD_REQUEST)
    public ResponseVO<Void> handleBind(BindException ex) {
        String message = ex.getFieldErrors().stream()
                .map(error -> error.getField() + ": " + error.getDefaultMessage())
                .reduce((a, b) -> a + "; " + b)
                .orElse("Bind failed");
        log.warn("bind_error: {}", message);
        return ResponseVO.fail(400, message);
    }

    @ExceptionHandler(HttpMessageNotReadableException.class)
    @ResponseStatus(HttpStatus.BAD_REQUEST)
    public ResponseVO<Void> handleUnreadable(HttpMessageNotReadableException ex) {
        log.warn("http_message_not_readable: {}", ex.getMessage());
        return ResponseVO.fail(400, "请求体须为合法 JSON（勿对 JSON 二次转义）");
    }

    @ExceptionHandler(DataAccessException.class)
    @ResponseStatus(HttpStatus.INTERNAL_SERVER_ERROR)
    public ResponseVO<Void> handleDataAccess(DataAccessException ex) {
        Throwable root = ex.getMostSpecificCause();
        log.error("data_access_error root={}: {}",
                root != null ? root.getClass().getName() : "null",
                root != null ? root.getMessage() : ex.getMessage(), ex);
        return ResponseVO.fail(500, "Internal server error");
    }

    @ExceptionHandler(Exception.class)
    @ResponseStatus(HttpStatus.INTERNAL_SERVER_ERROR)
    public ResponseVO<Void> handleGeneral(Exception ex) {
        log.error("unexpected_error: {}", ex.getMessage(), ex);
        return ResponseVO.fail(500, "Internal server error");
    }
}

