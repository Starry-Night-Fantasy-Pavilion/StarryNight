package com.starrynight.starrynight.system.auth.controller;

import com.starrynight.starrynight.system.auth.dto.LoginDTO;
import com.starrynight.starrynight.system.auth.dto.ResetPasswordDTO;
import com.starrynight.starrynight.system.auth.dto.RegisterDTO;
import com.starrynight.starrynight.system.auth.dto.SendCodeDTO;
import com.starrynight.starrynight.framework.common.util.ClientIpResolver;
import com.starrynight.starrynight.system.auth.service.AuthService;
import com.starrynight.starrynight.system.auth.vo.AuthVO;
import com.starrynight.starrynight.system.auth.vo.RegisterOptionsVO;
import com.starrynight.starrynight.framework.common.vo.ResponseVO;
import jakarta.servlet.http.HttpServletRequest;
import jakarta.validation.Valid;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.web.bind.annotation.*;

@RestController
@RequestMapping("/api/auth")
public class AuthController {

    @Autowired
    private AuthService authService;

    /** 注册页展示用：是否开放邮箱/手机号字段（无需登录） */
    @GetMapping("/register-options")
    public ResponseVO<RegisterOptionsVO> registerOptions() {
        return ResponseVO.success(authService.getRegisterOptions());
    }

    @PostMapping("/login")
    public ResponseVO<AuthVO> login(@Valid @RequestBody LoginDTO dto, HttpServletRequest request) {
        return ResponseVO.success(authService.login(dto, ClientIpResolver.resolve(request)));
    }

    @PostMapping("/register")
    public ResponseVO<AuthVO> register(@Valid @RequestBody RegisterDTO dto, HttpServletRequest request) {
        return ResponseVO.success(authService.register(dto, ClientIpResolver.resolve(request)));
    }

    @PostMapping("/refresh")
    public ResponseVO<AuthVO> refresh(@RequestHeader("Refresh-Token") String refreshToken) {
        return ResponseVO.success(authService.refreshToken(refreshToken));
    }

    /**
     * 对齐开发文档：/api/auth/refresh-token
     * 与 /refresh 复用同一逻辑，兼容旧前端。
     */
    @PostMapping("/refresh-token")
    public ResponseVO<AuthVO> refreshToken(@RequestHeader("Refresh-Token") String refreshToken) {
        return ResponseVO.success(authService.refreshToken(refreshToken));
    }

    @GetMapping("/me")
    public ResponseVO<AuthVO.UserInfo> me() {
        return ResponseVO.success(authService.getCurrentUser());
    }

    @PostMapping("/logout")
    public ResponseVO<Void> logout() {
        authService.logout();
        return ResponseVO.success();
    }

    @PostMapping("/send-code")
    public ResponseVO<Void> sendCode(@Valid @RequestBody SendCodeDTO dto) {
        authService.sendCode(dto);
        return ResponseVO.success();
    }

    @PostMapping("/reset-password")
    public ResponseVO<Void> resetPassword(@Valid @RequestBody ResetPasswordDTO dto) {
        authService.resetPassword(dto);
        return ResponseVO.success();
    }
}

