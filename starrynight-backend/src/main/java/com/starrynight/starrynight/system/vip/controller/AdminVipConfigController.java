package com.starrynight.starrynight.system.vip.controller;

import com.starrynight.starrynight.framework.common.vo.ResponseVO;
import com.starrynight.starrynight.system.vip.dto.AdminBenefitConfigUpdateDTO;
import com.starrynight.starrynight.system.vip.dto.AdminVipPackageSaveDTO;
import com.starrynight.starrynight.system.vip.entity.MemberBenefitConfig;
import com.starrynight.starrynight.system.vip.entity.VipPackage;
import com.starrynight.starrynight.system.vip.service.AdminVipConfigService;
import jakarta.validation.Valid;
import lombok.RequiredArgsConstructor;
import org.springframework.security.access.prepost.PreAuthorize;
import org.springframework.web.bind.annotation.*;

import java.util.List;

/**
 * 运营端：VIP 套餐定价、等级权益配置（非前台用户购买接口）
 */
@RestController
@RequestMapping("/api/admin/vip")
@PreAuthorize("hasRole('ADMIN')")
@RequiredArgsConstructor
public class AdminVipConfigController {

    private final AdminVipConfigService adminVipConfigService;

    @GetMapping("/packages")
    public ResponseVO<List<VipPackage>> listPackages() {
        return ResponseVO.success(adminVipConfigService.listAllPackages());
    }

    @PostMapping("/packages")
    public ResponseVO<VipPackage> createPackage(@Valid @RequestBody AdminVipPackageSaveDTO dto) {
        return ResponseVO.success(adminVipConfigService.createPackage(dto));
    }

    @PutMapping("/packages/{id}")
    public ResponseVO<VipPackage> updatePackage(
            @PathVariable Long id,
            @Valid @RequestBody AdminVipPackageSaveDTO dto) {
        return ResponseVO.success(adminVipConfigService.updatePackage(id, dto));
    }

    @GetMapping("/benefit-configs")
    public ResponseVO<List<MemberBenefitConfig>> listBenefitConfigs(
            @RequestParam(required = false) Integer memberLevel) {
        return ResponseVO.success(adminVipConfigService.listBenefitConfigs(memberLevel));
    }

    @PutMapping("/benefit-configs/{id}")
    public ResponseVO<MemberBenefitConfig> updateBenefitConfig(
            @PathVariable Long id,
            @Valid @RequestBody AdminBenefitConfigUpdateDTO dto) {
        return ResponseVO.success(adminVipConfigService.updateBenefitConfig(id, dto));
    }
}
