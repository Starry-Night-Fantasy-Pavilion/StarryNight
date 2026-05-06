package com.starrynight.starrynight.system.ai.service;

import com.baomidou.mybatisplus.core.conditions.query.LambdaQueryWrapper;
import com.fasterxml.jackson.core.JsonProcessingException;
import com.fasterxml.jackson.databind.ObjectMapper;
import com.starrynight.starrynight.framework.common.exception.BusinessException;
import com.starrynight.starrynight.framework.common.exception.ResourceNotFoundException;
import com.starrynight.starrynight.system.ai.AiModelTypeCodes;
import com.starrynight.starrynight.system.ai.dto.AiGenerationParamsDTO;
import com.starrynight.starrynight.system.ai.dto.AiModelDTO;
import com.starrynight.starrynight.system.ai.dto.AiSensitiveWordDTO;
import com.starrynight.starrynight.system.ai.dto.AiTemplateDTO;
import com.starrynight.starrynight.system.ai.entity.AiModel;
import com.starrynight.starrynight.system.ai.entity.AiSensitiveWord;
import com.starrynight.starrynight.system.ai.entity.AiTemplate;
import com.starrynight.starrynight.system.ai.repository.AiModelRepository;
import com.starrynight.starrynight.system.ai.repository.AiSensitiveWordRepository;
import com.starrynight.starrynight.system.ai.repository.AiTemplateRepository;
import com.starrynight.starrynight.system.billing.entity.BillingChannel;
import com.starrynight.starrynight.system.billing.mapper.BillingChannelMapper;
import com.starrynight.starrynight.system.system.service.SystemConfigService;
import org.slf4j.Logger;
import org.slf4j.LoggerFactory;
import org.springframework.beans.BeanUtils;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.stereotype.Service;
import org.springframework.transaction.annotation.Transactional;

import java.util.List;
import java.util.Map;
import java.util.Objects;
import java.util.Set;
import java.util.stream.Collectors;

@Service
public class AdminAiConfigService {

    private static final Logger log = LoggerFactory.getLogger(AdminAiConfigService.class);

    private static final String CONFIG_KEY_AI_GENERATION_PARAMS = "ai.generation.params";

    @Autowired
    private AiModelRepository aiModelRepository;
    @Autowired
    private AiSensitiveWordRepository aiSensitiveWordRepository;
    @Autowired
    private AiTemplateRepository aiTemplateRepository;
    @Autowired
    private BillingChannelMapper billingChannelMapper;
    @Autowired
    private SystemConfigService systemConfigService;
    @Autowired
    private ObjectMapper objectMapper;

    public List<AiModelDTO> listModels(Long billingChannelId) {
        LambdaQueryWrapper<AiModel> wrapper = new LambdaQueryWrapper<>();
        if (billingChannelId != null) {
            wrapper.eq(AiModel::getBillingChannelId, billingChannelId);
        }
        wrapper.orderByAsc(AiModel::getSortOrder).orderByDesc(AiModel::getCreateTime);
        List<AiModelDTO> list = aiModelRepository.selectList(wrapper).stream().map(this::toModelDTO).collect(Collectors.toList());
        enrichChannelFields(list);
        return list;
    }

    /**
     * 用户侧可用模型列表：仅返回 enabled=1 的模型。
     */
    public List<AiModelDTO> listEnabledModels(Long billingChannelId) {
        LambdaQueryWrapper<AiModel> wrapper = new LambdaQueryWrapper<>();
        wrapper.eq(AiModel::getEnabled, 1);
        if (billingChannelId != null) {
            wrapper.eq(AiModel::getBillingChannelId, billingChannelId);
        }
        wrapper.orderByAsc(AiModel::getSortOrder).orderByDesc(AiModel::getCreateTime);
        List<AiModelDTO> list = aiModelRepository.selectList(wrapper).stream().map(this::toModelDTO).collect(Collectors.toList());
        enrichChannelFields(list);
        return list;
    }

