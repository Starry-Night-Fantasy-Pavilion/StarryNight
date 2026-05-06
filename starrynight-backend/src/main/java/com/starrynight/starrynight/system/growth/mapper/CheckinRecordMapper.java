package com.starrynight.starrynight.system.growth.mapper;

import com.baomidou.mybatisplus.core.mapper.BaseMapper;
import com.starrynight.starrynight.system.growth.entity.CheckinRecord;
import org.apache.ibatis.annotations.Mapper;
import org.apache.ibatis.annotations.Param;
import org.apache.ibatis.annotations.Select;

import java.time.LocalDate;
import java.util.List;

@Mapper
public interface CheckinRecordMapper extends BaseMapper<CheckinRecord> {

    @Select("SELECT * FROM checkin_record WHERE user_id = #{userId} AND checkin_date = #{date}")
    CheckinRecord findByUserIdAndDate(@Param("userId") Long userId, @Param("date") LocalDate date);

    @Select("SELECT * FROM checkin_record WHERE user_id = #{userId} ORDER BY checkin_date DESC LIMIT #{limit}")
    List<CheckinRecord> findRecentByUserId(@Param("userId") Long userId, @Param("limit") int limit);

    @Select("SELECT MAX(checkin_date) FROM checkin_record WHERE user_id = #{userId}")
    LocalDate findLastCheckinDate(@Param("userId") Long userId);

    @Select("SELECT COUNT(*) FROM checkin_record WHERE user_id = #{userId}")
    Integer countTotalCheckins(@Param("userId") Long userId);

    @Select("SELECT MAX(continuous_days) FROM checkin_record WHERE user_id = #{userId}")
    Integer findMaxContinuousDays(@Param("userId") Long userId);
}
