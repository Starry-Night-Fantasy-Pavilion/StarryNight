package com.starrynight.starrynight.system.character.dto;

import jakarta.validation.constraints.NotBlank;
import lombok.Data;

import java.time.LocalDateTime;

@Data
public class NovelCharacterDTO {

    private Long id;

    private Long novelId;

    @NotBlank(message = "角色名称不能为空")
    private String name;

    private String identity;

    private String gender;

    private String age;

    private String appearance;

    private String background;

    private String motivation;

    private Object personality;

    private Object abilities;

    private Object relationships;

    private Object growthArc;

    private LocalDateTime createTime;

    private LocalDateTime updateTime;
}