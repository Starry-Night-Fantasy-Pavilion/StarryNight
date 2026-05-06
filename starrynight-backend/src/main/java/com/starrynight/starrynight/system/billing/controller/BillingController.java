package com.starrynight.starrynight.system.billing.controller;

import com.starrynight.starrynight.framework.common.vo.ResponseVO;
import com.starrynight.starrynight.system.billing.dto.ChargeRequest;
import com.starrynight.starrynight.system.billing.dto.ChargeResult;
import com.starrynight.starrynight.system.billing.service.BillingService;
import lombok.RequiredArgsConstructor;
import lombok.extern.slf4j.Slf4j;
import org.springframework.validation.annotation.Validated;
import org.springframework.web.bind.annotation.*;

@Slf4j
@RestController
@RequestMapping("/api/billing")
@RequiredArgsConstructor
public class BillingController {

    private final BillingService billingService;

    @PostMapping("/charge")
    public ResponseVO<ChargeResult> charge(@Validated @RequestBody ChargeRequest request) {
        try {
            ChargeResult result = billingService.charge(request);
            return ResponseVO.success(result);
        } catch (Exception e) {
            log.error("Billing charge failed: {}", e.getMessage(), e);
            return ResponseVO.error(e.getMessage());
        }
    }

    @PostMapping("/rollback")
    public ResponseVO<Void> rollback(@RequestParam String recordNo, @RequestParam String reason) {
        try {
            billingService.rollback(recordNo, reason);
            return ResponseVO.success(null);
        } catch (Exception e) {
            log.error("Billing rollback failed: {}", e.getMessage(), e);
            return ResponseVO.error(e.getMessage());
        }
    }
}
