package com.starrynight.starrynight.system.character.service;

import com.baomidou.mybatisplus.core.conditions.query.LambdaQueryWrapper;
import com.baomidou.mybatisplus.core.metadata.IPage;
import com.baomidou.mybatisplus.extension.plugins.pagination.Page;
import com.fasterxml.jackson.core.JsonProcessingException;
import com.fasterxml.jackson.databind.JsonNode;
import com.fasterxml.jackson.databind.ObjectMapper;
import com.starrynight.starrynight.framework.common.exception.BusinessException;
import com.starrynight.starrynight.framework.common.exception.ResourceNotFoundException;
import com.starrynight.starrynight.framework.common.util.ThreadLocalUtil;
import com.starrynight.starrynight.framework.common.vo.PageVO;
import com.starrynight.starrynight.system.character.dto.NovelCharacterDTO;
import com.starrynight.starrynight.system.character.entity.NovelCharacter;
import com.starrynight.starrynight.system.character.repository.NovelCharacterRepository;
import com.starrynight.starrynight.system.auth.realname.RealnameVerificationService;
import org.springframework.beans.BeanUtils;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.stereotype.Service;
import org.springframework.transaction.annotation.Transactional;

import java.util.*;
import java.util.stream.Collectors;

@Service
public class NovelCharacterService {

    @Autowired
    private NovelCharacterRepository novelCharacterRepository;

    @Autowired
    private ObjectMapper objectMapper;

    @Autowired
    private RealnameVerificationService realnameVerificationService;

    public PageVO<NovelCharacterDTO> list(String keyword, Long novelId, int page, int size) {
        Long userId = ThreadLocalUtil.getUserId();
        LambdaQueryWrapper<NovelCharacter> wrapper = new LambdaQueryWrapper<>();
        wrapper.eq(NovelCharacter::getUserId, userId);
        if (keyword != null && !keyword.isBlank()) {
            wrapper.like(NovelCharacter::getName, keyword);
        }
        if (novelId != null) {
            wrapper.eq(NovelCharacter::getNovelId, novelId);
        }
        wrapper.orderByAsc(NovelCharacter::getSortOrder).orderByDesc(NovelCharacter::getCreateTime);

        IPage<NovelCharacter> pageResult = novelCharacterRepository.selectPage(new Page<>(page, size), wrapper);
        List<NovelCharacterDTO> records = pageResult.getRecords().stream().map(this::toDTO).collect(Collectors.toList());
        return PageVO.of(pageResult.getTotal(), records, (long) page, (long) size);
    }

    public NovelCharacterDTO getById(Long id) {
        NovelCharacter entity = novelCharacterRepository.selectById(id);
        if (entity == null) {
            throw new ResourceNotFoundException("角色不存在");
        }
        return toDTO(entity);
    }

    @Transactional
    public NovelCharacterDTO create(NovelCharacterDTO dto) {
        Long userId = ThreadLocalUtil.getUserId();
        NovelCharacter entity = new NovelCharacter();
        BeanUtils.copyProperties(dto, entity);
        entity.setUserId(userId);
        entity.setPersonality(toJsonString(dto.getPersonality()));
        entity.setAbilities(toJsonString(dto.getAbilities()));
        entity.setRelationships(toJsonString(dto.getRelationships()));
        entity.setGrowthArc(toJsonString(dto.getGrowthArc()));
        novelCharacterRepository.insert(entity);
        return toDTO(entity);
    }

    @Transactional
    public NovelCharacterDTO update(Long id, NovelCharacterDTO dto) {
        NovelCharacter entity = novelCharacterRepository.selectById(id);
        if (entity == null) {
            throw new ResourceNotFoundException("角色不存在");
        }
        if (dto.getName() != null) entity.setName(dto.getName());
        if (dto.getIdentity() != null) entity.setIdentity(dto.getIdentity());
        if (dto.getGender() != null) entity.setGender(dto.getGender());
        if (dto.getAge() != null) entity.setAge(dto.getAge());
        if (dto.getAppearance() != null) entity.setAppearance(dto.getAppearance());
        if (dto.getBackground() != null) entity.setBackground(dto.getBackground());
        if (dto.getMotivation() != null) entity.setMotivation(dto.getMotivation());
        if (dto.getPersonality() != null) entity.setPersonality(toJsonString(dto.getPersonality()));
        if (dto.getAbilities() != null) entity.setAbilities(toJsonString(dto.getAbilities()));
        if (dto.getRelationships() != null) entity.setRelationships(toJsonString(dto.getRelationships()));
        if (dto.getGrowthArc() != null) entity.setGrowthArc(toJsonString(dto.getGrowthArc()));
        novelCharacterRepository.updateById(entity);
        return toDTO(entity);
    }

