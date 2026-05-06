package com.starrynight.starrynight.system.dashboard.dto;

import lombok.Data;
import java.util.List;
import java.util.Map;

@Data
public class AdminDashboardStatsDTO {

    private Long totalUsers;

    private Long totalNovels;

    private Long totalOrders;

    private Long totalAnnouncements;

    private Long pendingOrders;

    private Long activeUsers;

    private List<Map<String, Object>> userGrowthTrend;

    private List<Map<String, Object>> orderTrend;

    private List<Map<String, Object>> novelCategoryDistribution;

    private List<Map<String, Object>> revenueTrend;
}
