package com.starrynight.starrynight.system.redeem.mapper;

import com.baomidou.mybatisplus.core.mapper.BaseMapper;
import com.starrynight.starrynight.system.redeem.entity.RedeemCode;
import org.apache.ibatis.annotations.Mapper;
import org.apache.ibatis.annotations.Param;
import org.apache.ibatis.annotations.Select;

@Mapper
public interface RedeemCodeMapper extends BaseMapper<RedeemCode> {

    @Select("SELECT * FROM redeem_code WHERE code = #{code} FOR UPDATE")
    RedeemCode selectByCodeForUpdate(@Param("code") String code);
}
