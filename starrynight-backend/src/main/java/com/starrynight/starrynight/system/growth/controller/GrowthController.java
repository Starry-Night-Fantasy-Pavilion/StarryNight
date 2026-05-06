package com.starrynight.starrynight.system.growth.controller;

import com.starrynight.starrynight.framework.common.util.ThreadLocalUtil;
import com.starrynight.starrynight.framework.common.vo.ResponseVO;
import com.starrynight.starrynight.system.growth.entity.PointsTransaction;
import com.starrynight.starrynight.system.growth.entity.TaskConfig;
import com.starrynight.starrynight.system.growth.service.GrowthService;
import com.starrynight.starrynight.system.redeem.dto.RedeemRequest;
import com.starrynight.starrynight.system.redeem.dto.RedeemResultDTO;
import com.starrynight.starrynight.system.redeem.service.RedeemService;
import jakarta.validation.Valid;
import lombok.RequiredArgsConstructor;
import lombok.extern.slf4j.Slf4j;
import org.springframework.security.access.prepost.PreAuthorize;
import org.springframework.web.bind.annotation.*;

import java.util.List;

@Slf4j
@RestController
@RequestMapping("/api/growth")
@RequiredArgsConstructor
public class GrowthController {

    private final GrowthService growthService;
    private final RedeemService redeemService;

    @PostMapping("/checkin")
    public ResponseVO<GrowthService.CheckinResult> doCheckin(@RequestParam Long userId) {
        GrowthService.CheckinResult result = growthService.doCheckin(userId);
        return ResponseVO.success(result);
    }

    @GetMapping("/checkin/status")
    public ResponseVO<GrowthService.CheckinStatus> getCheckinStatus(@RequestParam Long userId) {
        GrowthService.CheckinStatus status = growthService.getCheckinStatus(userId);
        return ResponseVO.success(status);
    }

    @GetMapping("/points/summary")
    public ResponseVO<GrowthService.PointsSummary> getPointsSummary(@RequestParam Long userId) {
        GrowthService.PointsSummary summary = growthService.getPointsSummary(userId);
        return ResponseVO.success(summary);
    }

    @GetMapping("/points/history")
    public ResponseVO<List<PointsTransaction>> getPointsHistory(
            @RequestParam Long userId,
            @RequestParam(defaultValue = "20") int limit) {
        List<PointsTransaction> history = growthService.getPointsHistory(userId, limit);
        return ResponseVO.success(history);
    }

    @GetMapping("/tasks/daily")
    public ResponseVO<List<GrowthService.TaskStatus>> getDailyTasks(@RequestParam Long userId) {
        List<GrowthService.TaskStatus> tasks = growthService.getDailyTasks(userId);
        return ResponseVO.success(tasks);
    }

    @GetMapping("/tasks/achievement")
    public ResponseVO<List<TaskConfig>> getAchievementTasks() {
        List<TaskConfig> tasks = growthService.getAchievementTasks();
        return ResponseVO.success(tasks);
    }

    @PostMapping("/tasks/complete")
    public ResponseVO<Void> recordTaskCompletion(
            @RequestParam Long userId,
            @RequestParam String taskCode) {
        growthService.recordTaskCompletion(userId, taskCode);
        return ResponseVO.success(null);
    }

    /** 用户端兑换码（需用户 JWT，运营账号不可用） */
    @PostMapping("/redeem")
    @PreAuthorize("isAuthenticated() and !hasRole('ADMIN')")
    public ResponseVO<RedeemResultDTO> redeem(@Valid @RequestBody RedeemRequest request) {
        return ResponseVO.success(redeemService.redeem(ThreadLocalUtil.getUserId(), request.getCode()));
    }
}
