package com.starrynight.starrynight.system.prompt.service;

import com.baomidou.mybatisplus.core.conditions.query.LambdaQueryWrapper;
import com.baomidou.mybatisplus.core.metadata.IPage;
import com.baomidou.mybatisplus.extension.plugins.pagination.Page;
import com.fasterxml.jackson.core.JsonProcessingException;
import com.fasterxml.jackson.databind.ObjectMapper;
import com.starrynight.starrynight.framework.common.exception.BusinessException;
import com.starrynight.starrynight.framework.common.exception.ResourceNotFoundException;
import com.starrynight.starrynight.framework.common.util.ThreadLocalUtil;
import com.starrynight.starrynight.framework.common.vo.PageVO;
import com.starrynight.starrynight.system.prompt.dto.PromptTemplateDTO;
import com.starrynight.starrynight.system.prompt.entity.PromptTemplate;
import com.starrynight.starrynight.system.prompt.repository.PromptTemplateRepository;
import org.springframework.beans.BeanUtils;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.cache.annotation.CacheEvict;
import org.springframework.cache.annotation.Cacheable;
import org.springframework.stereotype.Service;
import org.springframework.transaction.annotation.Transactional;

import java.util.List;
import java.util.stream.Collectors;

@Service
public class PromptTemplateService {

    @Autowired
    private PromptTemplateRepository promptTemplateRepository;

    @Autowired
    private ObjectMapper objectMapper;

    public PageVO<PromptTemplateDTO> list(String keyword, String category, int page, int size) {
        Long userId = ThreadLocalUtil.getUserId();
        LambdaQueryWrapper<PromptTemplate> wrapper = new LambdaQueryWrapper<>();
        wrapper.and(w -> w.eq(PromptTemplate::getUserId, userId).or().eq(PromptTemplate::getIsBuiltin, 1));
        if (keyword != null && !keyword.isBlank()) {
            wrapper.like(PromptTemplate::getName, keyword);
        }
        if (category != null && !category.isBlank()) {
            wrapper.eq(PromptTemplate::getCategory, category);
        }
        wrapper.orderByDesc(PromptTemplate::getIsBuiltin).orderByDesc(PromptTemplate::getCreateTime);

        IPage<PromptTemplate> pageResult = promptTemplateRepository.selectPage(new Page<>(page, size), wrapper);
        List<PromptTemplateDTO> records = pageResult.getRecords().stream().map(this::toDTO).collect(Collectors.toList());
        return PageVO.of(pageResult.getTotal(), records, (long) page, (long) size);
    }

    @Cacheable(value = "promptTemplate", key = "#id", unless = "#result == null")
    public PromptTemplateDTO getById(Long id) {
        PromptTemplate entity = promptTemplateRepository.selectById(id);
        if (entity == null) {
            throw new ResourceNotFoundException("模板不存在");
        }
        return toDTO(entity);
    }

    @Transactional
    @CacheEvict(value = "promptTemplate", key = "#result.id")
    public PromptTemplateDTO create(PromptTemplateDTO dto) {
        Long userId = ThreadLocalUtil.getUserId();
        PromptTemplate entity = new PromptTemplate();
        BeanUtils.copyProperties(dto, entity);
        entity.setUserId(userId);
        entity.setVariables(toJsonString(dto.getVariables()));
        entity.setIsBuiltin(0);
        entity.setVersion(1);
        promptTemplateRepository.insert(entity);
        return toDTO(entity);
    }

    @Transactional
    @CacheEvict(value = "promptTemplate", key = "#id")
    public PromptTemplateDTO update(Long id, PromptTemplateDTO dto) {
        PromptTemplate entity = promptTemplateRepository.selectById(id);
        if (entity == null) {
            throw new ResourceNotFoundException("模板不存在");
        }
        if (entity.getIsBuiltin() != null && entity.getIsBuiltin() == 1) {
            throw new BusinessException("内置模板不可修改");
        }
        if (dto.getName() != null) entity.setName(dto.getName());
        if (dto.getCategory() != null) entity.setCategory(dto.getCategory());
        if (dto.getDescription() != null) entity.setDescription(dto.getDescription());
        if (dto.getPromptTemplate() != null) entity.setPromptTemplate(dto.getPromptTemplate());
        if (dto.getVariables() != null) entity.setVariables(toJsonString(dto.getVariables()));
        if (dto.getOutputFormat() != null) entity.setOutputFormat(dto.getOutputFormat());
        entity.setVersion(entity.getVersion() + 1);
        promptTemplateRepository.updateById(entity);
        return toDTO(entity);
    }

    @Transactional
    @CacheEvict(value = "promptTemplate", key = "#id")
    public void delete(Long id) {
        PromptTemplate entity = promptTemplateRepository.selectById(id);
        if (entity == null) {
            throw new ResourceNotFoundException("模板不存在");
        }
        if (entity.getIsBuiltin() != null && entity.getIsBuiltin() == 1) {
            throw new BusinessException("内置模板不可删除");
        }
        if (promptTemplateRepository.deleteById(id) <= 0) {
            throw new BusinessException("删除失败");
        }
    }

    public String applyPrompt(Long id, java.util.Map<String, String> variables) {
        PromptTemplate entity = promptTemplateRepository.selectById(id);
        if (entity == null) {
            throw new ResourceNotFoundException("模板不存在");
        }

        String template = entity.getPromptTemplate();
        if (template == null || template.isBlank()) {
            return "";
        }

        String result = template;
        for (java.util.Map.Entry<String, String> entry : variables.entrySet()) {
            String placeholder = "{{" + entry.getKey() + "}}";
            String value = entry.getValue() != null ? entry.getValue() : "";
            result = result.replace(placeholder, value);
        }

        entity.setVersion(entity.getVersion() == null ? 1 : entity.getVersion() + 1);
        promptTemplateRepository.updateById(entity);

        return result;
    }

    public List<String> listCategories() {
        return promptTemplateRepository.selectList(
                new LambdaQueryWrapper<PromptTemplate>()
                        .select(PromptTemplate::getCategory)
                        .groupBy(PromptTemplate::getCategory)
        ).stream().map(PromptTemplate::getCategory).collect(Collectors.toList());
    }

    private PromptTemplateDTO toDTO(PromptTemplate entity) {
        PromptTemplateDTO dto = new PromptTemplateDTO();
        BeanUtils.copyProperties(entity, dto);
        dto.setIsBuiltin(entity.getIsBuiltin() != null && entity.getIsBuiltin() == 1);
        dto.setVariables(parseJson(entity.getVariables()));
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