package com.starrynight.starrynight.system.campaign.service;

import com.baomidou.mybatisplus.core.conditions.query.LambdaQueryWrapper;
import com.starrynight.starrynight.framework.common.exception.ResourceNotFoundException;
import com.starrynight.starrynight.system.campaign.dto.OpsCampaignDTO;
import com.starrynight.starrynight.system.campaign.entity.OpsCampaign;
import com.starrynight.starrynight.system.campaign.mapper.OpsCampaignMapper;
import lombok.RequiredArgsConstructor;
import org.springframework.stereotype.Service;

import java.util.List;
import java.util.stream.Collectors;

@Service
@RequiredArgsConstructor
public class OpsCampaignService {

    private final OpsCampaignMapper opsCampaignMapper;

    public List<OpsCampaignDTO> listAll() {
        List<OpsCampaign> list = opsCampaignMapper.selectList(
                new LambdaQueryWrapper<OpsCampaign>()
                        .orderByAsc(OpsCampaign::getSortOrder)
                        .orderByDesc(OpsCampaign::getId));
        return list.stream().map(this::toDto).collect(Collectors.toList());
    }

    /** 用户端：已发布且在时间窗内 */
    public List<OpsCampaignDTO> listPublishedVisible() {
        java.time.LocalDateTime now = java.time.LocalDateTime.now();
        List<OpsCampaign> list = opsCampaignMapper.selectList(
                new LambdaQueryWrapper<OpsCampaign>()
                        .eq(OpsCampaign::getStatus, 1)
                        .and(w -> w.isNull(OpsCampaign::getStartTime).or().le(OpsCampaign::getStartTime, now))
                        .and(w -> w.isNull(OpsCampaign::getEndTime).or().ge(OpsCampaign::getEndTime, now))
                        .orderByAsc(OpsCampaign::getSortOrder)
                        .orderByDesc(OpsCampaign::getId));
        return list.stream().map(this::toDto).collect(Collectors.toList());
    }

    public OpsCampaignDTO getById(Long id) {
        OpsCampaign entity = opsCampaignMapper.selectById(id);
        if (entity == null) {
            throw new ResourceNotFoundException("活动不存在");
        }
        return toDto(entity);
    }

    public OpsCampaignDTO create(OpsCampaignDTO dto) {
        OpsCampaign entity = new OpsCampaign();
        apply(dto, entity);
        if (entity.getSortOrder() == null) {
            entity.setSortOrder(0);
        }
        opsCampaignMapper.insert(entity);
        return toDto(entity);
    }

    public OpsCampaignDTO update(Long id, OpsCampaignDTO dto) {
        OpsCampaign entity = opsCampaignMapper.selectById(id);
        if (entity == null) {
            throw new ResourceNotFoundException("活动不存在");
        }
        apply(dto, entity);
        opsCampaignMapper.updateById(entity);
        return toDto(entity);
    }

    public void delete(Long id) {
        OpsCampaign entity = opsCampaignMapper.selectById(id);
        if (entity == null) {
            throw new ResourceNotFoundException("活动不存在");
        }
        opsCampaignMapper.deleteById(id);
    }

    private void apply(OpsCampaignDTO dto, OpsCampaign entity) {
        entity.setTitle(dto.getTitle());
        entity.setSummary(dto.getSummary());
        entity.setLinkUrl(dto.getLinkUrl());
        entity.setCoverUrl(dto.getCoverUrl());
        entity.setStatus(dto.getStatus());
        entity.setStartTime(dto.getStartTime());
        entity.setEndTime(dto.getEndTime());
        if (dto.getSortOrder() != null) {
            entity.setSortOrder(dto.getSortOrder());
        }
    }

    private OpsCampaignDTO toDto(OpsCampaign entity) {
        OpsCampaignDTO dto = new OpsCampaignDTO();
        dto.setId(entity.getId());
        dto.setTitle(entity.getTitle());
        dto.setSummary(entity.getSummary());
        dto.setLinkUrl(entity.getLinkUrl());
        dto.setCoverUrl(entity.getCoverUrl());
        dto.setStatus(entity.getStatus());
        dto.setStartTime(entity.getStartTime());
        dto.setEndTime(entity.getEndTime());
        dto.setSortOrder(entity.getSortOrder());
        dto.setCreateTime(entity.getCreateTime());
        dto.setUpdateTime(entity.getUpdateTime());
        return dto;
    }
}
