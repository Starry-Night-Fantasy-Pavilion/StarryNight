package com.starrynight.starrynight.system.portal;

import com.starrynight.starrynight.framework.common.vo.ResponseVO;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.web.bind.annotation.GetMapping;
import org.springframework.web.bind.annotation.RequestMapping;
import org.springframework.web.bind.annotation.RestController;

@RestController
@RequestMapping("/api/portal")
public class PortalPublicConfigController {

    @Autowired
    private PortalPublicConfigAssembler portalPublicConfigAssembler;

    /**
     * 供前端首屏拉取：接口基址、站点品牌、页脚公开模块等（不含密钥）。
     */
    @GetMapping("/public-config")
    public ResponseVO<PortalPublicConfigVO> publicConfig() {
        return ResponseVO.success(portalPublicConfigAssembler.assemble());
    }
}
