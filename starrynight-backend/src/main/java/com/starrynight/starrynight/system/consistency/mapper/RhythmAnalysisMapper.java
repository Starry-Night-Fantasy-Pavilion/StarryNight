package com.starrynight.starrynight.system.consistency.mapper;

import com.baomidou.mybatisplus.core.mapper.BaseMapper;
import com.starrynight.starrynight.system.consistency.entity.RhythmAnalysis;
import org.apache.ibatis.annotations.Mapper;
import org.apache.ibatis.annotations.Param;
import org.apache.ibatis.annotations.Select;

import java.util.List;

@Mapper
public interface RhythmAnalysisMapper extends BaseMapper<RhythmAnalysis> {

    @Select("SELECT * FROM rhythm_analysis WHERE novel_id = #{novelId} ORDER BY chapter_no ASC")
    List<RhythmAnalysis> findByNovelId(@Param("novelId") Long novelId);

    @Select("SELECT * FROM rhythm_analysis WHERE chapter_id = #{chapterId} AND analysis_type = #{analysisType}")
    RhythmAnalysis findByChapterAndType(@Param("chapterId") Long chapterId, @Param("analysisType") String analysisType);

    @Select("SELECT * FROM rhythm_analysis WHERE novel_id = #{novelId} ORDER BY create_time DESC LIMIT 1")
    RhythmAnalysis findLatestByNovelId(@Param("novelId") Long novelId);
}
