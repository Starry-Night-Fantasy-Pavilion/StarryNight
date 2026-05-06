package com.starrynight.starrynight.system.billing.mapper;

import com.baomidou.mybatisplus.core.mapper.BaseMapper;
import com.starrynight.starrynight.system.billing.entity.BillingMarginConfig;
import org.apache.ibatis.annotations.Mapper;
import org.apache.ibatis.annotations.Param;
import org.apache.ibatis.annotations.Select;

import java.math.BigDecimal;
import java.time.LocalDateTime;
import java.util.List;

@Mapper
public interface BillingMarginConfigMapper extends BaseMapper<BillingMarginConfig> {

    @Select("SELECT * FROM billing_margin_config WHERE config_type = #{configType} AND enabled = 1 " +
            "AND (start_time IS NULL OR start_time <= #{now}) " +
            "AND (end_time IS NULL OR end_time >= #{now}) " +
            "ORDER BY priority DESC LIMIT 1")
    BillingMarginConfig selectEffectiveConfig(@Param("configType") String configType, @Param("now") LocalDateTime now);

    @Select("SELECT * FROM billing_margin_config WHERE config_type = 'content_type' AND content_type = #{contentType} AND enabled = 1 " +
            "AND (start_time IS NULL OR start_time <= #{now}) " +
            "AND (end_time IS NULL OR end_time >= #{now}) " +
            "ORDER BY priority DESC LIMIT 1")
    BillingMarginConfig selectByContentType(@Param("contentType") String contentType, @Param("now") LocalDateTime now);

    @Select("SELECT * FROM billing_margin_config WHERE config_type = 'user_group' AND user_group = #{userGroup} AND enabled = 1 " +
            "AND (start_time IS NULL OR start_time <= #{now}) " +
            "AND (end_time IS NULL OR end_time >= #{now}) " +
            "ORDER BY priority DESC LIMIT 1")
    BillingMarginConfig selectByUserGroup(@Param("userGroup") String userGroup, @Param("now") LocalDateTime now);
}