    @Transactional
    public AiModelDTO createModel(AiModelDTO dto) {
        BillingChannel channel = requireChannel(dto.getBillingChannelId());
        AiModel exists = aiModelRepository.selectOne(
                new LambdaQueryWrapper<AiModel>().eq(AiModel::getModelCode, dto.getModelCode())
        );
        if (exists != null) {
            throw new BusinessException("Model code already exists");
        }
        AiModel model = new AiModel();
        BeanUtils.copyProperties(dto, model, "modelType", "channelCode", "channelName");
        model.setModelType(AiModelTypeCodes.DEFAULT);
        model.setProvider(syncProviderFromChannel(dto.getProvider(), channel));
        if (model.getSortOrder() == null) {
            model.setSortOrder(0);
        }
        aiModelRepository.insert(model);
        return toModelDtoWithChannel(toModelDTO(model));
    }

    @Transactional
    public AiModelDTO updateModel(Long id, AiModelDTO dto) {
        AiModel model = aiModelRepository.selectById(id);
        if (model == null) {
            throw new ResourceNotFoundException("AI model not found");
        }
        BillingChannel channel = requireChannel(dto.getBillingChannelId());
        if (!model.getModelCode().equals(dto.getModelCode())) {
            AiModel exists = aiModelRepository.selectOne(
                    new LambdaQueryWrapper<AiModel>().eq(AiModel::getModelCode, dto.getModelCode())
            );
            if (exists != null) {
                throw new BusinessException("Model code already exists");
            }
        }
        BeanUtils.copyProperties(dto, model, "modelType", "channelCode", "channelName");
        model.setId(id);
        model.setModelType(AiModelTypeCodes.DEFAULT);
        model.setProvider(syncProviderFromChannel(dto.getProvider(), channel));
        aiModelRepository.updateById(model);
        return toModelDtoWithChannel(toModelDTO(model));
    }

    @Transactional
    public void deleteModel(Long id) {
        AiModel model = aiModelRepository.selectById(id);
        if (model == null) {
            throw new ResourceNotFoundException("AI model not found");
        }
        aiModelRepository.deleteById(id);
    }

    public List<AiSensitiveWordDTO> listSensitiveWords(Integer level) {
        LambdaQueryWrapper<AiSensitiveWord> wrapper = new LambdaQueryWrapper<>();
        if (level != null) {
            wrapper.eq(AiSensitiveWord::getLevel, level);
        }
        wrapper.orderByDesc(AiSensitiveWord::getCreateTime);
        return aiSensitiveWordRepository.selectList(wrapper).stream().map(this::toWordDTO).collect(Collectors.toList());
    }

    @Transactional
    public AiSensitiveWordDTO createSensitiveWord(AiSensitiveWordDTO dto) {
        AiSensitiveWord exists = aiSensitiveWordRepository.selectOne(
                new LambdaQueryWrapper<AiSensitiveWord>().eq(AiSensitiveWord::getWord, dto.getWord())
        );
        if (exists != null) {
            throw new BusinessException("Sensitive word already exists");
        }
        AiSensitiveWord entity = new AiSensitiveWord();
        BeanUtils.copyProperties(dto, entity);
        aiSensitiveWordRepository.insert(entity);
        return toWordDTO(entity);
    }

    @Transactional
    public AiSensitiveWordDTO updateSensitiveWord(Long id, AiSensitiveWordDTO dto) {
        AiSensitiveWord entity = aiSensitiveWordRepository.selectById(id);
        if (entity == null) {
            throw new ResourceNotFoundException("Sensitive word not found");
        }
        if (!entity.getWord().equals(dto.getWord())) {
            AiSensitiveWord exists = aiSensitiveWordRepository.selectOne(
                    new LambdaQueryWrapper<AiSensitiveWord>().eq(AiSensitiveWord::getWord, dto.getWord())
            );
            if (exists != null) {
                throw new BusinessException("Sensitive word already exists");
            }
        }
        BeanUtils.copyProperties(dto, entity);
        entity.setId(id);
        aiSensitiveWordRepository.updateById(entity);
        return toWordDTO(entity);
    }

