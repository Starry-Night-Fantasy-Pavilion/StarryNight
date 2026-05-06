package com.starrynight.starrynight.system.vip.mapper;

import com.baomidou.mybatisplus.core.mapper.BaseMapper;
import com.starrynight.starrynight.system.vip.entity.MemberSubscription;
import org.apache.ibatis.annotations.Mapper;
import org.apache.ibatis.annotations.Param;
import org.apache.ibatis.annotations.Select;

import java.time.LocalDateTime;

@Mapper
public interface MemberSubscriptionMapper extends BaseMapper<MemberSubscription> {

    @Select("SELECT * FROM member_subscription WHERE user_id = #{userId} AND status = 'ACTIVE' AND expire_time > #{now} ORDER BY expire_time DESC LIMIT 1")
    MemberSubscription findActiveSubscription(@Param("userId") Long userId, @Param("now") LocalDateTime now);

    @Select("SELECT * FROM member_subscription WHERE user_id = #{userId} ORDER BY create_time DESC LIMIT 1")
    MemberSubscription findLatestByUserId(@Param("userId") Long userId);
}
