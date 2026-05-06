package com.starrynight.starrynight.system.consistency.mapper;

import com.baomidou.mybatisplus.core.mapper.BaseMapper;
import com.starrynight.starrynight.system.consistency.entity.CharacterConsistency;
import org.apache.ibatis.annotations.Mapper;
import org.apache.ibatis.annotations.Param;
import org.apache.ibatis.annotations.Select;

import java.util.List;

@Mapper
public interface CharacterConsistencyMapper extends BaseMapper<CharacterConsistency> {

    @Select("SELECT * FROM character_consistency WHERE novel_id = #{novelId} AND check_result != 'pass' ORDER BY create_time DESC")
    List<CharacterConsistency> findIssuesByNovelId(@Param("novelId") Long novelId);

    @Select("SELECT * FROM character_consistency WHERE character_id = #{characterId} ORDER BY create_time DESC")
    List<CharacterConsistency> findByCharacterId(@Param("characterId") Long characterId);
}
