package com.starrynight.starrynight.system.growth.mapper;

import com.baomidou.mybatisplus.core.mapper.BaseMapper;
import com.starrynight.starrynight.system.growth.entity.CheckinConfig;
import org.apache.ibatis.annotations.Mapper;
import org.apache.ibatis.annotations.Param;
import org.apache.ibatis.annotations.Select;

@Mapper
public interface CheckinConfigMapper extends BaseMapper<CheckinConfig> {

    @Select("SELECT config_value FROM checkin_config WHERE config_key = #{configKey}")
    String getConfigValue(@Param("configKey") String configKey);

    @Select("SELECT * FROM checkin_config WHERE config_key = #{configKey}")
    CheckinConfig getConfigByKey(@Param("configKey") String configKey);
}
