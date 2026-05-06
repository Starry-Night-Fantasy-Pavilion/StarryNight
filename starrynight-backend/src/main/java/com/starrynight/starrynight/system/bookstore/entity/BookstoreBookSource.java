package com.starrynight.starrynight.system.bookstore.entity;

import com.baomidou.mybatisplus.annotation.FieldFill;
import com.baomidou.mybatisplus.annotation.IdType;
import com.baomidou.mybatisplus.annotation.TableField;
import com.baomidou.mybatisplus.annotation.TableId;
import com.baomidou.mybatisplus.annotation.TableName;
import lombok.Data;

import java.time.LocalDateTime;

@Data
@TableName("bookstore_book_source")
public class BookstoreBookSource {

    @TableId(type = IdType.AUTO)
    private Long id;

    private String bookSourceName;

    private String bookSourceUrl;

    private String bookSourceGroup;

    private String sourceJson;

    private Integer enabled;

    private Integer sortOrder;

    @TableField(fill = FieldFill.INSERT)
    private LocalDateTime createTime;

    @TableField(fill = FieldFill.INSERT_UPDATE)
    private LocalDateTime updateTime;
}
