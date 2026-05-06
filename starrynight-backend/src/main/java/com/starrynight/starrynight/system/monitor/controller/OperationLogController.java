package com.starrynight.starrynight.system.monitor.controller;

import com.baomidou.mybatisplus.extension.plugins.pagination.Page;
import com.starrynight.starrynight.framework.common.vo.PageVO;
import com.starrynight.starrynight.framework.common.vo.ResponseVO;
import com.starrynight.starrynight.system.monitor.entity.OperationLog;
import com.starrynight.starrynight.system.monitor.service.OperationLogService;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.format.annotation.DateTimeFormat;
import org.springframework.security.access.prepost.PreAuthorize;
import org.springframework.web.bind.annotation.*;

import java.time.LocalDateTime;
import java.util.List;

@RestController
@RequestMapping("/api/admin/logs")
@PreAuthorize("hasRole('ADMIN')")
public class OperationLogController {

    @Autowired
    private OperationLogService operationLogService;

    @GetMapping("/list")
    public ResponseVO<PageVO<OperationLog>> list(
            @RequestParam(required = false) Long userId,
            @RequestParam(required = false) String operation,
            @RequestParam(required = false) String module,
            @RequestParam(required = false) @DateTimeFormat(pattern = "yyyy-MM-dd HH:mm:ss") LocalDateTime startTime,
            @RequestParam(required = false) @DateTimeFormat(pattern = "yyyy-MM-dd HH:mm:ss") LocalDateTime endTime,
            @RequestParam(defaultValue = "1") int page,
            @RequestParam(defaultValue = "20") int size) {

        Page<OperationLog> pageData = operationLogService.list(userId, operation, module, startTime, endTime, page, size);
        return ResponseVO.success(PageVO.of(
                pageData.getTotal(),
                pageData.getRecords(),
                pageData.getCurrent(),
                pageData.getSize()
        ));
    }

    @GetMapping("/recent")
    public ResponseVO<List<OperationLog>> recent(@RequestParam(defaultValue = "50") int limit) {
        return ResponseVO.success(operationLogService.getRecentLogs(limit));
    }

    @DeleteMapping("/{id}")
    public ResponseVO<Void> delete(@PathVariable Long id) {
        operationLogService.delete(id);
        return ResponseVO.success();
    }
}

