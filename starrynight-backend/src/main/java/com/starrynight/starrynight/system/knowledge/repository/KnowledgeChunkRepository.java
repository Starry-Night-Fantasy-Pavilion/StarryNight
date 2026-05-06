package com.starrynight.starrynight.system.knowledge.repository;

import com.baomidou.mybatisplus.core.mapper.BaseMapper;
import com.starrynight.starrynight.system.knowledge.entity.KnowledgeChunk;
import org.apache.ibatis.annotations.Mapper;

@Mapper
public interface KnowledgeChunkRepository extends BaseMapper<KnowledgeChunk> {
}