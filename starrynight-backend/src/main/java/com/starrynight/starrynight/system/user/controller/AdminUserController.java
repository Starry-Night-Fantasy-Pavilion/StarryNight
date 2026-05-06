package com.starrynight.starrynight.system.user.controller;

import com.baomidou.mybatisplus.extension.plugins.pagination.Page;
import com.starrynight.starrynight.framework.common.vo.PageVO;
import com.starrynight.starrynight.framework.common.vo.ResponseVO;
import com.starrynight.starrynight.system.user.dto.AdminUserBalanceUpdateDTO;
import com.starrynight.starrynight.system.user.dto.AdminUserCreateDTO;
import com.starrynight.starrynight.system.user.dto.AdminUserDetailDTO;
import com.starrynight.starrynight.system.user.dto.AdminUserDTO;
import com.starrynight.starrynight.system.user.dto.AdminUserMembershipUpdateDTO;
import com.starrynight.starrynight.system.user.dto.AdminUserRealnameVerifiedUpdateDTO;
import com.starrynight.starrynight.system.user.dto.UserPointsUpdateDTO;
import com.starrynight.starrynight.system.user.dto.UserStatusUpdateDTO;
import com.starrynight.starrynight.framework.common.util.ClientIpResolver;
import com.starrynight.starrynight.system.user.service.AdminUserService;
import jakarta.servlet.http.HttpServletRequest;
import jakarta.validation.Valid;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.security.access.prepost.PreAuthorize;
import org.springframework.web.bind.annotation.*;

@RestController
@RequestMapping("/api/admin/users")
@PreAuthorize("hasRole('ADMIN')")
public class AdminUserController {

    @Autowired
    private AdminUserService adminUserService;

    @PostMapping
    public ResponseVO<AdminUserDTO> create(@Valid @RequestBody AdminUserCreateDTO dto, HttpServletRequest request) {
        return ResponseVO.success(adminUserService.create(dto, ClientIpResolver.resolve(request)));
    }

    @GetMapping("/{id}/detail")
    public ResponseVO<AdminUserDetailDTO> detail(@PathVariable Long id) {
        return ResponseVO.success(adminUserService.getDetail(id));
    }

    @PutMapping("/{id}/balance")
    public ResponseVO<Void> updateBalance(@PathVariable Long id, @Valid @RequestBody AdminUserBalanceUpdateDTO dto) {
        adminUserService.updateBalance(id, dto);
        return ResponseVO.success();
    }

    @PutMapping("/{id}/membership")
    public ResponseVO<Void> updateMembership(@PathVariable Long id, @Valid @RequestBody AdminUserMembershipUpdateDTO dto) {
        adminUserService.updateMembership(id, dto);
        return ResponseVO.success();
    }

    /** 人工修正实名核验状态（人脸/三方）；标为通过时须已登记姓名与证件号。 */
    @PutMapping("/{id}/realname-verified")
    public ResponseVO<Void> updateRealnameVerified(
            @PathVariable Long id, @Valid @RequestBody AdminUserRealnameVerifiedUpdateDTO dto) {
        adminUserService.updateRealnameVerified(id, dto.getRealNameVerified());
        return ResponseVO.success();
    }

    @GetMapping("/list")
    public ResponseVO<PageVO<AdminUserDTO>> list(
            @RequestParam(required = false) String keyword,
            @RequestParam(required = false) Integer status,
            @RequestParam(required = false) Integer memberLevel,
            @RequestParam(required = false) Integer memberLevelMin,
            @RequestParam(defaultValue = "1") int page,
            @RequestParam(defaultValue = "10") int size) {
        Page<AdminUserDTO> pageData = adminUserService.list(keyword, status, memberLevel, memberLevelMin, page, size);
        return ResponseVO.success(PageVO.of(
                pageData.getTotal(),
                pageData.getRecords(),
                pageData.getCurrent(),
                pageData.getSize()
        ));
    }

    @PutMapping("/{id}/status")
    public ResponseVO<Void> updateStatus(@PathVariable Long id, @Valid @RequestBody UserStatusUpdateDTO dto) {
        adminUserService.updateStatus(id, dto.getStatus());
        return ResponseVO.success();
    }

    @PutMapping("/{id}/points")
    public ResponseVO<Void> updatePoints(@PathVariable Long id, @Valid @RequestBody UserPointsUpdateDTO dto) {
        adminUserService.updatePoints(id, dto.getPoints());
        return ResponseVO.success();
    }
}
