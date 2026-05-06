package com.starrynight.starrynight.system.auth.entity;

import com.baomidou.mybatisplus.annotation.*;
import lombok.Data;

import java.time.LocalDateTime;

@Data
@TableName("auth_user")
public class AuthUser {

    @TableId(type = IdType.AUTO)
    private Long id;

    private String username;

    private String password;

    private String email;

    private String phone;

    /** 实名（仅当运营开启注册实名时写入） */
    private String realName;

    /** 证件号（当前为大陆 18 位身份证校验） */
    private String idCardNo;

    /** 0 未通过人脸/三方核验；1 已通过 */
    private Integer realNameVerified;

    /** 最近一次核验外部单号（支付宝 certify_id、Ovooa 流水等） */
    private String realNameVerifyOuterNo;

    /** 易支付实名认证费已付本地单号（{@code recharge_record.record_no}，{@code pay_method=REALNAME_FEE}） */
    private String realnameFeePaidRecordNo;

    private String avatar;

    /** 前台用户首次自助注册时的客户端 IP（运营后台创建可为空） */
    private String registerIp;

    private LocalDateTime lastLoginTime;

    private String lastLoginIp;

    private Integer status;

    private Integer isAdmin;

    @TableField(fill = FieldFill.INSERT)
    private LocalDateTime createTime;

    @TableField(fill = FieldFill.INSERT_UPDATE)
    private LocalDateTime updateTime;

    @TableLogic
    private Integer deleted;
}

