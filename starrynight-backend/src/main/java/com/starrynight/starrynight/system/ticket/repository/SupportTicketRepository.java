package com.starrynight.starrynight.system.ticket.repository;

import com.baomidou.mybatisplus.core.mapper.BaseMapper;
import com.starrynight.starrynight.system.ticket.entity.SupportTicket;
import org.apache.ibatis.annotations.Mapper;

@Mapper
public interface SupportTicketRepository extends BaseMapper<SupportTicket> {
}
