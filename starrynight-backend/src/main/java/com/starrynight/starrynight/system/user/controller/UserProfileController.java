package com.starrynight.starrynight.system.user.controller;

import com.starrynight.starrynight.framework.common.vo.ResponseVO;
import com.starrynight.starrynight.system.user.dto.UserProfileDTO;
import com.starrynight.starrynight.system.user.dto.UserProfileUpdateDTO;
import com.starrynight.starrynight.system.user.service.UserProfileService;
import jakarta.validation.Valid;
import org.springframework.web.bind.annotation.GetMapping;
import org.springframework.web.bind.annotation.PutMapping;
import org.springframework.web.bind.annotation.RequestBody;
import org.springframework.web.bind.annotation.RequestMapping;
import org.springframework.web.bind.annotation.RestController;

@RestController
@RequestMapping("/api/user/profile")
public class UserProfileController {

    private final UserProfileService userProfileService;

    public UserProfileController(UserProfileService userProfileService) {
        this.userProfileService = userProfileService;
    }

    @GetMapping
    public ResponseVO<UserProfileDTO> getProfile() {
        return ResponseVO.success(userProfileService.getCurrentProfile());
    }

    @PutMapping
    public ResponseVO<UserProfileDTO> updateProfile(@Valid @RequestBody UserProfileUpdateDTO dto) {
        return ResponseVO.success(userProfileService.updateCurrentProfile(dto));
    }
}

