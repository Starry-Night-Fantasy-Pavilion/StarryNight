package com.starrynight.starrynight.system.bookstore.entity;

import com.baomidou.mybatisplus.annotation.FieldFill;
import com.baomidou.mybatisplus.annotation.IdType;
import com.baomidou.mybatisplus.annotation.TableField;
import com.baomidou.mybatisplus.annotation.TableId;
import com.baomidou.mybatisplus.annotation.TableName;
import lombok.Data;

import java.math.BigDecimal;
import java.time.LocalDateTime;

@Data
@TableName("bookstore_book")
public class BookstoreBook {

    @TableId(type = IdType.AUTO)
    private Long id;

    private String title;

    private String author;

    private String coverUrl;

    private String intro;

    private Long categoryId;

    private Integer isVip;

    private BigDecimal rating;

    private Integer wordCount;

    private Long readCount;

    private Integer sortOrder;

    private Integer status;

    private String tags;

    /** 书源详情或目录页 URL */
    private String sourceUrl;

    /** 书源规则 JSON */
    private String sourceJson;

    @TableField(fill = FieldFill.INSERT)
    private LocalDateTime createTime;

    @TableField(fill = FieldFill.INSERT_UPDATE)
    private LocalDateTime updateTime;
}
