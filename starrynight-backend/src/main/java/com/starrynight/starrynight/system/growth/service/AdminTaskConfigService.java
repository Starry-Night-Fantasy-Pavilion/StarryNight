package com.starrynight.starrynight.system.growth.service;

import com.baomidou.mybatisplus.core.conditions.query.LambdaQueryWrapper;
import com.baomidou.mybatisplus.extension.plugins.pagination.Page;
import com.starrynight.starrynight.framework.common.exception.BusinessException;
import com.starrynight.starrynight.framework.common.exception.ResourceNotFoundException;
import com.starrynight.starrynight.framework.common.vo.PageVO;
import com.starrynight.starrynight.system.growth.dto.TaskConfigDTO;
import com.starrynight.starrynight.system.growth.entity.TaskConfig;
import com.starrynight.starrynight.system.growth.mapper.TaskConfigMapper;
import lombok.RequiredArgsConstructor;
import org.springframework.stereotype.Service;
import org.springframework.util.StringUtils;

import java.util.List;
import java.util.stream.Collectors;

@Service
@RequiredArgsConstructor
public class AdminTaskConfigService {

    private final TaskConfigMapper taskConfigMapper;

    public PageVO<TaskConfigDTO> page(String keyword, int page, int size) {
        LambdaQueryWrapper<TaskConfig> w = new LambdaQueryWrapper<>();
        if (StringUtils.hasText(keyword)) {
            String k = keyword.trim();
            w.and(q -> q.like(TaskConfig::getTaskCode, k)
                    .or().like(TaskConfig::getTaskName, k)
                    .or().like(TaskConfig::getDescription, k));
        }
        w.orderByAsc(TaskConfig::getSortOrder).orderByDesc(TaskConfig::getId);
        Page<TaskConfig> p = taskConfigMapper.selectPage(new Page<>(page, size), w);
        List<TaskConfigDTO> records = p.getRecords().stream().map(this::toDto).collect(Collectors.toList());
        return PageVO.of(p.getTotal(), records, p.getCurrent(), p.getSize());
    }

    public List<TaskConfigDTO> listAll() {
        return taskConfigMapper.selectList(
                        new LambdaQueryWrapper<TaskConfig>()
                                .orderByAsc(TaskConfig::getSortOrder)
                                .orderByDesc(TaskConfig::getId))
                .stream()
                .map(this::toDto)
                .collect(Collectors.toList());
    }

    public TaskConfigDTO getById(Long id) {
        TaskConfig entity = taskConfigMapper.selectById(id);
        if (entity == null) {
            throw new ResourceNotFoundException("任务配置不存在");
        }
        return toDto(entity);
    }

    public TaskConfigDTO create(TaskConfigDTO dto) {
        Long cnt = taskConfigMapper.selectCount(
                new LambdaQueryWrapper<TaskConfig>().eq(TaskConfig::getTaskCode, dto.getTaskCode().trim()));
        if (cnt != null && cnt > 0) {
            throw new BusinessException("任务编码已存在");
        }
        TaskConfig entity = new TaskConfig();
        apply(dto, entity);
        entity.setTaskCode(dto.getTaskCode().trim());
        taskConfigMapper.insert(entity);
        return toDto(entity);
    }

    public TaskConfigDTO update(Long id, TaskConfigDTO dto) {
        TaskConfig entity = taskConfigMapper.selectById(id);
        if (entity == null) {
            throw new ResourceNotFoundException("任务配置不存在");
        }
        String newCode = dto.getTaskCode().trim();
        if (!newCode.equals(entity.getTaskCode())) {
            Long cnt = taskConfigMapper.selectCount(
                    new LambdaQueryWrapper<TaskConfig>()
                            .eq(TaskConfig::getTaskCode, newCode)
                            .ne(TaskConfig::getId, id));
            if (cnt != null && cnt > 0) {
                throw new BusinessException("任务编码已存在");
            }
        }
        apply(dto, entity);
        entity.setTaskCode(newCode);
        taskConfigMapper.updateById(entity);
        return toDto(entity);
    }

    public void delete(Long id) {
        TaskConfig entity = taskConfigMapper.selectById(id);
        if (entity == null) {
            throw new ResourceNotFoundException("任务配置不存在");
        }
        taskConfigMapper.deleteById(id);
    }

    private void apply(TaskConfigDTO dto, TaskConfig entity) {
        entity.setTaskName(dto.getTaskName());
        entity.setTaskType(dto.getTaskType());
        entity.setDescription(dto.getDescription());
        entity.setTriggerAction(dto.getTriggerAction());
        entity.setRewardType(dto.getRewardType());
        entity.setRewardAmount(dto.getRewardAmount());
        entity.setConditionValue(dto.getConditionValue());
        entity.setConditionOperator(dto.getConditionOperator() != null ? dto.getConditionOperator() : "eq");
        entity.setMaxDailyTimes(dto.getMaxDailyTimes());
        entity.setSortOrder(dto.getSortOrder());
        entity.setEnabled(dto.getEnabled());
    }

    private TaskConfigDTO toDto(TaskConfig entity) {
        TaskConfigDTO dto = new TaskConfigDTO();
        dto.setId(entity.getId());
        dto.setTaskCode(entity.getTaskCode());
        dto.setTaskName(entity.getTaskName());
        dto.setTaskType(entity.getTaskType());
        dto.setDescription(entity.getDescription());
        dto.setTriggerAction(entity.getTriggerAction());
        dto.setRewardType(entity.getRewardType());
        dto.setRewardAmount(entity.getRewardAmount());
        dto.setConditionValue(entity.getConditionValue());
        dto.setConditionOperator(entity.getConditionOperator());
        dto.setMaxDailyTimes(entity.getMaxDailyTimes());
        dto.setSortOrder(entity.getSortOrder());
        dto.setEnabled(entity.getEnabled());
        dto.setCreateTime(entity.getCreateTime());
        dto.setUpdateTime(entity.getUpdateTime());
        return dto;
    }
}
