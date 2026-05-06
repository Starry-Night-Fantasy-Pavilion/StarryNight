package com.starrynight.starrynight.system.billing.mapper;

import com.baomidou.mybatisplus.core.mapper.BaseMapper;
import com.starrynight.starrynight.system.billing.entity.BillingConfig;
import org.apache.ibatis.annotations.Mapper;
import org.apache.ibatis.annotations.Param;
import org.apache.ibatis.annotations.Select;

@Mapper
public interface BillingConfigMapper extends BaseMapper<BillingConfig> {

    @Select("SELECT config_value FROM billing_config WHERE config_key = #{configKey}")
    String selectValueByKey(@Param("configKey") String configKey);
}
