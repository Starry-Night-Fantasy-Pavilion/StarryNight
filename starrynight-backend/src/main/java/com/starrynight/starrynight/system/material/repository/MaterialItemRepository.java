package com.starrynight.starrynight.system.material.repository;

import com.baomidou.mybatisplus.core.mapper.BaseMapper;
import com.starrynight.starrynight.system.material.entity.MaterialItem;
import org.apache.ibatis.annotations.Mapper;

@Mapper
public interface MaterialItemRepository extends BaseMapper<MaterialItem> {
}