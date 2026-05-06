package com.starrynight.starrynight.system.prompt.repository;

import com.baomidou.mybatisplus.core.mapper.BaseMapper;
import com.starrynight.starrynight.system.prompt.entity.PromptTemplate;
import org.apache.ibatis.annotations.Mapper;

@Mapper
public interface PromptTemplateRepository extends BaseMapper<PromptTemplate> {
}