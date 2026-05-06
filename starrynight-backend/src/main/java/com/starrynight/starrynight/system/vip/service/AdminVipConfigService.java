package com.starrynight.starrynight.system.vip.service;

import com.baomidou.mybatisplus.core.conditions.query.LambdaQueryWrapper;
import com.starrynight.starrynight.framework.common.exception.BusinessException;
import com.starrynight.starrynight.framework.common.exception.ResourceNotFoundException;
import com.starrynight.starrynight.system.vip.dto.AdminBenefitConfigUpdateDTO;
import com.starrynight.starrynight.system.vip.dto.AdminVipPackageSaveDTO;
import com.starrynight.starrynight.system.vip.entity.MemberBenefitConfig;
import com.starrynight.starrynight.system.vip.entity.VipPackage;
import com.starrynight.starrynight.system.vip.mapper.MemberBenefitConfigMapper;
import com.starrynight.starrynight.system.vip.mapper.VipPackageMapper;
import lombok.RequiredArgsConstructor;
import org.springframework.stereotype.Service;
import org.springframework.transaction.annotation.Transactional;

import java.util.List;

@Service
@RequiredArgsConstructor
public class AdminVipConfigService {

    private final VipPackageMapper vipPackageMapper;
    private final MemberBenefitConfigMapper benefitConfigMapper;

    public List<VipPackage> listAllPackages() {
        return vipPackageMapper.selectList(
                new LambdaQueryWrapper<VipPackage>()
                        .eq(VipPackage::getDeleted, 0)
                        .orderByAsc(VipPackage::getSortOrder)
                        .orderByAsc(VipPackage::getId));
    }

    @Transactional(rollbackFor = Exception.class)
    public VipPackage createPackage(AdminVipPackageSaveDTO dto) {
        if (dto.getPackageCode() == null || dto.getPackageCode().isBlank()) {
            throw new BusinessException("套餐编码不能为空");
        }
        Long n = vipPackageMapper.selectCount(
                new LambdaQueryWrapper<VipPackage>()
                        .eq(VipPackage::getPackageCode, dto.getPackageCode().trim())
                        .eq(VipPackage::getDeleted, 0));
        if (n != null && n > 0) {
            throw new BusinessException("套餐编码已存在");
        }
        VipPackage p = toEntity(new VipPackage(), dto);
        p.setPackageCode(dto.getPackageCode().trim());
        vipPackageMapper.insert(p);
        return vipPackageMapper.selectById(p.getId());
    }

    @Transactional(rollbackFor = Exception.class)
    public VipPackage updatePackage(Long id, AdminVipPackageSaveDTO dto) {
        VipPackage existing = vipPackageMapper.selectById(id);
        if (existing == null || (existing.getDeleted() != null && existing.getDeleted() != 0)) {
            throw new ResourceNotFoundException("套餐不存在");
        }
        if (dto.getPackageCode() != null && !dto.getPackageCode().isBlank()
                && !dto.getPackageCode().trim().equals(existing.getPackageCode())) {
            Long n = vipPackageMapper.selectCount(
                    new LambdaQueryWrapper<VipPackage>()
                            .eq(VipPackage::getPackageCode, dto.getPackageCode().trim())
                            .eq(VipPackage::getDeleted, 0)
                            .ne(VipPackage::getId, id));
            if (n != null && n > 0) {
                throw new BusinessException("套餐编码已被占用");
            }
            existing.setPackageCode(dto.getPackageCode().trim());
        }
        toEntity(existing, dto);
        vipPackageMapper.updateById(existing);
        return vipPackageMapper.selectById(id);
    }

    private static VipPackage toEntity(VipPackage p, AdminVipPackageSaveDTO dto) {
        p.setPackageName(dto.getPackageName());
        p.setDescription(dto.getDescription());
        p.setMemberLevel(dto.getMemberLevel());
        p.setDurationDays(dto.getDurationDays());
        p.setPrice(dto.getPrice());
        p.setOriginalPrice(dto.getOriginalPrice());
        p.setDailyFreeQuota(dto.getDailyFreeQuota());
        p.setFeatures(dto.getFeatures());
        p.setSortOrder(dto.getSortOrder());
        p.setStatus(dto.getStatus());
        return p;
    }

    public List<MemberBenefitConfig> listBenefitConfigs(Integer memberLevel) {
        LambdaQueryWrapper<MemberBenefitConfig> q = new LambdaQueryWrapper<MemberBenefitConfig>()
                .orderByAsc(MemberBenefitConfig::getMemberLevel)
                .orderByAsc(MemberBenefitConfig::getBenefitKey);
        if (memberLevel != null) {
            q.eq(MemberBenefitConfig::getMemberLevel, memberLevel);
        }
        return benefitConfigMapper.selectList(q);
    }

    @Transactional(rollbackFor = Exception.class)
    public MemberBenefitConfig updateBenefitConfig(Long id, AdminBenefitConfigUpdateDTO dto) {
        MemberBenefitConfig row = benefitConfigMapper.selectById(id);
        if (row == null) {
            throw new ResourceNotFoundException("权益配置不存在");
        }
        row.setBenefitName(dto.getBenefitName());
        row.setBenefitValue(dto.getBenefitValue());
        row.setDescription(dto.getDescription());
        row.setEnabled(dto.getEnabled());
        benefitConfigMapper.updateById(row);
        return benefitConfigMapper.selectById(id);
    }
}
