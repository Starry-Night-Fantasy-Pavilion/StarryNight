package com.starrynight.starrynight.system.auth.controller;

import com.starrynight.starrynight.framework.common.exception.BusinessException;
import com.starrynight.starrynight.framework.common.util.ClientIpResolver;
import com.starrynight.starrynight.framework.common.vo.ResponseVO;
import com.starrynight.starrynight.system.auth.dto.OauthExchangeDTO;
import com.starrynight.starrynight.system.auth.oauth.PortalOAuthService;
import com.starrynight.starrynight.system.auth.vo.AuthVO;
import com.starrynight.starrynight.system.auth.vo.OauthLoginOptionsVO;
import jakarta.servlet.http.HttpServletRequest;
import jakarta.servlet.http.HttpServletResponse;
import jakarta.validation.Valid;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.web.bind.annotation.*;

import java.io.IOException;

@RestController
@RequestMapping("/api/auth/oauth")
public class OauthLoginController {

    @Autowired
    private PortalOAuthService portalOAuthService;

    @GetMapping("/options")
    public ResponseVO<OauthLoginOptionsVO> options() {
        return ResponseVO.success(portalOAuthService.loginOptions());
    }

    /**
     * 知我云聚合登录（<a href="https://u.zevost.com/doc.php">文档</a>）。回调地址须登记为
     * {@code {站点公网根}/api/auth/oauth/zevost/callback}。{@code type} 为 qq、wx、github 等。
     */
    @GetMapping("/zevost/{type}/start")
    public void zevostStart(
            @PathVariable String type, HttpServletRequest request, HttpServletResponse response) throws IOException {
        portalOAuthService.startZevost(type, request, response);
    }

    @GetMapping("/zevost/callback")
    public void zevostCallback(
            @RequestParam String type,
            @RequestParam(required = false) String code,
            HttpServletRequest request,
            HttpServletResponse response) throws IOException {
        portalOAuthService.handleZevostCallback(type, code, request, response);
    }

    /** 发起 OAuth：{@code provider} 为 linuxdo、github、google、wechat、qq（与回调路径一致）。 */
    @GetMapping("/{provider}/start")
    public void start(@PathVariable String provider, HttpServletResponse response) throws IOException {
        try {
            response.sendRedirect(portalOAuthService.buildAuthorizeUrl(provider));
        } catch (BusinessException e) {
            response.sendError(HttpServletResponse.SC_BAD_REQUEST, e.getMessage());
        }
    }

    @GetMapping("/{provider}/callback")
    public void callback(
            @PathVariable String provider,
            @RequestParam(required = false) String code,
            @RequestParam(required = false) String state,
            @RequestParam(required = false) String error,
            HttpServletResponse response) throws IOException {
        portalOAuthService.handleCallback(provider, code, state, error, response);
    }

    @PostMapping("/exchange")
    public ResponseVO<AuthVO> exchange(@Valid @RequestBody OauthExchangeDTO dto, HttpServletRequest request) {
        return ResponseVO.success(portalOAuthService.exchangeSid(dto.getSid(), ClientIpResolver.resolve(request)));
    }
}
