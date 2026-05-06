package com.starrynight.starrynight.system.character.repository;

import com.baomidou.mybatisplus.core.mapper.BaseMapper;
import com.starrynight.starrynight.system.character.entity.NovelCharacter;
import org.apache.ibatis.annotations.Mapper;

@Mapper
public interface NovelCharacterRepository extends BaseMapper<NovelCharacter> {
}