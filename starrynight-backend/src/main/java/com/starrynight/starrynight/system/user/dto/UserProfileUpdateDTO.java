package com.starrynight.starrynight.system.user.dto;

import lombok.Data;

@Data
public class UserProfileUpdateDTO {

    private String nickname;

    private String email;

    private String phone;

    private String avatar;

    /** 真实姓名；与 idCardNo 成对提交，登录后在个人中心登记 */
    private String realName;

    /** 18 位身份证号；与 realName 成对提交 */
    private String idCardNo;
}

