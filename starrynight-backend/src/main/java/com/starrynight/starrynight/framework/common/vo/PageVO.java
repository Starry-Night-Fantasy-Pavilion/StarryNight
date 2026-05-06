package com.starrynight.starrynight.framework.common.vo;

import lombok.Data;
import java.util.List;

@Data
public class PageVO<T> {

    private Long total;
    private List<T> records;
    private Long current;
    private Long size;

    public static <T> PageVO<T> of(Long total, List<T> records, Long current, Long size) {
        PageVO<T> page = new PageVO<>();
        page.setTotal(total);
        page.setRecords(records);
        page.setCurrent(current);
        page.setSize(size);
        return page;
    }

    public static <T> PageVO<T> empty(Long current, Long size) {
        return of(0L, List.of(), current, size);
    }
}

