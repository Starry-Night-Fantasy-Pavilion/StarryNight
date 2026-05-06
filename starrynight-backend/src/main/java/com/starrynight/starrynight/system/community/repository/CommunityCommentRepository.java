package com.starrynight.starrynight.system.community.repository;

import com.baomidou.mybatisplus.core.mapper.BaseMapper;
import com.starrynight.starrynight.system.community.entity.CommunityComment;
import org.apache.ibatis.annotations.Mapper;

@Mapper
public interface CommunityCommentRepository extends BaseMapper<CommunityComment> {
}
