package com.starrynight.starrynight.system.order.service;

import com.baomidou.mybatisplus.core.conditions.query.LambdaQueryWrapper;
import com.baomidou.mybatisplus.extension.plugins.pagination.Page;
import com.starrynight.starrynight.framework.common.exception.BusinessException;
import com.starrynight.starrynight.framework.common.exception.ResourceNotFoundException;
import com.starrynight.starrynight.system.auth.entity.AuthUser;
import com.starrynight.starrynight.system.auth.repository.AuthUserRepository;
import com.starrynight.starrynight.system.order.dto.AdminOrderDTO;
import com.starrynight.starrynight.system.order.entity.TradeOrder;
import com.starrynight.starrynight.system.order.repository.TradeOrderRepository;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.stereotype.Service;
import org.springframework.transaction.annotation.Transactional;

import java.time.format.DateTimeFormatter;
import java.util.Collections;
import java.util.List;
import java.util.Map;
import java.util.Set;
import java.util.function.Function;
import java.util.stream.Collectors;

@Service
public class AdminOrderService {

    @Autowired
    private TradeOrderRepository tradeOrderRepository;
    @Autowired
    private AuthUserRepository authUserRepository;

    public Page<AdminOrderDTO> list(String keyword, Integer status, int page, int size) {
        LambdaQueryWrapper<TradeOrder> wrapper = new LambdaQueryWrapper<>();
        if (status != null) {
            wrapper.eq(TradeOrder::getStatus, status);
        }
        if (keyword != null && !keyword.isBlank()) {
            wrapper.and(w -> w.like(TradeOrder::getOrderNo, keyword).or().like(TradeOrder::getProductName, keyword));
        }
        wrapper.orderByDesc(TradeOrder::getCreateTime);

        Page<TradeOrder> orderPage = tradeOrderRepository.selectPage(new Page<>(page, size), wrapper);
        List<TradeOrder> records = orderPage.getRecords();
        Set<Long> userIds = records.stream().map(TradeOrder::getUserId).collect(Collectors.toSet());

        Map<Long, AuthUser> userMap;
        if (userIds.isEmpty()) {
            userMap = Collections.emptyMap();
        } else {
            userMap = authUserRepository.selectList(
                    new LambdaQueryWrapper<AuthUser>().in(AuthUser::getId, userIds).eq(AuthUser::getDeleted, 0)
            ).stream().collect(Collectors.toMap(AuthUser::getId, Function.identity(), (a, b) -> a));
        }

        List<AdminOrderDTO> dtoRecords = records.stream().map(order -> {
            AdminOrderDTO dto = new AdminOrderDTO();
            dto.setId(order.getId());
            dto.setOrderNo(order.getOrderNo());
            dto.setUserId(order.getUserId());
            dto.setProductName(order.getProductName());
            dto.setAmount(order.getAmount());
            dto.setStatus(order.getStatus());
            dto.setPayTime(order.getPayTime());
            dto.setCreateTime(order.getCreateTime());
            dto.setUsername(userMap.containsKey(order.getUserId()) ? userMap.get(order.getUserId()).getUsername() : "-");
            return dto;
        }).collect(Collectors.toList());

        Page<AdminOrderDTO> result = new Page<>(orderPage.getCurrent(), orderPage.getSize(), orderPage.getTotal());
        result.setRecords(dtoRecords);
        return result;
    }

    public AdminOrderDTO getById(Long id) {
        TradeOrder order = tradeOrderRepository.selectById(id);
        if (order == null) {
            throw new ResourceNotFoundException("Order not found");
        }
        AuthUser user = authUserRepository.selectById(order.getUserId());
        AdminOrderDTO dto = new AdminOrderDTO();
        dto.setId(order.getId());
        dto.setOrderNo(order.getOrderNo());
        dto.setUserId(order.getUserId());
        dto.setUsername(user != null ? user.getUsername() : "-");
        dto.setProductName(order.getProductName());
        dto.setAmount(order.getAmount());
        dto.setStatus(order.getStatus());
        dto.setPayTime(order.getPayTime());
        dto.setCreateTime(order.getCreateTime());
        return dto;
    }

    @Transactional
    public void updateStatus(Long id, Integer status) {
        if (status == null || status < 0 || status > 3) {
            throw new BusinessException("Invalid order status");
        }
        TradeOrder order = tradeOrderRepository.selectById(id);
        if (order == null) {
            throw new ResourceNotFoundException("Order not found");
        }
        order.setStatus(status);
        tradeOrderRepository.updateById(order);
    }

    public List<AdminOrderDTO> export(String keyword, Integer status, int limit) {
        int page = 1;
        int size = Math.max(1, Math.min(limit, 5000));
        return list(keyword, status, page, size).getRecords();
    }
}
