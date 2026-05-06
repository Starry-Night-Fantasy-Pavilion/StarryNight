package com.starrynight.starrynight.system.auth.entity;

import com.baomidou.mybatisplus.annotation.IdType;
import com.baomidou.mybatisplus.annotation.TableId;
import com.baomidou.mybatisplus.annotation.TableName;
import lombok.Data;

import java.time.LocalDateTime;

@Data
@TableName("auth_oauth_link")
public class AuthOauthLink {

    @TableId(type = IdType.AUTO)
    private Long id;

    private String provider;

    private String externalId;

    private Long userId;

    private LocalDateTime createTime;
}
