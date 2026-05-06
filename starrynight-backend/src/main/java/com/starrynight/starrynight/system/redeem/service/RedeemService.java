package com.starrynight.starrynight.system.redeem.service;

import com.baomidou.mybatisplus.core.conditions.query.LambdaQueryWrapper;
import com.baomidou.mybatisplus.extension.plugins.pagination.Page;
import com.starrynight.starrynight.framework.common.exception.BusinessException;
import com.starrynight.starrynight.framework.common.exception.ResourceNotFoundException;
import com.starrynight.starrynight.framework.common.vo.PageVO;
import com.starrynight.starrynight.system.growth.service.GrowthService;
import com.starrynight.starrynight.system.redeem.dto.RedeemCodeDTO;
import com.starrynight.starrynight.system.redeem.dto.RedeemGenerateRequest;
import com.starrynight.starrynight.system.redeem.dto.RedeemResultDTO;
import com.starrynight.starrynight.system.redeem.entity.RedeemCode;
import com.starrynight.starrynight.system.redeem.entity.RedeemRedemption;
import com.starrynight.starrynight.system.redeem.mapper.RedeemCodeMapper;
import com.starrynight.starrynight.system.redeem.mapper.RedeemRedemptionMapper;
import lombok.RequiredArgsConstructor;
import lombok.extern.slf4j.Slf4j;
import org.springframework.stereotype.Service;
import org.springframework.transaction.annotation.Transactional;
import org.springframework.util.StringUtils;

import java.math.BigDecimal;
import java.security.SecureRandom;
import java.time.LocalDateTime;
import java.util.ArrayList;
import java.util.List;
import java.util.stream.Collectors;

@Slf4j
@Service
@RequiredArgsConstructor
public class RedeemService {

    private static final String CODE_ALPHABET = "ABCDEFGHJKLMNPQRSTUVWXYZ23456789";

    private final RedeemCodeMapper redeemCodeMapper;
    private final RedeemRedemptionMapper redeemRedemptionMapper;
    private final GrowthService growthService;

    public PageVO<RedeemCodeDTO> page(String keyword, int page, int size) {
        LambdaQueryWrapper<RedeemCode> w = new LambdaQueryWrapper<>();
        if (StringUtils.hasText(keyword)) {
            String k = keyword.trim();
            w.and(q -> q.like(RedeemCode::getCode, k).or().like(RedeemCode::getBatchLabel, k));
        }
        w.orderByDesc(RedeemCode::getId);
        Page<RedeemCode> p = redeemCodeMapper.selectPage(new Page<>(page, size), w);
        List<RedeemCodeDTO> records = p.getRecords().stream().map(this::toDto).collect(Collectors.toList());
        return PageVO.of(p.getTotal(), records, p.getCurrent(), p.getSize());
    }

    public RedeemCodeDTO create(RedeemCodeDTO dto) {
        RedeemCode entity = new RedeemCode();
        applyWrite(dto, entity);
        entity.setCode(normalizeCode(dto.getCode()));
        entity.setRedemptionCount(0);
        if (entity.getRewardPoints() == null) {
            entity.setRewardPoints(0L);
        }
        if (entity.getRewardCurrency() == null) {
            entity.setRewardCurrency(BigDecimal.ZERO);
        }
        validateReward(entity.getRewardType(), entity.getRewardPoints(), entity.getRewardCurrency());
        redeemCodeMapper.insert(entity);
        return toDto(entity);
    }

    public RedeemCodeDTO update(Long id, RedeemCodeDTO dto) {
        RedeemCode entity = redeemCodeMapper.selectById(id);
        if (entity == null) {
            throw new ResourceNotFoundException("兑换码不存在");
        }
        applyWrite(dto, entity);
        entity.setCode(normalizeCode(dto.getCode()));
        validateReward(entity.getRewardType(), entity.getRewardPoints(), entity.getRewardCurrency());
        redeemCodeMapper.updateById(entity);
        return toDto(entity);
    }

    public void delete(Long id) {
        RedeemCode entity = redeemCodeMapper.selectById(id);
        if (entity == null) {
            throw new ResourceNotFoundException("兑换码不存在");
        }
        redeemCodeMapper.deleteById(id);
    }

