package com.starrynight.starrynight.system.ops.controller;

import com.starrynight.starrynight.framework.common.vo.ResponseVO;
import com.starrynight.starrynight.system.auth.service.AuthService;
import com.starrynight.starrynight.system.auth.vo.AuthVO;
import com.starrynight.starrynight.system.ops.dto.OpsSelfPasswordDTO;
import com.starrynight.starrynight.system.ops.dto.OpsSelfProfileDTO;
import com.starrynight.starrynight.system.ops.service.OpsSelfService;
import jakarta.validation.Valid;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.security.access.prepost.PreAuthorize;
import org.springframework.web.bind.annotation.*;

@RestController
@RequestMapping("/api/admin/self")
@PreAuthorize("hasRole('ADMIN')")
public class AdminOpsSelfController {

    @Autowired
    private AuthService authService;
    @Autowired
    private OpsSelfService opsSelfService;

    @GetMapping("/profile")
    public ResponseVO<AuthVO.UserInfo> profile() {
        return ResponseVO.success(authService.getCurrentUser());
    }

    @PutMapping("/profile")
    public ResponseVO<AuthVO.UserInfo> updateProfile(@Valid @RequestBody OpsSelfProfileDTO dto) {
        return ResponseVO.success(opsSelfService.updateProfile(dto));
    }

    @PutMapping("/password")
    public ResponseVO<Void> updatePassword(@Valid @RequestBody OpsSelfPasswordDTO dto) {
        opsSelfService.updatePassword(dto);
        return ResponseVO.success();
    }
}
