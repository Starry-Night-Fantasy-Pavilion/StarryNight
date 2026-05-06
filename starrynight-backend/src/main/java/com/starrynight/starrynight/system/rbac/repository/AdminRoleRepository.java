package com.starrynight.starrynight.system.rbac.repository;

import com.baomidou.mybatisplus.core.mapper.BaseMapper;
import com.starrynight.starrynight.system.rbac.entity.AdminRole;
import org.apache.ibatis.annotations.Mapper;

@Mapper
public interface AdminRoleRepository extends BaseMapper<AdminRole> {
}