    @Transactional(rollbackFor = Exception.class)
    public List<RedeemCodeDTO> generate(RedeemGenerateRequest req) {
        validateReward(req.getRewardType(), req.getRewardPoints(), req.getRewardCurrency());
        String prefix = req.getPrefix() != null ? req.getPrefix().trim().toUpperCase() : "";
        int len = req.getCodeLength();
        int suffixLen = len - prefix.length();
        if (suffixLen < 4) {
            throw new BusinessException("码长度不足或前缀过长");
        }
        SecureRandom rnd = new SecureRandom();
        List<RedeemCodeDTO> created = new ArrayList<>();
        for (int i = 0; i < req.getCount(); i++) {
            int attempts = 0;
            boolean added = false;
            while (attempts < 12 && !added) {
                String code = prefix + randomSuffix(rnd, suffixLen);
                Long exists = redeemCodeMapper.selectCount(new LambdaQueryWrapper<RedeemCode>().eq(RedeemCode::getCode, code));
                if (exists != null && exists > 0) {
                    attempts++;
                    continue;
                }
                RedeemCode entity = new RedeemCode();
                entity.setCode(code);
                entity.setBatchLabel(req.getBatchLabel());
                entity.setRewardType(req.getRewardType());
                entity.setRewardPoints(req.getRewardPoints() != null ? req.getRewardPoints() : 0L);
                entity.setRewardCurrency(req.getRewardCurrency() != null ? req.getRewardCurrency() : BigDecimal.ZERO);
                entity.setMaxTotalRedemptions(req.getMaxTotalRedemptions());
                entity.setRedemptionCount(0);
                entity.setMaxPerUser(req.getMaxPerUser());
                entity.setValidStart(req.getValidStart());
                entity.setValidEnd(req.getValidEnd());
                entity.setEnabled(req.getEnabled());
                entity.setCampaignId(req.getCampaignId());
                redeemCodeMapper.insert(entity);
                created.add(toDto(entity));
                added = true;
            }
            if (!added) {
                throw new BusinessException("生成兑换码冲突过多，请重试");
            }
        }
        return created;
    }

    @Transactional(rollbackFor = Exception.class)
    public RedeemResultDTO redeem(Long userId, String rawCode) {
        if (userId == null) {
            throw new BusinessException(401, "请先登录");
        }
        String code = normalizeCode(rawCode);
        if (!StringUtils.hasText(code)) {
            throw new BusinessException("请输入兑换码");
        }
        RedeemCode locked = redeemCodeMapper.selectByCodeForUpdate(code);
        if (locked == null) {
            throw new BusinessException("兑换码无效");
        }
        LocalDateTime now = LocalDateTime.now();
        if (locked.getEnabled() == null || locked.getEnabled() != 1) {
            throw new BusinessException("兑换码已停用");
        }
        if (locked.getValidStart() != null && now.isBefore(locked.getValidStart())) {
            throw new BusinessException("兑换码尚未生效");
        }
        if (locked.getValidEnd() != null && now.isAfter(locked.getValidEnd())) {
            throw new BusinessException("兑换码已过期");
        }
        if (locked.getMaxTotalRedemptions() != null
                && locked.getRedemptionCount() != null
                && locked.getRedemptionCount() >= locked.getMaxTotalRedemptions()) {
            throw new BusinessException("兑换码已达总兑换上限");
        }
        long userTimes = redeemRedemptionMapper.countByCodeAndUser(locked.getId(), userId);
        int perUser = locked.getMaxPerUser() != null ? locked.getMaxPerUser() : 1;
        if (userTimes >= perUser) {
            throw new BusinessException("您已达到该码的兑换次数上限");
        }

        RedeemRedemption redemption = new RedeemRedemption();
        redemption.setRedeemCodeId(locked.getId());
        redemption.setUserId(userId);
        redemption.setRewardType(locked.getRewardType());
        if ("platform_currency".equals(locked.getRewardType())) {
            BigDecimal grant = locked.getRewardCurrency() != null
                    ? locked.getRewardCurrency()
                    : BigDecimal.ZERO;
            redemption.setCurrencyGranted(grant);
            redemption.setPointsGranted(null);
        } else {
            long pts = locked.getRewardPoints() != null ? locked.getRewardPoints() : 0L;
            redemption.setPointsGranted(pts);
            redemption.setCurrencyGranted(null);
        }
        redeemRedemptionMapper.insert(redemption);

        String desc = "兑换码奖励";
        if ("platform_currency".equals(locked.getRewardType())) {
            BigDecimal grant = locked.getRewardCurrency() != null ? locked.getRewardCurrency() : BigDecimal.ZERO;
            growthService.grantRedeemPlatformCurrency(userId, grant, desc);
        } else {
            long pts = locked.getRewardPoints() != null ? locked.getRewardPoints() : 0L;
            growthService.grantRedeemFreeQuota(userId, pts, redemption.getId(), desc);
        }

        locked.setRedemptionCount((locked.getRedemptionCount() == null ? 0 : locked.getRedemptionCount()) + 1);
        redeemCodeMapper.updateById(locked);

        if ("platform_currency".equals(locked.getRewardType())) {
            return RedeemResultDTO.builder()
                    .rewardType(locked.getRewardType())
                    .rewardCurrency(locked.getRewardCurrency())
                    .message("兑换成功")
                    .build();
        }
        return RedeemResultDTO.builder()
                .rewardType("free_quota")
                .rewardPoints(locked.getRewardPoints())
                .message("兑换成功")
                .build();
    }

