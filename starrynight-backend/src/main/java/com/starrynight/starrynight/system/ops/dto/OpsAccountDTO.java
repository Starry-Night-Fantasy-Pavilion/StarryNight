package com.starrynight.starrynight.system.ops.dto;

import lombok.Data;

import java.time.LocalDateTime;

@Data
public class OpsAccountDTO {
    private Long id;
    private String username;
    private String email;
    private Long roleId;
    private String roleName;
    private Integer status;
    private LocalDateTime createTime;
}
