package com.starrynight.starrynight.system.billing.dto;

import lombok.Data;

import java.math.BigDecimal;
import java.time.LocalDateTime;

@Data
public class ChargeResult {
    private String recordNo;
    private Boolean success;
    private Integer creationPoints;
    private Integer freePointsUsed;
    private Integer paidPointsUsed;
    private BigDecimal platformCurrencyUsed;
    private BigDecimal userPrice;
    private String message;
    private String errorCode;

    public static ChargeResult success(String recordNo, Integer creationPoints, Integer freePointsUsed,
                                       Integer paidPointsUsed, BigDecimal platformCurrencyUsed, BigDecimal userPrice) {
        ChargeResult result = new ChargeResult();
        result.setRecordNo(recordNo);
        result.setSuccess(true);
        result.setCreationPoints(creationPoints);
        result.setFreePointsUsed(freePointsUsed);
        result.setPaidPointsUsed(paidPointsUsed);
        result.setPlatformCurrencyUsed(platformCurrencyUsed);
        result.setUserPrice(userPrice);
        result.setMessage("生成成功");
        return result;
    }

    public static ChargeResult fail(String recordNo, String errorCode, String message) {
        ChargeResult result = new ChargeResult();
        result.setRecordNo(recordNo);
        result.setSuccess(false);
        result.setErrorCode(errorCode);
        result.setMessage(message);
        return result;
    }
}
