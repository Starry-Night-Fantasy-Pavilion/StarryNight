package com.starrynight.starrynight.system.notification.controller;

import com.starrynight.starrynight.framework.common.vo.ResponseVO;
import com.starrynight.starrynight.system.notification.MailTemplateKind;
import com.starrynight.starrynight.system.notification.dto.MailTemplateCatalogItemDTO;
import com.starrynight.starrynight.system.notification.dto.MailTemplateHtmlStatusDTO;
import com.starrynight.starrynight.system.notification.dto.MailTemplatePreviewDTO;
import com.starrynight.starrynight.system.notification.service.MailTemplateFileService;
import com.starrynight.starrynight.system.notification.service.MailTemplatePreviewSampleProvider;
import com.starrynight.starrynight.system.notification.service.MailTemplatePreviewService;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.http.MediaType;
import org.springframework.web.bind.annotation.DeleteMapping;
import org.springframework.web.bind.annotation.GetMapping;
import org.springframework.web.bind.annotation.PathVariable;
import org.springframework.web.bind.annotation.PostMapping;
import org.springframework.web.bind.annotation.RequestBody;
import org.springframework.web.bind.annotation.RequestMapping;
import org.springframework.web.bind.annotation.RequestParam;
import org.springframework.web.bind.annotation.RestController;
import org.springframework.web.multipart.MultipartFile;

import java.io.IOException;
import java.util.Arrays;
import java.util.List;
import java.util.Map;
import java.util.stream.Collectors;

@RestController
@RequestMapping("/api/admin/mail-template")
public class AdminMailTemplateController {

    @Autowired
    private MailTemplateFileService mailTemplateFileService;
    @Autowired
    private MailTemplatePreviewService mailTemplatePreviewService;
    @Autowired
    private MailTemplatePreviewSampleProvider mailTemplatePreviewSampleProvider;

    @GetMapping("/catalog")
    public ResponseVO<List<MailTemplateCatalogItemDTO>> catalog() {
        List<MailTemplateCatalogItemDTO> list = Arrays.stream(MailTemplateKind.values())
                .map(k -> new MailTemplateCatalogItemDTO(
                        k.getKey(),
                        k.getTitle(),
                        k.getCategory(),
                        k.getPlaceholderHint(),
                        k.getDescription(),
                        mailTemplatePreviewSampleProvider.previewSamples(k)))
                .collect(Collectors.toList());
        return ResponseVO.success(list);
    }

    /**
     * 预览已保存的磁盘 HTML；占位符使用示例值（合并站点配置）并可被请求体覆盖。
     */
    @PostMapping("/{templateKey}/preview")
    public ResponseVO<MailTemplatePreviewDTO> preview(
            @PathVariable String templateKey,
            @RequestBody(required = false) Map<String, String> variableOverrides) {
        return ResponseVO.success(mailTemplatePreviewService.preview(templateKey, variableOverrides));
    }

    @GetMapping("/{templateKey}/status")
    public ResponseVO<MailTemplateHtmlStatusDTO> status(@PathVariable String templateKey) {
        mailTemplateFileService.assertValidTemplateKey(templateKey);
        boolean has = mailTemplateFileService.htmlExists(templateKey);
        Long lm = mailTemplateFileService.htmlLastModifiedMillis(templateKey);
        return ResponseVO.success(new MailTemplateHtmlStatusDTO(
                has,
                mailTemplateFileService.htmlSizeBytes(templateKey),
                lm));
    }

    @PostMapping(value = "/{templateKey}/html", consumes = MediaType.MULTIPART_FORM_DATA_VALUE)
    public ResponseVO<Void> uploadHtml(
            @PathVariable String templateKey,
            @RequestParam("file") MultipartFile file) throws IOException {
        mailTemplateFileService.saveHtml(templateKey, file);
        return ResponseVO.success(null);
    }

    @DeleteMapping("/{templateKey}/html")
    public ResponseVO<Void> deleteHtml(@PathVariable String templateKey) {
        mailTemplateFileService.deleteHtml(templateKey);
        return ResponseVO.success(null);
    }
}
