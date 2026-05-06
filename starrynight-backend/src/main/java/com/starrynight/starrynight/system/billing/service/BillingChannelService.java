package com.starrynight.starrynight.system.billing.service;

import com.baomidou.mybatisplus.core.conditions.query.LambdaQueryWrapper;
import com.starrynight.starrynight.system.billing.dto.ChannelDTO;
import com.starrynight.starrynight.system.billing.entity.BillingChannel;
import com.starrynight.starrynight.system.billing.mapper.BillingChannelMapper;
import lombok.RequiredArgsConstructor;
import lombok.extern.slf4j.Slf4j;
import org.springframework.stereotype.Service;
import org.springframework.transaction.annotation.Transactional;

import java.util.List;
import java.util.stream.Collectors;

@Slf4j
@Service
@RequiredArgsConstructor
public class BillingChannelService {

    private final BillingChannelMapper channelMapper;

    public List<ChannelDTO> listChannels(String type, Boolean enabled) {
        LambdaQueryWrapper<BillingChannel> query = new LambdaQueryWrapper<>();
        if (type != null && !type.isEmpty()) {
            query.eq(BillingChannel::getChannelType, type);
        }
        if (enabled != null) {
            query.eq(BillingChannel::getEnabled, enabled ? 1 : 0);
        }
        query.eq(BillingChannel::getDeleted, 0);
        query.orderByAsc(BillingChannel::getSortOrder);

        List<BillingChannel> channels = channelMapper.selectList(query);
        return channels.stream().map(this::toDTO).collect(Collectors.toList());
    }

    public ChannelDTO getChannel(Long id) {
        BillingChannel channel = channelMapper.selectById(id);
        return channel != null ? toDTO(channel) : null;
    }

    @Transactional(rollbackFor = Exception.class)
    public ChannelDTO createChannel(ChannelDTO dto) {
        BillingChannel channel = toEntity(dto);
        channelMapper.insert(channel);
        return toDTO(channel);
    }

    @Transactional(rollbackFor = Exception.class)
    public ChannelDTO updateChannel(Long id, ChannelDTO dto) {
        BillingChannel channel = channelMapper.selectById(id);
        if (channel == null) {
            throw new RuntimeException("Channel not found");
        }

        channel.setChannelName(dto.getChannelName());
        channel.setChannelType(dto.getChannelType());
        channel.setApiBaseUrl(dto.getApiBaseUrl());
        channel.setApiKey(dto.getApiKey());
        channel.setModelName(dto.getModelName());
        channel.setCostPer1kInput(dto.getCostPer1kInput());
        channel.setCostPer1kOutput(dto.getCostPer1kOutput());
        channel.setCostPerCall(dto.getCostPerCall());
        channel.setCostPerSecond(dto.getCostPerSecond());
        channel.setBaseCost(dto.getBaseCost());
        channel.setIsFree(dto.getIsFree() != null && dto.getIsFree() ? 1 : 0);
        channel.setEnabled(dto.getEnabled() == null || dto.getEnabled() != 0 ? 1 : 0);
        channel.setSortOrder(dto.getSortOrder() != null ? dto.getSortOrder() : 0);

        channelMapper.updateById(channel);
        return toDTO(channel);
    }

    @Transactional(rollbackFor = Exception.class)
    public void deleteChannel(Long id) {
        BillingChannel channel = channelMapper.selectById(id);
        if (channel != null) {
            channel.setDeleted(1);
            channelMapper.updateById(channel);
        }
    }

    @Transactional(rollbackFor = Exception.class)
    public void enableChannel(Long id) {
        BillingChannel channel = channelMapper.selectById(id);
        if (channel != null) {
            channel.setEnabled(1);
            channel.setStatus("NORMAL");
            channel.setFailureCount(0);
            channelMapper.updateById(channel);
        }
    }

    @Transactional(rollbackFor = Exception.class)
    public void disableChannel(Long id) {
        BillingChannel channel = channelMapper.selectById(id);
        if (channel != null) {
            channel.setEnabled(0);
            channelMapper.updateById(channel);
        }
    }

    public void recordChannelFailure(Long id) {
        BillingChannel channel = channelMapper.selectById(id);
        if (channel != null) {
            channel.setFailureCount(channel.getFailureCount() + 1);
            channel.setLastFailureTime(java.time.LocalDateTime.now());

            if (channel.getFailureCount() >= 10) {
                channel.setStatus("CIRCUIT_BROKEN");
                channel.setCircuitOpenTime(java.time.LocalDateTime.now());
                log.warn("Channel {} circuit broken due to {} consecutive failures", id, channel.getFailureCount());
            } else if (channel.getFailureCount() >= 5) {
                channel.setStatus("WARNING");
            }

            channelMapper.updateById(channel);
        }
    }

    public void recordChannelSuccess(Long id) {
        BillingChannel channel = channelMapper.selectById(id);
        if (channel != null) {
            channel.setFailureCount(0);
            channel.setStatus("NORMAL");
            channelMapper.updateById(channel);
        }
    }

    private ChannelDTO toDTO(BillingChannel channel) {
        ChannelDTO dto = new ChannelDTO();
        dto.setId(channel.getId());
        dto.setChannelCode(channel.getChannelCode());
        dto.setChannelName(channel.getChannelName());
        dto.setChannelType(channel.getChannelType());
        dto.setApiBaseUrl(channel.getApiBaseUrl());
        dto.setApiKey(channel.getApiKey());
        dto.setModelName(channel.getModelName());
        dto.setCostPer1kInput(channel.getCostPer1kInput());
        dto.setCostPer1kOutput(channel.getCostPer1kOutput());
        dto.setCostPerCall(channel.getCostPerCall());
        dto.setCostPerSecond(channel.getCostPerSecond());
        dto.setBaseCost(channel.getBaseCost());
        dto.setIsFree(channel.getIsFree() == 1);
        dto.setStatus(channel.getStatus());
        dto.setEnabled(channel.getEnabled());
        dto.setSortOrder(channel.getSortOrder());
        return dto;
    }

    private BillingChannel toEntity(ChannelDTO dto) {
        BillingChannel channel = new BillingChannel();
        channel.setChannelCode(dto.getChannelCode());
        channel.setChannelName(dto.getChannelName());
        channel.setChannelType(dto.getChannelType() != null ? dto.getChannelType() : "token");
        channel.setApiBaseUrl(dto.getApiBaseUrl());
        channel.setApiKey(dto.getApiKey());
        channel.setModelName(dto.getModelName());
        channel.setCostPer1kInput(dto.getCostPer1kInput() != null ? dto.getCostPer1kInput() : new java.math.BigDecimal("0"));
        channel.setCostPer1kOutput(dto.getCostPer1kOutput() != null ? dto.getCostPer1kOutput() : new java.math.BigDecimal("0"));
        channel.setCostPerCall(dto.getCostPerCall() != null ? dto.getCostPerCall() : new java.math.BigDecimal("0"));
        channel.setCostPerSecond(dto.getCostPerSecond() != null ? dto.getCostPerSecond() : new java.math.BigDecimal("0"));
        channel.setBaseCost(dto.getBaseCost() != null ? dto.getBaseCost() : new java.math.BigDecimal("0"));
        channel.setIsFree(dto.getIsFree() != null && dto.getIsFree() ? 1 : 0);
        channel.setStatus("NORMAL");
        channel.setEnabled(dto.getEnabled() == null || dto.getEnabled() != 0 ? 1 : 0);
        channel.setSortOrder(dto.getSortOrder() != null ? dto.getSortOrder() : 0);
        return channel;
    }
}
