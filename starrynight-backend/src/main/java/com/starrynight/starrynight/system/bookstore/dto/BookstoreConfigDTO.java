package com.starrynight.starrynight.system.bookstore.dto;

import lombok.Data;

@Data
public class BookstoreConfigDTO {

    /** 运营端回显与保存：保存时 null 表示不修改 */
    private Boolean enabled;

    private String siteTitle;
}
