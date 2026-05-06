package com.starrynight.starrynight.system.order.repository;

import com.baomidou.mybatisplus.core.mapper.BaseMapper;
import com.starrynight.starrynight.system.order.entity.TradeOrder;
import org.apache.ibatis.annotations.Mapper;

@Mapper
public interface TradeOrderRepository extends BaseMapper<TradeOrder> {
}
