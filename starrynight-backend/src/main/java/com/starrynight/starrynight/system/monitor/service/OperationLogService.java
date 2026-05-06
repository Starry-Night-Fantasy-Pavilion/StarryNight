package com.starrynight.starrynight.system.monitor.service;

import com.baomidou.mybatisplus.core.conditions.query.LambdaQueryWrapper;
import com.baomidou.mybatisplus.extension.plugins.pagination.Page;
import com.starrynight.starrynight.system.monitor.entity.OperationLog;
import com.starrynight.starrynight.system.monitor.repository.OperationLogRepository;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.stereotype.Service;

import java.time.LocalDateTime;
import java.util.List;

@Service
public class OperationLogService {

    @Autowired
    private OperationLogRepository operationLogRepository;

    public Page<OperationLog> list(Long userId, String operation, String module,
                                   LocalDateTime startTime, LocalDateTime endTime,
                                   int page, int size) {
        LambdaQueryWrapper<OperationLog> wrapper = new LambdaQueryWrapper<>();

        if (userId != null) {
            wrapper.eq(OperationLog::getUserId, userId);
        }
        if (operation != null && !operation.isEmpty()) {
            wrapper.like(OperationLog::getOperation, operation);
        }
        if (module != null && !module.isEmpty()) {
            wrapper.eq(OperationLog::getModule, module);
        }
        if (startTime != null) {
            wrapper.ge(OperationLog::getCreateTime, startTime);
        }
        if (endTime != null) {
            wrapper.le(OperationLog::getCreateTime, endTime);
        }

        wrapper.orderByDesc(OperationLog::getCreateTime);

        return operationLogRepository.selectPage(new Page<>(page, size), wrapper);
    }

    public List<OperationLog> getRecentLogs(int limit) {
        return operationLogRepository.selectList(
                new LambdaQueryWrapper<OperationLog>()
                        .orderByDesc(OperationLog::getCreateTime)
                        .last("LIMIT " + limit)
        );
    }

    public void delete(Long id) {
        operationLogRepository.deleteById(id);
    }
}

