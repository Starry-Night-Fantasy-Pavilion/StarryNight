package com.starrynight.starrynight.system.dashboard.service;

import com.baomidou.mybatisplus.core.conditions.query.LambdaQueryWrapper;
import com.starrynight.starrynight.system.announcement.entity.Announcement;
import com.starrynight.starrynight.system.announcement.repository.AnnouncementRepository;
import com.starrynight.starrynight.system.auth.entity.AuthUser;
import com.starrynight.starrynight.system.auth.repository.AuthUserRepository;
import com.starrynight.starrynight.system.dashboard.dto.AdminDashboardStatsDTO;
import com.starrynight.starrynight.system.novel.entity.Novel;
import com.starrynight.starrynight.system.novel.repository.NovelRepository;
import com.starrynight.starrynight.system.order.entity.TradeOrder;
import com.starrynight.starrynight.system.order.repository.TradeOrderRepository;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.stereotype.Service;

import java.time.LocalDateTime;
import java.time.format.DateTimeFormatter;
import java.util.*;
import java.util.stream.Collectors;

@Service
public class AdminDashboardService {

    @Autowired
    private AuthUserRepository authUserRepository;
    @Autowired
    private NovelRepository novelRepository;
    @Autowired
    private TradeOrderRepository tradeOrderRepository;
    @Autowired
    private AnnouncementRepository announcementRepository;

    public AdminDashboardStatsDTO getStats() {
        AdminDashboardStatsDTO dto = new AdminDashboardStatsDTO();
        dto.setTotalUsers(authUserRepository.selectCount(
                new LambdaQueryWrapper<AuthUser>().eq(AuthUser::getDeleted, 0)
        ));
        dto.setActiveUsers(authUserRepository.selectCount(
                new LambdaQueryWrapper<AuthUser>().eq(AuthUser::getDeleted, 0).eq(AuthUser::getStatus, 1)
        ));
        dto.setTotalNovels(novelRepository.selectCount(
                new LambdaQueryWrapper<Novel>().eq(Novel::getIsDeleted, 0)
        ));
        dto.setTotalOrders(tradeOrderRepository.selectCount(
                new LambdaQueryWrapper<TradeOrder>().eq(TradeOrder::getDeleted, 0)
        ));
        dto.setPendingOrders(tradeOrderRepository.selectCount(
                new LambdaQueryWrapper<TradeOrder>().eq(TradeOrder::getDeleted, 0).eq(TradeOrder::getStatus, 0)
        ));
        dto.setTotalAnnouncements(announcementRepository.selectCount(
                new LambdaQueryWrapper<Announcement>().eq(Announcement::getDeleted, 0).eq(Announcement::getStatus, 1)
        ));

        dto.setUserGrowthTrend(getUserGrowthTrend());
        dto.setOrderTrend(getOrderTrend());
        dto.setNovelCategoryDistribution(getNovelCategoryDistribution());
        dto.setRevenueTrend(getRevenueTrend());

        return dto;
    }

    private List<Map<String, Object>> getUserGrowthTrend() {
        List<Map<String, Object>> trend = new ArrayList<>();
        LocalDateTime now = LocalDateTime.now();

        for (int i = 5; i >= 0; i--) {
            Map<String, Object> monthData = new LinkedHashMap<>();
            LocalDateTime monthStart = now.minusMonths(i).withDayOfMonth(1).withHour(0).withMinute(0).withSecond(0);
            String monthLabel = monthStart.format(DateTimeFormatter.ofPattern("yyyy-MM"));

            Long count = authUserRepository.selectCount(
                    new LambdaQueryWrapper<AuthUser>()
                            .eq(AuthUser::getDeleted, 0)
                            .ge(AuthUser::getCreateTime, monthStart)
            );

            monthData.put("month", monthLabel);
            monthData.put("count", count != null ? count : 0);
            trend.add(monthData);
        }

        return trend;
    }

    private List<Map<String, Object>> getOrderTrend() {
        List<Map<String, Object>> trend = new ArrayList<>();
        LocalDateTime now = LocalDateTime.now();

        for (int i = 5; i >= 0; i--) {
            Map<String, Object> monthData = new LinkedHashMap<>();
            LocalDateTime monthStart = now.minusMonths(i).withDayOfMonth(1).withHour(0).withMinute(0).withSecond(0);
            String monthLabel = monthStart.format(DateTimeFormatter.ofPattern("yyyy-MM"));

            Long count = tradeOrderRepository.selectCount(
                    new LambdaQueryWrapper<TradeOrder>()
                            .eq(TradeOrder::getDeleted, 0)
                            .ge(TradeOrder::getCreateTime, monthStart)
            );

            monthData.put("month", monthLabel);
            monthData.put("count", count != null ? count : 0);
            trend.add(monthData);
        }

        return trend;
    }

    private List<Map<String, Object>> getNovelCategoryDistribution() {
        List<Map<String, Object>> distribution = new ArrayList<>();

        String[] categories = {"玄幻", "都市", "科幻", "武侠", "言情", "悬疑", "历史", "其他"};
        for (String category : categories) {
            Map<String, Object> catData = new LinkedHashMap<>();
            Long count = novelRepository.selectCount(
                    new LambdaQueryWrapper<Novel>()
                            .eq(Novel::getIsDeleted, 0)
                            .eq(Novel::getGenre, category)
            );
            catData.put("category", category);
            catData.put("count", count != null ? count : 0);
            distribution.add(catData);
        }

        return distribution;
    }

    private List<Map<String, Object>> getRevenueTrend() {
        List<Map<String, Object>> trend = new ArrayList<>();
        LocalDateTime now = LocalDateTime.now();

        for (int i = 5; i >= 0; i--) {
            Map<String, Object> monthData = new LinkedHashMap<>();
            LocalDateTime monthStart = now.minusMonths(i).withDayOfMonth(1).withHour(0).withMinute(0).withSecond(0);
            String monthLabel = monthStart.format(DateTimeFormatter.ofPattern("yyyy-MM"));

            List<TradeOrder> orders = tradeOrderRepository.selectList(
                    new LambdaQueryWrapper<TradeOrder>()
                            .eq(TradeOrder::getDeleted, 0)
                            .eq(TradeOrder::getStatus, 1)
                            .ge(TradeOrder::getCreateTime, monthStart)
            );

            double revenue = orders.stream()
                    .filter(o -> o.getAmount() != null)
                    .mapToDouble(o -> o.getAmount().doubleValue())
                    .sum();

            monthData.put("month", monthLabel);
            monthData.put("revenue", revenue);
            trend.add(monthData);
        }

        return trend;
    }
}
