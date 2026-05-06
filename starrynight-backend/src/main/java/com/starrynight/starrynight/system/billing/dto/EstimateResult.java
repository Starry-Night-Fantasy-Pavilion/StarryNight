package com.starrynight.starrynight.system.billing.dto;

import lombok.Data;

import java.math.BigDecimal;

@Data
public class EstimateResult {
    private Integer estimatedPoints;
    private String message;
    private String scenario;

    public static EstimateResult sufficient(Integer points) {
        EstimateResult result = new EstimateResult();
        result.setEstimatedPoints(points);
        result.setMessage("预计消耗 " + points + " 创作点。");
        result.setScenario("SUFFICIENT");
        return result;
    }

    public static EstimateResult mixedPayment(Integer points, Long freeRemaining, BigDecimal platformCurrencyNeeded) {
        EstimateResult result = new EstimateResult();
        result.setEstimatedPoints(points);
        result.setMessage("免费额度仅剩 " + freeRemaining + " 点，本次将扣尽免费额并额外支付 " + platformCurrencyNeeded + " 平台币。");
        result.setScenario("MIXED_PAYMENT");
        return result;
    }

    public static EstimateResult freeInsufficient(Integer points, Long freeRemaining) {
        EstimateResult result = new EstimateResult();
        result.setEstimatedPoints(points);
        result.setMessage("免费额度仅剩 " + freeRemaining + " 点，仍可生成，生成后免费额度将清零。确定继续吗？");
        result.setScenario("FREE_INSUFFICIENT");
        return result;
    }

    public static EstimateResult paidInsufficient(Integer points, BigDecimal paidRemaining) {
        EstimateResult result = new EstimateResult();
        result.setEstimatedPoints(points);
        result.setMessage("平台币不足以支付全额，将扣尽余额完成生成。");
        result.setScenario("PAID_INSUFFICIENT");
        return result;
    }

    public static EstimateResult insufficient() {
        EstimateResult result = new EstimateResult();
        result.setEstimatedPoints(0);
        result.setMessage("额度已用完，请充值或等待明日免费额度。");
        result.setScenario("INSUFFICIENT");
        return result;
    }
}
