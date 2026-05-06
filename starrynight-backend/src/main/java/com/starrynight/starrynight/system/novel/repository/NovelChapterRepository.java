package com.starrynight.starrynight.system.novel.repository;

import com.baomidou.mybatisplus.core.mapper.BaseMapper;
import com.starrynight.starrynight.system.novel.entity.NovelChapter;
import org.apache.ibatis.annotations.Mapper;

@Mapper
public interface NovelChapterRepository extends BaseMapper<NovelChapter> {
}

