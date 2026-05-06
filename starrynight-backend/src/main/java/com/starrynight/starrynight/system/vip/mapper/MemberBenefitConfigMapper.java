package com.starrynight.starrynight.system.vip.mapper;

import com.baomidou.mybatisplus.core.mapper.BaseMapper;
import com.starrynight.starrynight.system.vip.entity.MemberBenefitConfig;
import org.apache.ibatis.annotations.Mapper;
import org.apache.ibatis.annotations.Param;
import org.apache.ibatis.annotations.Select;

import java.util.List;

@Mapper
public interface MemberBenefitConfigMapper extends BaseMapper<MemberBenefitConfig> {

    @Select("SELECT * FROM member_benefit_config WHERE member_level = #{memberLevel} AND enabled = 1")
    List<MemberBenefitConfig> findByMemberLevel(@Param("memberLevel") Integer memberLevel);

    @Select("SELECT * FROM member_benefit_config WHERE member_level = #{memberLevel} AND benefit_key = #{benefitKey} AND enabled = 1")
    MemberBenefitConfig findByLevelAndKey(@Param("memberLevel") Integer memberLevel, @Param("benefitKey") String benefitKey);
}
