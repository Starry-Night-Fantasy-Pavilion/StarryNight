package com.starrynight.starrynight.system.consistency.controller;

import com.starrynight.starrynight.system.consistency.entity.ForeshadowingRecord;
import com.starrynight.starrynight.system.consistency.service.ForeshadowingService;
import com.starrynight.engine.foreshadowing.PayoffCheckResult;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.http.ResponseEntity;
import org.springframework.web.bind.annotation.*;

import java.util.HashMap;
import java.util.List;
import java.util.Map;

@RestController
@RequestMapping("/api/foreshadowing")
public class ForeshadowingController {

    @Autowired
    private ForeshadowingService foreshadowingService;

    @PostMapping("/detect/{novelId}/{chapterNo}")
    public ResponseEntity<Map<String, Object>> detectForeshadowing(
            @PathVariable Long novelId,
            @PathVariable Integer chapterNo,
            @RequestBody Map<String, String> request) {
        String content = request.get("content");
        List<ForeshadowingRecord> detected = foreshadowingService.detectAndSave(novelId, chapterNo, content);

        Map<String, Object> response = new HashMap<>();
        response.put("success", true);
        response.put("count", detected.size());
        response.put("data", detected);
        return ResponseEntity.ok(response);
    }

    @GetMapping("/pending/{novelId}")
    public ResponseEntity<Map<String, Object>> getPendingForeshadowings(@PathVariable Long novelId) {
        List<ForeshadowingRecord> pending = foreshadowingService.getPendingForeshadowings(novelId);

        Map<String, Object> response = new HashMap<>();
        response.put("success", true);
        response.put("count", pending.size());
        response.put("data", pending);
        return ResponseEntity.ok(response);
    }

    @GetMapping("/paid-off/{novelId}")
    public ResponseEntity<Map<String, Object>> getPaidOffForeshadowings(@PathVariable Long novelId) {
        List<ForeshadowingRecord> paidOff = foreshadowingService.getPaidOffForeshadowings(novelId);

        Map<String, Object> response = new HashMap<>();
        response.put("success", true);
        response.put("count", paidOff.size());
        response.put("data", paidOff);
        return ResponseEntity.ok(response);
    }

    @GetMapping("/all/{novelId}")
    public ResponseEntity<Map<String, Object>> getAllForeshadowings(@PathVariable Long novelId) {
        List<ForeshadowingRecord> all = foreshadowingService.getAllForeshadowings(novelId);

        Map<String, Object> response = new HashMap<>();
        response.put("success", true);
        response.put("count", all.size());
        response.put("data", all);
        return ResponseEntity.ok(response);
    }

    @PutMapping("/confirm/{id}")
    public ResponseEntity<Map<String, Object>> confirmForeshadowing(@PathVariable String id) {
        ForeshadowingRecord record = foreshadowingService.confirmForeshadowing(id);

        Map<String, Object> response = new HashMap<>();
        response.put("success", record != null);
        response.put("data", record);
        return ResponseEntity.ok(response);
    }

    @PutMapping("/cancel/{id}")
    public ResponseEntity<Map<String, Object>> cancelForeshadowing(@PathVariable String id) {
        ForeshadowingRecord record = foreshadowingService.cancelForeshadowing(id);

        Map<String, Object> response = new HashMap<>();
        response.put("success", record != null);
        response.put("data", record);
        return ResponseEntity.ok(response);
    }

    @PutMapping("/expected-chapter/{id}")
    public ResponseEntity<Map<String, Object>> setExpectedChapter(
            @PathVariable String id,
            @RequestBody Map<String, Integer> request) {
        Integer expectedChapterNo = request.get("expectedChapterNo");
        ForeshadowingRecord record = foreshadowingService.setExpectedChapter(id, expectedChapterNo);

        Map<String, Object> response = new HashMap<>();
        response.put("success", record != null);
        response.put("data", record);
        return ResponseEntity.ok(response);
    }

    @PostMapping("/check-payoff/{id}")
    public ResponseEntity<Map<String, Object>> checkPayoff(
            @PathVariable String id,
            @RequestBody Map<String, Object> request) {
        @SuppressWarnings("unchecked")
        List<String> contents = (List<String>) request.get("contents");
        PayoffCheckResult result = foreshadowingService.checkPayoff(id, contents);

        Map<String, Object> response = new HashMap<>();
        response.put("success", result != null);
        response.put("data", result);
        return ResponseEntity.ok(response);
    }

    @PutMapping("/mark-paid-off/{id}")
    public ResponseEntity<Map<String, Object>> markAsPaidOff(
            @PathVariable String id,
            @RequestBody Map<String, String> request) {
        Integer paidOffChapterNo = Integer.parseInt(request.get("paidOffChapterNo"));
        String payoffMethod = request.get("payoffMethod");
        String payoffContent = request.get("payoffContent");

        ForeshadowingRecord record = foreshadowingService.markAsPaidOff(id, paidOffChapterNo, payoffMethod, payoffContent);

        Map<String, Object> response = new HashMap<>();
        response.put("success", record != null);
        response.put("data", record);
        return ResponseEntity.ok(response);
    }

    @GetMapping("/suggestions/{id}")
    public ResponseEntity<Map<String, Object>> getPayoffSuggestions(@PathVariable String id) {
        List<String> suggestions = foreshadowingService.getPayoffSuggestions(id);

        Map<String, Object> response = new HashMap<>();
        response.put("success", true);
        response.put("data", suggestions);
        return ResponseEntity.ok(response);
    }

    @GetMapping("/overdue/{novelId}")
    public ResponseEntity<Map<String, Object>> getOverdueForeshadowings(
            @PathVariable Long novelId,
            @RequestParam(defaultValue = "0") Integer currentChapter) {
        List<ForeshadowingRecord> overdue = foreshadowingService.getOverdueForeshadowings(novelId, currentChapter);

        Map<String, Object> response = new HashMap<>();
        response.put("success", true);
        response.put("count", overdue.size());
        response.put("data", overdue);
        return ResponseEntity.ok(response);
    }
}