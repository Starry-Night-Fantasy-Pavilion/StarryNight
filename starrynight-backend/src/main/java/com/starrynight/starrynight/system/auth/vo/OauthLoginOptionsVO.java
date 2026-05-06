package com.starrynight.starrynight.system.auth.vo;

import lombok.Data;

import java.util.LinkedHashMap;
import java.util.Map;

@Data
public class OauthLoginOptionsVO {

    private boolean linuxdoEnabled;
    private boolean githubEnabled;
    private boolean googleEnabled;
    private boolean wechatEnabled;
    private boolean qqEnabled;

    /** 知我云聚合总开关；各方式见 {@link #zevostTypes} */
    private boolean zevostEnabled;

    /**
     * 聚合登录项是否对登录页展示：key 为知我云 type（qq、wx、github 等），与
     * <a href="https://u.zevost.com/doc.php">知我云文档</a> 一致。
     */
    private Map<String, Boolean> zevostTypes = new LinkedHashMap<>();
}
