package com.starrynight.starrynight.system.bookstore.dto;

import lombok.Data;

@Data
public class BookstoreLegadoImportUrlDTO {

    /** 书源集合 JSON 地址，如 yiove import 链接 */
    private String url;
}
