package com.starrynight.starrynight.system.consistency.mapper;

import com.baomidou.mybatisplus.core.mapper.BaseMapper;
import com.starrynight.starrynight.system.consistency.entity.ForeshadowingRecord;
import org.apache.ibatis.annotations.Mapper;
import org.apache.ibatis.annotations.Param;
import org.apache.ibatis.annotations.Select;

import java.util.List;

@Mapper
public interface ForeshadowingRecordMapper extends BaseMapper<ForeshadowingRecord> {

    @Select("SELECT * FROM foreshadowing_record WHERE novel_id = #{novelId} AND status = 'pending' ORDER BY chapter_no ASC")
    List<ForeshadowingRecord> findPendingByNovelId(@Param("novelId") Long novelId);

    @Select("SELECT * FROM foreshadowing_record WHERE novel_id = #{novelId} AND status = 'paid_off' ORDER BY paid_off_chapter_no DESC")
    List<ForeshadowingRecord> findPaidOffByNovelId(@Param("novelId") Long novelId);

    @Select("SELECT * FROM foreshadowing_record WHERE novel_id = #{novelId} AND chapter_no <= #{chapterNo} AND status = 'pending'")
    List<ForeshadowingRecord> findUnpaidForeshadowingsBeforeChapter(@Param("novelId") Long novelId, @Param("chapterNo") Integer chapterNo);

    @Select("SELECT * FROM foreshadowing_record WHERE novel_id = #{novelId} AND type = #{type} AND status = 'pending'")
    List<ForeshadowingRecord> findByTypeAndStatusPending(@Param("novelId") Long novelId, @Param("type") String type);
}