    @Transactional
    public void deleteSensitiveWord(Long id) {
        AiSensitiveWord entity = aiSensitiveWordRepository.selectById(id);
        if (entity == null) {
            throw new ResourceNotFoundException("Sensitive word not found");
        }
        aiSensitiveWordRepository.deleteById(id);
    }

    public List<AiTemplateDTO> listTemplates(String type) {
        LambdaQueryWrapper<AiTemplate> wrapper = new LambdaQueryWrapper<>();
        if (type != null && !type.isBlank()) {
            wrapper.eq(AiTemplate::getType, type.trim());
        }
        wrapper.orderByDesc(AiTemplate::getCreateTime);
        return aiTemplateRepository.selectList(wrapper).stream().map(this::toTemplateDTO).collect(Collectors.toList());
    }

    @Transactional
    public AiTemplateDTO createTemplate(AiTemplateDTO dto) {
        AiTemplate entity = new AiTemplate();
        BeanUtils.copyProperties(dto, entity, "id", "usageCount");
        entity.setUsageCount(0);
        if (entity.getEnabled() == null) {
            entity.setEnabled(1);
        }
        aiTemplateRepository.insert(entity);
        return toTemplateDTO(entity);
    }

    @Transactional
    public AiTemplateDTO updateTemplate(Long id, AiTemplateDTO dto) {
        AiTemplate entity = aiTemplateRepository.selectById(id);
        if (entity == null) {
            throw new ResourceNotFoundException("AI template not found");
        }
        BeanUtils.copyProperties(dto, entity, "id", "usageCount");
        entity.setId(id);
        aiTemplateRepository.updateById(entity);
        return toTemplateDTO(entity);
    }

    @Transactional
    public void deleteTemplate(Long id) {
        AiTemplate entity = aiTemplateRepository.selectById(id);
        if (entity == null) {
            throw new ResourceNotFoundException("AI template not found");
        }
        aiTemplateRepository.deleteById(id);
    }

    public AiGenerationParamsDTO getGenerationParams() {
        String raw = systemConfigService.getValue(CONFIG_KEY_AI_GENERATION_PARAMS);
        if (raw == null || raw.isBlank()) {
            return defaultGenerationParams();
        }
        try {
            AiGenerationParamsDTO parsed = objectMapper.readValue(raw, AiGenerationParamsDTO.class);
            return mergeGenerationParams(defaultGenerationParams(), parsed);
        } catch (JsonProcessingException e) {
            log.warn("invalid ai.generation.params json, using defaults: {}", e.toString());
            return defaultGenerationParams();
        }
    }

    @Transactional
    public void saveGenerationParams(AiGenerationParamsDTO dto) {
        AiGenerationParamsDTO merged = mergeGenerationParams(defaultGenerationParams(), dto);
        String json;
        try {
            json = objectMapper.writeValueAsString(merged);
        } catch (JsonProcessingException e) {
            throw new BusinessException("无法序列化生成参数");
        }
        systemConfigService.upsertConfigValue(
                CONFIG_KEY_AI_GENERATION_PARAMS,
                json,
                "AI 生成参数",
                "ai",
                "运营端 AI 生成默认参数（JSON）");
    }

    private static AiGenerationParamsDTO defaultGenerationParams() {
        AiGenerationParamsDTO d = new AiGenerationParamsDTO();
        d.setTemperature(0.7);
        d.setMaxTokens(2000);
        d.setTopP(0.9);
        d.setFrequencyPenalty(0.0);
        d.setPresencePenalty(0.0);
        d.setOutlineTemperature(0.6);
        d.setContentTemperature(0.75);
        d.setChatTemperature(0.8);
        d.setEnableStreaming(true);
        d.setStreamInterval(100);
        return d;
    }

