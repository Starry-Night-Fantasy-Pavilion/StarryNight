package com.starrynight.starrynight.system.announcement.repository;

import com.baomidou.mybatisplus.core.mapper.BaseMapper;
import com.starrynight.starrynight.system.announcement.entity.Announcement;
import org.apache.ibatis.annotations.Mapper;

@Mapper
public interface AnnouncementRepository extends BaseMapper<Announcement> {
}
