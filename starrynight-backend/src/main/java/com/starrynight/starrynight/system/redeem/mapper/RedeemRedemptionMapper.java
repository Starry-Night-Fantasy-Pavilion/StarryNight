package com.starrynight.starrynight.system.redeem.mapper;

import com.baomidou.mybatisplus.core.mapper.BaseMapper;
import com.starrynight.starrynight.system.redeem.entity.RedeemRedemption;
import org.apache.ibatis.annotations.Mapper;
import org.apache.ibatis.annotations.Param;
import org.apache.ibatis.annotations.Select;

@Mapper
public interface RedeemRedemptionMapper extends BaseMapper<RedeemRedemption> {

    @Select("SELECT COUNT(1) FROM redeem_redemption WHERE redeem_code_id = #{codeId} AND user_id = #{userId}")
    long countByCodeAndUser(@Param("codeId") Long codeId, @Param("userId") Long userId);
}