    @Transactional
    public void delete(Long id) {
        NovelCharacter entity = novelCharacterRepository.selectById(id);
        if (entity == null) {
            throw new ResourceNotFoundException("角色不存在");
        }
        if (novelCharacterRepository.deleteById(id) <= 0) {
            throw new BusinessException("删除失败");
        }
    }

    // ==================== 关系图谱 API ====================

    /**
     * 获取作品的角色关系图谱
     */
    public Map<String, Object> getRelationshipGraph(Long novelId) {
        Long userId = ThreadLocalUtil.getUserId();
        LambdaQueryWrapper<NovelCharacter> wrapper = new LambdaQueryWrapper<>();
        wrapper.eq(NovelCharacter::getUserId, userId);
        if (novelId != null) {
            wrapper.eq(NovelCharacter::getNovelId, novelId);
        }
        wrapper.orderByAsc(NovelCharacter::getSortOrder);

        List<NovelCharacter> characters = novelCharacterRepository.selectList(wrapper);

        // 构建节点列表
        List<Map<String, Object>> nodes = new ArrayList<>();
        // 构建关系列表
        List<Map<String, Object>> edges = new ArrayList<>();
        // 用于去重的关系集合
        Set<String> edgeKeys = new HashSet<>();

        for (NovelCharacter character : characters) {
            // 添加节点
            Map<String, Object> node = new LinkedHashMap<>();
            node.put("id", character.getId());
            node.put("name", character.getName());
            node.put("identity", character.getIdentity() != null ? character.getIdentity() : "");
            node.put("gender", character.getGender());
            nodes.add(node);

            // 解析关系数据
            if (character.getRelationships() != null && !character.getRelationships().isBlank()) {
                try {
                    JsonNode rels = objectMapper.readTree(character.getRelationships());
                    if (rels.isArray()) {
                        for (JsonNode rel : rels) {
                            Long targetId = rel.has("targetId") ? rel.get("targetId").asLong() : null;
                            String relationType = rel.has("type") ? rel.get("type").asText("未知") : "未知";
                            String description = rel.has("description") ? rel.get("description").asText("") : "";

                            if (targetId != null) {
                                String edgeKey = Math.min(character.getId(), targetId) + "-" + Math.max(character.getId(), targetId);
                                if (!edgeKeys.contains(edgeKey)) {
                                    edgeKeys.add(edgeKey);
                                    Map<String, Object> edge = new LinkedHashMap<>();
                                    edge.put("source", character.getId());
                                    edge.put("target", targetId);
                                    edge.put("type", relationType);
                                    edge.put("description", description);
                                    edges.add(edge);
                                }
                            }
                        }
                    }
                } catch (JsonProcessingException ignored) {
                    // 忽略解析错误
                }
            }
        }

        Map<String, Object> graph = new LinkedHashMap<>();
        graph.put("nodes", nodes);
        graph.put("edges", edges);
        return graph;
    }

    public List<NovelCharacterDTO> exportCharacters(Long novelId) {
        Long userId = ThreadLocalUtil.getUserId();
        realnameVerificationService.requireVerifiedForContentExport(userId);
        LambdaQueryWrapper<NovelCharacter> wrapper = new LambdaQueryWrapper<>();
        wrapper.eq(NovelCharacter::getUserId, userId);
        wrapper.eq(NovelCharacter::getNovelId, novelId);

        List<NovelCharacter> characters = novelCharacterRepository.selectList(wrapper);
        return characters.stream().map(this::toDTO).collect(Collectors.toList());
    }

    @Transactional
    public void importCharacters(Long novelId, List<NovelCharacterDTO> characters) {
        Long userId = ThreadLocalUtil.getUserId();

        for (NovelCharacterDTO dto : characters) {
            NovelCharacter entity = new NovelCharacter();
            BeanUtils.copyProperties(dto, entity);
            entity.setId(null);
            entity.setUserId(userId);
            entity.setNovelId(novelId);
            entity.setPersonality(toJsonString(dto.getPersonality()));
            entity.setAbilities(toJsonString(dto.getAbilities()));
            entity.setRelationships(toJsonString(dto.getRelationships()));
            entity.setGrowthArc(toJsonString(dto.getGrowthArc()));
            novelCharacterRepository.insert(entity);
        }
    }

    // ==================== DTO 转换 ====================

    private NovelCharacterDTO toDTO(NovelCharacter entity) {
        NovelCharacterDTO dto = new NovelCharacterDTO();
        BeanUtils.copyProperties(entity, dto);
        dto.setPersonality(parseJson(entity.getPersonality()));
        dto.setAbilities(parseJson(entity.getAbilities()));
        dto.setRelationships(parseJson(entity.getRelationships()));
        dto.setGrowthArc(parseJson(entity.getGrowthArc()));
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