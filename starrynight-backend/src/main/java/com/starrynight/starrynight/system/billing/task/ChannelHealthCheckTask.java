package com.starrynight.starrynight.system.billing.task;

import com.starrynight.starrynight.system.billing.entity.BillingChannel;
import com.starrynight.starrynight.system.billing.mapper.BillingChannelMapper;
import lombok.RequiredArgsConstructor;
import lombok.extern.slf4j.Slf4j;
import org.springframework.context.annotation.DependsOn;
import org.springframework.scheduling.annotation.Scheduled;
import org.springframework.stereotype.Component;

import java.time.LocalDateTime;
import java.util.List;

@Slf4j
@Component
@DependsOn("billingChannelSchemaCompat")
@RequiredArgsConstructor
public class ChannelHealthCheckTask {

    private final BillingChannelMapper channelMapper;

    private static final int CIRCUIT_BREAK_DURATION_MINUTES = 5;
    private static final int HALF_OPEN_SUCCESS_THRESHOLD = 5;
    private static final double HALF_OPEN_SUCCESS_RATE = 0.8;

    @Scheduled(fixedRate = 60000)
    public void checkChannelHealth() {
        List<BillingChannel> brokenChannels = channelMapper.selectList(
                new com.baomidou.mybatisplus.core.conditions.query.LambdaQueryWrapper<BillingChannel>()
                        .eq(BillingChannel::getStatus, "CIRCUIT_BROKEN")
                        .eq(BillingChannel::getDeleted, 0)
        );

        for (BillingChannel channel : brokenChannels) {
            if (channel.getCircuitOpenTime() != null) {
                LocalDateTime breakOpenTime = channel.getCircuitOpenTime();
                LocalDateTime halfOpenTime = breakOpenTime.plusMinutes(CIRCUIT_BREAK_DURATION_MINUTES);

                if (LocalDateTime.now().isAfter(halfOpenTime)) {
                    channel.setStatus("HALF_OPEN");
                    channelMapper.updateById(channel);
                    log.info("Channel {} entering half-open state for testing", channel.getId());
                }
            }
        }
    }
}
