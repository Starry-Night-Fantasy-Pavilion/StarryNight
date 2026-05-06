package com.starrynight.starrynight.system.order.controller;

import com.baomidou.mybatisplus.extension.plugins.pagination.Page;
import com.starrynight.starrynight.framework.common.vo.PageVO;
import com.starrynight.starrynight.framework.common.vo.ResponseVO;
import com.starrynight.starrynight.framework.common.util.CsvUtil;
import com.starrynight.starrynight.system.order.dto.AdminOrderDTO;
import com.starrynight.starrynight.system.order.dto.OrderStatusUpdateDTO;
import com.starrynight.starrynight.system.order.service.AdminOrderService;
import jakarta.validation.Valid;
import org.springframework.beans.factory.annotation.Autowired;
import org.springframework.security.access.prepost.PreAuthorize;
import org.springframework.web.bind.annotation.*;

import jakarta.servlet.http.HttpServletResponse;
import java.nio.charset.StandardCharsets;
import java.time.format.DateTimeFormatter;
import java.util.List;

@RestController
@RequestMapping("/api/admin/orders")
@PreAuthorize("hasRole('ADMIN')")
public class AdminOrderController {

    @Autowired
    private AdminOrderService adminOrderService;

    @GetMapping("/list")
    public ResponseVO<PageVO<AdminOrderDTO>> list(
            @RequestParam(required = false) String keyword,
            @RequestParam(required = false) Integer status,
            @RequestParam(defaultValue = "1") int page,
            @RequestParam(defaultValue = "10") int size) {
        Page<AdminOrderDTO> pageData = adminOrderService.list(keyword, status, page, size);
        return ResponseVO.success(PageVO.of(
                pageData.getTotal(),
                pageData.getRecords(),
                pageData.getCurrent(),
                pageData.getSize()
        ));
    }

    @GetMapping("/{id}")
    public ResponseVO<AdminOrderDTO> get(@PathVariable Long id) {
        return ResponseVO.success(adminOrderService.getById(id));
    }

    @PutMapping("/{id}/status")
    public ResponseVO<Void> updateStatus(@PathVariable Long id, @Valid @RequestBody OrderStatusUpdateDTO dto) {
        adminOrderService.updateStatus(id, dto.getStatus());
        return ResponseVO.success();
    }

    @GetMapping("/export")
    public void export(
            @RequestParam(required = false) String keyword,
            @RequestParam(required = false) Integer status,
            @RequestParam(defaultValue = "2000") int limit,
            HttpServletResponse response) throws Exception {
        List<AdminOrderDTO> rows = adminOrderService.export(keyword, status, limit);
        DateTimeFormatter dtf = DateTimeFormatter.ofPattern("yyyy-MM-dd HH:mm:ss");

        StringBuilder sb = new StringBuilder();
        sb.append("订单号,用户,商品,金额,状态,支付时间,创建时间\n");
        for (AdminOrderDTO row : rows) {
            String payTime = row.getPayTime() == null ? "" : dtf.format(row.getPayTime());
            String createTime = row.getCreateTime() == null ? "" : dtf.format(row.getCreateTime());
            sb.append(CsvUtil.escape(row.getOrderNo())).append(',')
                    .append(CsvUtil.escape(row.getUsername())).append(',')
                    .append(CsvUtil.escape(row.getProductName())).append(',')
                    .append(CsvUtil.escape(row.getAmount() == null ? "" : row.getAmount().toPlainString())).append(',')
                    .append(CsvUtil.escape(String.valueOf(row.getStatus()))).append(',')
                    .append(CsvUtil.escape(payTime)).append(',')
                    .append(CsvUtil.escape(createTime)).append('\n');
        }

        byte[] bytes = sb.toString().getBytes(StandardCharsets.UTF_8);
        response.setCharacterEncoding("UTF-8");
        response.setContentType("text/csv; charset=UTF-8");
        response.setHeader("Content-Disposition", "attachment; filename=\"orders.csv\"");
        response.getOutputStream().write(bytes);
    }
}
