package com.starrynight.starrynight.system.growth.mapper;

import com.baomidou.mybatisplus.core.mapper.BaseMapper;
import com.starrynight.starrynight.system.growth.entity.PointsTransaction;
import org.apache.ibatis.annotations.Mapper;
import org.apache.ibatis.annotations.Param;
import org.apache.ibatis.annotations.Select;

import java.util.List;

@Mapper
public interface PointsTransactionMapper extends BaseMapper<PointsTransaction> {

    @Select("SELECT * FROM points_transaction WHERE user_id = #{userId} ORDER BY create_time DESC LIMIT #{limit}")
    List<PointsTransaction> findRecentByUserId(@Param("userId") Long userId, @Param("limit") int limit);

    @Select("SELECT SUM(points_change) FROM points_transaction WHERE user_id = #{userId} AND transaction_type = #{type}")
    Long sumPointsByType(@Param("userId") Long userId, @Param("type") String type);
}
