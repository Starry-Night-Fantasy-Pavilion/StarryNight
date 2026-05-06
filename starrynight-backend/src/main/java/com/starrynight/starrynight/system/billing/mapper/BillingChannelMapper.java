package com.starrynight.starrynight.system.billing.mapper;

import com.baomidou.mybatisplus.core.mapper.BaseMapper;
import com.starrynight.starrynight.system.billing.entity.BillingChannel;
import org.apache.ibatis.annotations.Mapper;
import org.apache.ibatis.annotations.Param;
import org.apache.ibatis.annotations.Select;

import java.util.List;

@Mapper
public interface BillingChannelMapper extends BaseMapper<BillingChannel> {

    @Select("SELECT * FROM billing_channel WHERE enabled = 1 AND deleted = 0 AND is_free = 1 AND status = 'NORMAL' ORDER BY sort_order ASC LIMIT 1")
    BillingChannel selectBestFreeChannel();

    @Select("SELECT * FROM billing_channel WHERE enabled = 1 AND deleted = 0 AND is_free = 0 AND status = 'NORMAL' ORDER BY sort_order ASC LIMIT 1")
    BillingChannel selectBestPaidChannel();

    @Select("SELECT * FROM billing_channel WHERE enabled = 1 AND deleted = 0 ORDER BY sort_order ASC")
    List<BillingChannel> selectAllEnabled();
}
