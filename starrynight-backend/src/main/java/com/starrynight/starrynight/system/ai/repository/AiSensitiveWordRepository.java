package com.starrynight.starrynight.system.ai.repository;

import com.baomidou.mybatisplus.core.mapper.BaseMapper;
import com.starrynight.starrynight.system.ai.entity.AiSensitiveWord;
import org.apache.ibatis.annotations.Mapper;

@Mapper
public interface AiSensitiveWordRepository extends BaseMapper<AiSensitiveWord> {
}
