package com.starrynight.starrynight.system.billing.controller;

import com.starrynight.starrynight.framework.common.vo.ResponseVO;
import com.starrynight.starrynight.system.billing.dto.*;
import com.starrynight.starrynight.system.billing.service.BillingService;
import com.starrynight.starrynight.system.billing.service.RechargeService;
import lombok.RequiredArgsConstructor;
import lombok.extern.slf4j.Slf4j;
import org.springframework.validation.annotation.Validated;
import org.springframework.web.bind.annotation.*;

@Slf4j
@RestController
@RequestMapping("/api/user")
@RequiredArgsConstructor
public class UserBillingController {

    private final BillingService billingService;
    private final RechargeService rechargeService;

    @GetMapping("/balance")
    public ResponseVO<UserBalanceDTO> getBalance(@RequestParam Long userId) {
        UserBalanceDTO balance = billingService.getUserBalance(userId);
        return ResponseVO.success(balance);
    }

    @GetMapping("/balance/free")
    public ResponseVO<Long> getFreeQuota(@RequestParam Long userId) {
        UserBalanceDTO balance = billingService.getUserBalance(userId);
        return ResponseVO.success(balance.getFreeQuota());
    }

    @GetMapping("/balance/platform")
    public ResponseVO<Long> getPlatformCurrency(@RequestParam Long userId) {
        UserBalanceDTO balance = billingService.getUserBalance(userId);
        return ResponseVO.success(balance.getPlatformCurrencyInPoints());
    }

    @GetMapping("/mixed-payment")
    public ResponseVO<Boolean> getMixedPayment(@RequestParam Long userId) {
        UserBalanceDTO balance = billingService.getUserBalance(userId);
        return ResponseVO.success(balance.getEnableMixedPayment());
    }

    @PutMapping("/mixed-payment")
    public ResponseVO<Void> setMixedPayment(@RequestParam Long userId, @RequestParam Boolean enabled) {
        billingService.setMixedPayment(userId, enabled);
        return ResponseVO.success(null);
    }

    @PostMapping("/recharge")
    public ResponseVO<RechargeResult> recharge(@Validated @RequestBody RechargeRequest request) {
        RechargeResult result = rechargeService.createRechargeOrder(request);
        return ResponseVO.success(result);
    }

    @GetMapping("/cost/estimate")
    public ResponseVO<EstimateResult> estimateCost(
            @RequestParam Long userId,
            @RequestParam String contentType,
            @RequestParam(defaultValue = "0") Integer inputTokens,
            @RequestParam(defaultValue = "0") Integer outputTokens) {
        EstimateResult result = billingService.estimateCost(contentType, userId, inputTokens, outputTokens);
        return ResponseVO.success(result);
    }
}
