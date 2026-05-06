package com.starrynight.starrynight.system.portal;

import lombok.Data;

import java.util.ArrayList;
import java.util.List;

/**
 * 前台启动时拉取的公开配置（无需登录）。
 */
@Data
public class PortalPublicConfigVO {

    /**
     * 浏览器访问后端 API 时使用的站点根（无尾斜杠、不含 /api），实际请求为 {@code {apiPublicOrigin}/api/...}。
     * 空表示使用当前页面所在源的相对路径 {@code /api}。
     */
    private String apiPublicOrigin;

    /** 网站名称，默认星夜阁 */
    private String siteName;

    /** 网站 Logo 完整 URL，可空 */
    private String siteLogoUrl;

    /** 平台币展示名称 */
    private String platformCoinName;

    private boolean footerIcpEnabled;
    private String footerIcpRecord;
    private String footerIcpUrl;

    private boolean footerContactEnabled;
    private List<FooterContactLineVO> footerContactLines = new ArrayList<>();

    private boolean footerFriendLinksEnabled;
    private List<FooterFriendLinkVO> footerFriendLinks = new ArrayList<>();

    private boolean footerSponsorEnabled;
    /** 旧版纯文本（未上传 HTML 时仍可展示） */
    private String footerSponsorText;
    /** 上传至对象存储的鸣谢页 HTML 访问地址 */
    private String footerSponsorHtmlUrl;

    /**
     * 与 {@code starrynight.community.auto_publish_posts} 一致：为 true 时用户发帖无需运营审核即上架。
     */
    private boolean communityAutoPublishPosts;
}
