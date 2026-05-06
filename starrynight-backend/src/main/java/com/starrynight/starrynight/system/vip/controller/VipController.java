package com.starrynight.starrynight.system.vip.controller;

import com.starrynight.starrynight.framework.common.vo.ResponseVO;
import com.starrynight.starrynight.system.vip.entity.MemberSubscription;
import com.starrynight.starrynight.system.vip.entity.VipPackage;
import com.starrynight.starrynight.system.vip.service.VipMembershipService;
import lombok.RequiredArgsConstructor;
import lombok.extern.slf4j.Slf4j;
import org.springframework.web.bind.annotation.*;

import java.util.List;
import java.util.Map;

@Slf4j
@RestController
@RequestMapping("/api/vip")
@RequiredArgsConstructor
public class VipController {

    private final VipMembershipService membershipService;

    @GetMapping("/packages")
    public ResponseVO<List<VipPackage>> getPackages(
            @RequestParam(required = false) Integer memberLevel) {
        List<VipPackage> packages;
        if (memberLevel != null) {
            packages = membershipService.getPackagesByLevel(memberLevel);
        } else {
            packages = membershipService.getActivePackages();
        }
        return ResponseVO.success(packages);
    }

    @GetMapping("/package/{id}")
    public ResponseVO<VipPackage> getPackage(@PathVariable Long id) {
        VipPackage pkg = membershipService.getPackageById(id);
        return ResponseVO.success(pkg);
    }

    @GetMapping("/benefits")
    public ResponseVO<Map<String, Object>> getBenefits(@RequestParam Long userId) {
        Map<String, Object> benefits = membershipService.getMemberBenefits(userId);
        return ResponseVO.success(benefits);
    }

    @GetMapping("/status")
    public ResponseVO<Map<String, Object>> getStatus(@RequestParam Long userId) {
        Map<String, Object> status = Map.of(
                "memberLevel", membershipService.getMemberLevel(userId),
                "memberLevelName", membershipService.getMemberLevelName(membershipService.getMemberLevel(userId)),
                "isActive", membershipService.isMembershipActive(userId),
                "dailyFreeQuota", membershipService.getDailyFreeQuota(userId)
        );
        return ResponseVO.success(status);
    }

    @GetMapping("/subscription")
    public ResponseVO<MemberSubscription> getSubscription(@RequestParam Long userId) {
        MemberSubscription subscription = membershipService.getActiveSubscription(userId);
        return ResponseVO.success(subscription);
    }

    @GetMapping("/subscription/history")
    public ResponseVO<List<MemberSubscription>> getHistory(@RequestParam Long userId) {
        List<MemberSubscription> history = membershipService.getUserSubscriptionHistory(userId);
        return ResponseVO.success(history);
    }

    @GetMapping("/check-benefit")
    public ResponseVO<Boolean> checkBenefit(@RequestParam Long userId, @RequestParam String benefitKey) {
        boolean has = membershipService.hasBenefit(userId, benefitKey);
        return ResponseVO.success(has);
    }

    @PostMapping("/activate")
    public ResponseVO<MemberSubscription> activate(
            @RequestParam Long userId,
            @RequestParam Long packageId) {
        try {
            MemberSubscription subscription = membershipService.activateMembership(userId, packageId);
            return ResponseVO.success(subscription);
        } catch (Exception e) {
            log.error("Failed to activate membership: {}", e.getMessage());
            return ResponseVO.error(e.getMessage());
        }
    }
}
