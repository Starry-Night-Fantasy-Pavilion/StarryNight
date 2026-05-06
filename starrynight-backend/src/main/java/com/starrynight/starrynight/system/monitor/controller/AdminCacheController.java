package com.starrynight.starrynight.system.monitor.controller;

import com.starrynight.starrynight.framework.common.vo.ResponseVO;
import com.starrynight.starrynight.system.monitor.dto.CacheKeyEntryDTO;
import com.starrynight.starrynight.system.monitor.service.AdminCacheService;
import lombok.RequiredArgsConstructor;
import org.springframework.security.access.prepost.PreAuthorize;
import org.springframework.web.bind.annotation.*;

import java.util.List;

@RestController
@RequestMapping("/api/admin/cache")
@PreAuthorize("hasRole('ADMIN')")
@RequiredArgsConstructor
public class AdminCacheController {

    private final AdminCacheService adminCacheService;

    @GetMapping("/names")
    public ResponseVO<List<String>> names() {
        return ResponseVO.success(adminCacheService.listCacheNames());
    }

    @GetMapping("/redis/scan")
    public ResponseVO<List<CacheKeyEntryDTO>> scan(
            @RequestParam(required = false) String pattern,
            @RequestParam(defaultValue = "100") int limit) {
        return ResponseVO.success(adminCacheService.scanKeys(pattern, limit));
    }

    @GetMapping("/redis/value")
    public ResponseVO<String> value(@RequestParam String key) {
        return ResponseVO.success(adminCacheService.previewValue(key));
    }

    @DeleteMapping("/redis/key")
    public ResponseVO<Void> deleteKey(@RequestParam String key) {
        adminCacheService.deleteRedisKey(key);
        return ResponseVO.success();
    }

    @PostMapping("/spring/clear")
    public ResponseVO<Void> clearSpring(@RequestParam String cacheName) {
        adminCacheService.clearSpringCache(cacheName);
        return ResponseVO.success();
    }
}
