package com.starrynight.starrynight.system.recommendation.mapper;

import com.baomidou.mybatisplus.core.mapper.BaseMapper;
import com.starrynight.starrynight.system.recommendation.entity.Recommendation;
import org.apache.ibatis.annotations.Mapper;

@Mapper
public interface RecommendationMapper extends BaseMapper<Recommendation> {
}