    private static String normalizeCode(String code) {
        return code == null ? "" : code.trim().toUpperCase();
    }

    private static void validateReward(String rewardType, Long rewardPoints, BigDecimal rewardCurrency) {
        if ("platform_currency".equals(rewardType)) {
            if (rewardCurrency == null || rewardCurrency.compareTo(BigDecimal.ZERO) <= 0) {
                throw new BusinessException("平台币类型须填写大于 0 的 rewardCurrency");
            }
        } else if ("free_quota".equals(rewardType)) {
            if (rewardPoints == null || rewardPoints <= 0) {
                throw new BusinessException("创作点类型须填写大于 0 的 rewardPoints");
            }
        } else {
            throw new BusinessException("不支持的 rewardType");
        }
    }

    private void applyWrite(RedeemCodeDTO dto, RedeemCode entity) {
        entity.setBatchLabel(dto.getBatchLabel());
        entity.setRewardType(dto.getRewardType());
        entity.setRewardPoints(dto.getRewardPoints());
        entity.setRewardCurrency(dto.getRewardCurrency());
        entity.setMaxTotalRedemptions(dto.getMaxTotalRedemptions());
        entity.setMaxPerUser(dto.getMaxPerUser());
        entity.setValidStart(dto.getValidStart());
        entity.setValidEnd(dto.getValidEnd());
        entity.setEnabled(dto.getEnabled());
        entity.setCampaignId(dto.getCampaignId());
    }

    private RedeemCodeDTO toDto(RedeemCode entity) {
        RedeemCodeDTO dto = new RedeemCodeDTO();
        dto.setId(entity.getId());
        dto.setCode(entity.getCode());
        dto.setBatchLabel(entity.getBatchLabel());
        dto.setRewardType(entity.getRewardType());
        dto.setRewardPoints(entity.getRewardPoints());
        dto.setRewardCurrency(entity.getRewardCurrency());
        dto.setMaxTotalRedemptions(entity.getMaxTotalRedemptions());
        dto.setRedemptionCount(entity.getRedemptionCount());
        dto.setMaxPerUser(entity.getMaxPerUser());
        dto.setValidStart(entity.getValidStart());
        dto.setValidEnd(entity.getValidEnd());
        dto.setEnabled(entity.getEnabled());
        dto.setCampaignId(entity.getCampaignId());
        dto.setCreateTime(entity.getCreateTime());
        dto.setUpdateTime(entity.getUpdateTime());
        return dto;
    }

    private static String randomSuffix(SecureRandom rnd, int length) {
        int n = Math.max(length, 4);
        StringBuilder sb = new StringBuilder(n);
        for (int i = 0; i < n; i++) {
            sb.append(CODE_ALPHABET.charAt(rnd.nextInt(CODE_ALPHABET.length())));
        }
        return sb.toString();
    }
}