    private static AiGenerationParamsDTO mergeGenerationParams(AiGenerationParamsDTO base, AiGenerationParamsDTO over) {
        if (over.getTemperature() != null) {
            base.setTemperature(over.getTemperature());
        }
        if (over.getMaxTokens() != null) {
            base.setMaxTokens(over.getMaxTokens());
        }
        if (over.getTopP() != null) {
            base.setTopP(over.getTopP());
        }
        if (over.getFrequencyPenalty() != null) {
            base.setFrequencyPenalty(over.getFrequencyPenalty());
        }
        if (over.getPresencePenalty() != null) {
            base.setPresencePenalty(over.getPresencePenalty());
        }
        if (over.getOutlineTemperature() != null) {
            base.setOutlineTemperature(over.getOutlineTemperature());
        }
        if (over.getContentTemperature() != null) {
            base.setContentTemperature(over.getContentTemperature());
        }
        if (over.getChatTemperature() != null) {
            base.setChatTemperature(over.getChatTemperature());
        }
        if (over.getEnableStreaming() != null) {
            base.setEnableStreaming(over.getEnableStreaming());
        }
        if (over.getStreamInterval() != null) {
            base.setStreamInterval(over.getStreamInterval());
        }
        return base;
    }

    private AiTemplateDTO toTemplateDTO(AiTemplate entity) {
        AiTemplateDTO dto = new AiTemplateDTO();
        BeanUtils.copyProperties(entity, dto);
        return dto;
    }

    private AiModelDTO toModelDTO(AiModel entity) {
        AiModelDTO dto = new AiModelDTO();
        BeanUtils.copyProperties(entity, dto);
        if (dto.getModelType() == null || dto.getModelType().isBlank()) {
            dto.setModelType(AiModelTypeCodes.DEFAULT);
        }
        return dto;
    }

    private BillingChannel requireChannel(Long billingChannelId) {
        if (billingChannelId == null) {
            throw new BusinessException("请选择计费渠道");
        }
        BillingChannel channel = billingChannelMapper.selectById(billingChannelId);
        if (channel == null || (channel.getDeleted() != null && channel.getDeleted() != 0)) {
            throw new BusinessException("计费渠道不存在或已删除");
        }
        return channel;
    }

    private static String syncProviderFromChannel(String requestedProvider, BillingChannel channel) {
        if (requestedProvider != null && !requestedProvider.isBlank()) {
            return requestedProvider.trim();
        }
        if (channel.getChannelName() != null && !channel.getChannelName().isBlank()) {
            return channel.getChannelName().trim();
        }
        return channel.getChannelCode() != null ? channel.getChannelCode().trim() : "";
    }

    private AiModelDTO toModelDtoWithChannel(AiModelDTO dto) {
        enrichChannelFields(List.of(dto));
        return dto;
    }

    private void enrichChannelFields(List<AiModelDTO> list) {
        if (list == null || list.isEmpty()) {
            return;
        }
        Set<Long> ids = list.stream()
                .map(AiModelDTO::getBillingChannelId)
                .filter(Objects::nonNull)
                .collect(Collectors.toSet());
        if (ids.isEmpty()) {
            return;
        }
        List<BillingChannel> channels = billingChannelMapper.selectList(
                new LambdaQueryWrapper<BillingChannel>().in(BillingChannel::getId, ids));
        Map<Long, BillingChannel> map = channels.stream().collect(Collectors.toMap(BillingChannel::getId, c -> c, (a, b) -> a));
        for (AiModelDTO dto : list) {
            if (dto.getBillingChannelId() == null) {
                continue;
            }
            BillingChannel c = map.get(dto.getBillingChannelId());
            if (c != null) {
                dto.setChannelCode(c.getChannelCode());
                dto.setChannelName(c.getChannelName());
            }
        }
    }

    private AiSensitiveWordDTO toWordDTO(AiSensitiveWord entity) {
        AiSensitiveWordDTO dto = new AiSensitiveWordDTO();
        BeanUtils.copyProperties(entity, dto);
        return dto;
    }
}
