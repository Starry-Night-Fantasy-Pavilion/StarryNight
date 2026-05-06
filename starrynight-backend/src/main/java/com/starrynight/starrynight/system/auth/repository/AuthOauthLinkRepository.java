package com.starrynight.starrynight.system.auth.repository;

import com.baomidou.mybatisplus.core.mapper.BaseMapper;
import com.starrynight.starrynight.system.auth.entity.AuthOauthLink;
import org.apache.ibatis.annotations.Mapper;

@Mapper
public interface AuthOauthLinkRepository extends BaseMapper<AuthOauthLink> {
}
