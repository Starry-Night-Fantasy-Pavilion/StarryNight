package com.starrynight.starrynight.system.rbac.dto;

import jakarta.validation.constraints.NotBlank;
import jakarta.validation.constraints.NotNull;
import lombok.Data;

import java.util.List;

@Data
public class AdminRoleDTO {

    private Long id;

    @NotBlank(message = "Role name is required")
    private String name;

    @NotBlank(message = "Role code is required")
    private String code;

    private String description;

    @NotNull(message = "Role status is required")
    private Integer status;

    private List<String> menuPermissions;

    private Integer userCount;
}
