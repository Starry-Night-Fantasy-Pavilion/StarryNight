package com.starrynight.starrynight.system.billing.controller;

import com.starrynight.starrynight.framework.common.vo.ResponseVO;
import com.starrynight.starrynight.system.billing.dto.BillingConfigDTO;
import com.starrynight.starrynight.system.billing.dto.ChannelDTO;
import com.starrynight.starrynight.system.billing.service.BillingChannelService;
import com.starrynight.starrynight.system.billing.service.BillingConfigService;
import lombok.RequiredArgsConstructor;
import lombok.extern.slf4j.Slf4j;
import org.springframework.validation.annotation.Validated;
import org.springframework.web.bind.annotation.*;

import java.math.BigDecimal;
import java.util.HashMap;
import java.util.List;
import java.util.Map;

@Slf4j
@RestController
@RequestMapping("/api/admin/billing")
@RequiredArgsConstructor
public class AdminBillingController {

    private final BillingChannelService channelService;
    private final BillingConfigService configService;

    @GetMapping("/config")
    public ResponseVO<BillingConfigDTO> getConfig() {
        BillingConfigDTO config = new BillingConfigDTO();
        config.setDailyFreeQuota(configService.getDailyFreeQuota());
        config.setDefaultProfitMargin(configService.getDefaultProfitMargin());
        config.setMixedPaymentDefault(configService.getMixedPaymentDefault());
        config.setFreeQuotaResetHour(configService.getFreeQuotaResetHour());
        config.setPlatformCurrencyRate(new BigDecimal("10"));
        config.setCreationPointRate(1000L);
        return ResponseVO.success(config);
    }

    @PutMapping("/config")
    public ResponseVO<Void> updateConfig(@RequestBody Map<String, String> configMap) {
        configMap.forEach(configService::updateConfig);
        return ResponseVO.success(null);
    }

    @GetMapping("/channel")
    public ResponseVO<List<ChannelDTO>> listChannels(
            @RequestParam(required = false) String type,
            @RequestParam(required = false) Boolean enabled) {
        List<ChannelDTO> channels = channelService.listChannels(type, enabled);
        return ResponseVO.success(channels);
    }

    @PostMapping("/channel")
    public ResponseVO<ChannelDTO> createChannel(@Validated @RequestBody ChannelDTO channel) {
        ChannelDTO result = channelService.createChannel(channel);
        return ResponseVO.success(result);
    }

    @PutMapping("/channel/{id}")
    public ResponseVO<ChannelDTO> updateChannel(@PathVariable Long id, @Validated @RequestBody ChannelDTO channel) {
        ChannelDTO result = channelService.updateChannel(id, channel);
        return ResponseVO.success(result);
    }

    @DeleteMapping("/channel/{id}")
    public ResponseVO<Void> deleteChannel(@PathVariable Long id) {
        channelService.deleteChannel(id);
        return ResponseVO.success(null);
    }

    @PostMapping("/channel/{id}/enable")
    public ResponseVO<Void> enableChannel(@PathVariable Long id) {
        channelService.enableChannel(id);
        return ResponseVO.success(null);
    }

    @PostMapping("/channel/{id}/disable")
    public ResponseVO<Void> disableChannel(@PathVariable Long id) {
        channelService.disableChannel(id);
        return ResponseVO.success(null);
    }

    @GetMapping("/report/daily")
    public ResponseVO<Map<String, Object>> getDailyReport() {
        Map<String, Object> report = new HashMap<>();
        report.put("date", java.time.LocalDate.now());
        return ResponseVO.success(report);
    }
}
