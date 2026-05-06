package com.starrynight.starrynight.system.bookstore.dto;

import lombok.AllArgsConstructor;
import lombok.Builder;
import lombok.Data;
import lombok.NoArgsConstructor;

@Data
@Builder
@NoArgsConstructor
@AllArgsConstructor
public class BookstoreLegadoSourceAdminDTO {

    private Long id;

    private String bookSourceName;

    private String bookSourceUrl;

    private String bookSourceGroup;

    private Integer enabled;

    /** 从 JSON 解析：是否有搜索规则 */
    private Boolean hasRuleSearch;

    /** 是否有目录规则（含 chapterList 等） */
    private Boolean hasRuleToc;

    /** 是否有正文规则 */
    private Boolean hasRuleContent;

    /** bookSourceComment 前若干字 */
    private String commentSnippet;
}
