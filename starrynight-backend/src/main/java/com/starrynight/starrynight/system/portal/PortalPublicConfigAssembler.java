package com.starrynight.starrynight.system.portal;

import com.fasterxml.jackson.core.type.TypeReference;
import com.fasterxml.jackson.databind.ObjectMapper;
import com.starrynight.starrynight.system.system.service.RuntimeConfigService;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.stereotype.Component;
import org.springframework.util.StringUtils;

import java.util.Collections;
import java.util.List;

@Component
public class PortalPublicConfigAssembler {

    @Autowired
    private RuntimeConfigService runtimeConfigService;

    @Autowired
    private ObjectMapper objectMapper;

    public PortalPublicConfigVO assemble() {
        PortalPublicConfigVO vo = new PortalPublicConfigVO();
        vo.setApiPublicOrigin(runtimeConfigService.getString("portal.frontend.api-public-origin", "").trim());
        vo.setSiteName(runtimeConfigService.getString("portal.site.name", "星夜阁"));
        vo.setSiteLogoUrl(runtimeConfigService.getString("portal.site.logo-url", "").trim());
        vo.setPlatformCoinName(runtimeConfigService.getString("portal.wallet.coin-display-name", "星夜币"));

        vo.setFooterIcpEnabled(runtimeConfigService.getBoolean("portal.footer.icp.enabled", false));
        vo.setFooterIcpRecord(runtimeConfigService.getString("portal.footer.icp.record", ""));
        vo.setFooterIcpUrl(runtimeConfigService.getString("portal.footer.icp.url", "").trim());

        vo.setFooterContactEnabled(runtimeConfigService.getBoolean("portal.footer.contact.enabled", false));
        vo.setFooterContactLines(readContactLines(runtimeConfigService.getProperty("portal.footer.contact.lines-json")));

        vo.setFooterFriendLinksEnabled(runtimeConfigService.getBoolean("portal.footer.friend-links.enabled", false));
        vo.setFooterFriendLinks(readFriendLinks(runtimeConfigService.getProperty("portal.footer.friend-links.json")));

        vo.setFooterSponsorEnabled(runtimeConfigService.getBoolean("portal.footer.sponsor.enabled", false));
        vo.setFooterSponsorText(runtimeConfigService.getString("portal.footer.sponsor.text", ""));
        vo.setFooterSponsorHtmlUrl(runtimeConfigService.getString("portal.footer.sponsor.html-url", "").trim());
        vo.setCommunityAutoPublishPosts(
                runtimeConfigService.getBoolean("starrynight.community.auto_publish_posts", true));
        return vo;
    }

    private List<FooterContactLineVO> readContactLines(String raw) {
        if (!StringUtils.hasText(raw)) {
            return Collections.emptyList();
        }
        try {
            List<FooterContactLineVO> list = objectMapper.readValue(raw.trim(), new TypeReference<List<FooterContactLineVO>>() {
            });
            return list != null ? list : Collections.emptyList();
        } catch (Exception e) {
            return Collections.emptyList();
        }
    }

    private List<FooterFriendLinkVO> readFriendLinks(String raw) {
        if (!StringUtils.hasText(raw)) {
            return Collections.emptyList();
        }
        try {
            List<FooterFriendLinkVO> list = objectMapper.readValue(raw.trim(), new TypeReference<List<FooterFriendLinkVO>>() {
            });
            return list != null ? list : Collections.emptyList();
        } catch (Exception e) {
            return Collections.emptyList();
        }
    }
}
