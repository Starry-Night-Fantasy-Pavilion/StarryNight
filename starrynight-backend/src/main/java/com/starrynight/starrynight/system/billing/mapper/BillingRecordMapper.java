package com.starrynight.starrynight.system.billing.mapper;

import com.baomidou.mybatisplus.core.mapper.BaseMapper;
import com.starrynight.starrynight.system.billing.entity.BillingRecord;
import org.apache.ibatis.annotations.Mapper;
import org.apache.ibatis.annotations.Param;
import org.apache.ibatis.annotations.Select;

import java.math.BigDecimal;
import java.time.LocalDateTime;
import java.util.List;

@Mapper
public interface BillingRecordMapper extends BaseMapper<BillingRecord> {

    @Select("SELECT SUM(channel_cost) FROM billing_record WHERE DATE(create_time) = CURDATE()")
    BigDecimal selectTodayChannelCost();

    @Select("SELECT SUM(user_price) FROM billing_record WHERE DATE(create_time) = CURDATE()")
    BigDecimal selectTodayRevenue();

    @Select("SELECT SUM(free_points_used) FROM billing_record WHERE user_id = #{userId} AND DATE(create_time) = CURDATE()")
    Long selectTodayFreePointsUsed(@Param("userId") Long userId);

    @Select("SELECT * FROM billing_record WHERE user_id = #{userId} ORDER BY create_time DESC LIMIT #{limit}")
    List<BillingRecord> selectRecentByUserId(@Param("userId") Long userId, @Param("limit") int limit);
}
