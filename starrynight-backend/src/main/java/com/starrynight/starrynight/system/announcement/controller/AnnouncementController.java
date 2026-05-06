package com.starrynight.starrynight.system.announcement.controller;

import com.starrynight.starrynight.framework.common.vo.ResponseVO;
import com.starrynight.starrynight.system.announcement.dto.AnnouncementDTO;
import com.starrynight.starrynight.system.announcement.service.AnnouncementService;
import jakarta.validation.Valid;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.security.access.prepost.PreAuthorize;
import org.springframework.web.bind.annotation.*;

import java.util.List;

@RestController
@RequestMapping("/api/admin/announcements")
@PreAuthorize("hasRole('ADMIN')")
public class AnnouncementController {

    @Autowired
    private AnnouncementService announcementService;

    @GetMapping("/list")
    public ResponseVO<List<AnnouncementDTO>> list(@RequestParam(required = false) Integer status) {
        return ResponseVO.success(announcementService.list(status));
    }

    @GetMapping("/{id}")
    public ResponseVO<AnnouncementDTO> get(@PathVariable Long id) {
        return ResponseVO.success(announcementService.getById(id));
    }

    @PostMapping
    public ResponseVO<AnnouncementDTO> create(@Valid @RequestBody AnnouncementDTO dto) {
        return ResponseVO.success(announcementService.create(dto));
    }

    @PutMapping("/{id}")
    public ResponseVO<AnnouncementDTO> update(@PathVariable Long id, @Valid @RequestBody AnnouncementDTO dto) {
        return ResponseVO.success(announcementService.update(id, dto));
    }

    @DeleteMapping("/{id}")
    public ResponseVO<Void> delete(@PathVariable Long id) {
        announcementService.delete(id);
        return ResponseVO.success();
    }
}
