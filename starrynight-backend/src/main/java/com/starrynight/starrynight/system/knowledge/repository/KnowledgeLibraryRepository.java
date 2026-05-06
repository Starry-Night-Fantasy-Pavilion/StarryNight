package com.starrynight.starrynight.system.knowledge.repository;

import com.baomidou.mybatisplus.core.mapper.BaseMapper;
import com.starrynight.starrynight.system.knowledge.entity.KnowledgeLibrary;
import org.apache.ibatis.annotations.Mapper;

@Mapper
public interface KnowledgeLibraryRepository extends BaseMapper<KnowledgeLibrary> {
}