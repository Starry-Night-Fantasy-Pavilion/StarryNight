package com.starrynight.starrynight.system.material.service;

import com.baomidou.mybatisplus.core.conditions.query.LambdaQueryWrapper;
import com.baomidou.mybatisplus.core.metadata.IPage;
import com.baomidou.mybatisplus.extension.plugins.pagination.Page;
import com.fasterxml.jackson.core.JsonProcessingException;
import com.fasterxml.jackson.databind.ObjectMapper;
import com.starrynight.starrynight.framework.common.exception.BusinessException;
import com.starrynight.starrynight.framework.common.exception.ResourceNotFoundException;
import com.starrynight.starrynight.framework.common.util.ThreadLocalUtil;
import com.starrynight.starrynight.framework.common.vo.PageVO;
import com.starrynight.starrynight.system.material.dto.MaterialItemDTO;
import com.starrynight.starrynight.system.material.entity.MaterialItem;
import com.starrynight.starrynight.system.material.repository.MaterialItemRepository;
import org.springframework.beans.BeanUtils;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.stereotype.Service;
import org.springframework.transaction.annotation.Transactional;

import java.time.LocalDateTime;
import java.util.List;
import java.util.stream.Collectors;

@Service
public class MaterialItemService {

    @Autowired
    private MaterialItemRepository materialItemRepository;

    @Autowired
    private ObjectMapper objectMapper;

    public PageVO<MaterialItemDTO> list(String keyword, String type, int page, int size) {
        Long userId = ThreadLocalUtil.getUserId();
        LambdaQueryWrapper<MaterialItem> wrapper = new LambdaQueryWrapper<>();
        wrapper.eq(MaterialItem::getUserId, userId);
        if (keyword != null && !keyword.isBlank()) {
            wrapper.like(MaterialItem::getTitle, keyword);
        }
        if (type != null && !type.isBlank()) {
            wrapper.eq(MaterialItem::getType, type);
        }
        wrapper.orderByDesc(MaterialItem::getUpdateTime);

        IPage<MaterialItem> pageResult = materialItemRepository.selectPage(new Page<>(page, size), wrapper);
        List<MaterialItemDTO> records = pageResult.getRecords().stream().map(this::toDTO).collect(Collectors.toList());
        return PageVO.of(pageResult.getTotal(), records, (long) page, (long) size);
    }

    public MaterialItemDTO getById(Long id) {
        MaterialItem entity = materialItemRepository.selectById(id);
        if (entity == null) {
            throw new ResourceNotFoundException("素材不存在");
        }
        return toDTO(entity);
    }

    @Transactional
    public MaterialItemDTO create(MaterialItemDTO dto) {
        Long userId = ThreadLocalUtil.getUserId();
        MaterialItem entity = new MaterialItem();
        BeanUtils.copyProperties(dto, entity);
        entity.setUserId(userId);
        entity.setContent(toJsonString(dto.getContent()));
        entity.setUsageCount(0);
        materialItemRepository.insert(entity);
        return toDTO(entity);
    }

    @Transactional
    public MaterialItemDTO update(Long id, MaterialItemDTO dto) {
        MaterialItem entity = materialItemRepository.selectById(id);
        if (entity == null) {
            throw new ResourceNotFoundException("素材不存在");
        }
        if (dto.getTitle() != null) entity.setTitle(dto.getTitle());
        if (dto.getType() != null) entity.setType(dto.getType());
        if (dto.getDescription() != null) entity.setDescription(dto.getDescription());
        if (dto.getContent() != null) entity.setContent(toJsonString(dto.getContent()));
        if (dto.getTags() != null) entity.setTags(dto.getTags());
        if (dto.getNovelId() != null) entity.setNovelId(dto.getNovelId());
        materialItemRepository.updateById(entity);
        return toDTO(entity);
    }

    @Transactional
    public void delete(Long id) {
        MaterialItem entity = materialItemRepository.selectById(id);
        if (entity == null) {
            throw new ResourceNotFoundException("素材不存在");
        }
        if (materialItemRepository.deleteById(id) <= 0) {
            throw new BusinessException("删除失败");
        }
    }

    /**
     * 记录素材使用次数
     */
    @Transactional
    public void recordUsage(Long id) {
        MaterialItem entity = materialItemRepository.selectById(id);
        if (entity == null) {
            throw new ResourceNotFoundException("素材不存在");
        }
        entity.setUsageCount(entity.getUsageCount() == null ? 1 : entity.getUsageCount() + 1);
        entity.setLastUsedAt(LocalDateTime.now());
        materialItemRepository.updateById(entity);
    }

    public List<MaterialItemDTO> recommendMaterials(Long novelId, String context, String type, int limit) {
        Long userId = ThreadLocalUtil.getUserId();
        LambdaQueryWrapper<MaterialItem> wrapper = new LambdaQueryWrapper<>();
        wrapper.eq(MaterialItem::getUserId, userId);

        if (novelId != null) {
            wrapper.eq(MaterialItem::getNovelId, novelId);
        }

        if (type != null && !type.isBlank()) {
            wrapper.eq(MaterialItem::getType, type);
        }

        wrapper.orderByDesc(MaterialItem::getUsageCount)
               .orderByDesc(MaterialItem::getLastUsedAt)
               .orderByDesc(MaterialItem::getUpdateTime);

        IPage<MaterialItem> pageResult = materialItemRepository.selectPage(new Page<>(1, limit), wrapper);
        return pageResult.getRecords().stream().map(this::toDTO).collect(Collectors.toList());
    }

    private MaterialItemDTO toDTO(MaterialItem entity) {
        MaterialItemDTO dto = new MaterialItemDTO();
        BeanUtils.copyProperties(entity, dto);
        dto.setContent(parseJson(entity.getContent()));
        return dto;
    }

    private String toJsonString(Object obj) {
        if (obj == null) return null;
        try {
            return objectMapper.writeValueAsString(obj);
        } catch (JsonProcessingException e) {
            return obj.toString();
        }
    }

    private Object parseJson(String json) {
        if (json == null || json.isBlank()) return null;
        try {
            return objectMapper.readTree(json);
        } catch (JsonProcessingException e) {
            return json;
        }
    }
}