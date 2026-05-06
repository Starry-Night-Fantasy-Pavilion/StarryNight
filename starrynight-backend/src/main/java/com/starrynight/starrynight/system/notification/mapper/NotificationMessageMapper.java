package com.starrynight.starrynight.system.notification.mapper;

import com.baomidou.mybatisplus.core.mapper.BaseMapper;
import com.starrynight.starrynight.system.notification.entity.NotificationMessage;
import org.apache.ibatis.annotations.Mapper;
import org.apache.ibatis.annotations.Param;
import org.apache.ibatis.annotations.Select;
import org.apache.ibatis.annotations.Update;

import java.time.LocalDateTime;
import java.util.List;

@Mapper
public interface NotificationMessageMapper extends BaseMapper<NotificationMessage> {

    @Select("SELECT * FROM notification_message WHERE user_id = #{userId} AND (expire_time IS NULL OR expire_time > #{now}) ORDER BY create_time DESC LIMIT #{limit}")
    List<NotificationMessage> selectRecentByUserId(@Param("userId") Long userId, @Param("limit") int limit, @Param("now") LocalDateTime now);

    @Select("SELECT COUNT(*) FROM notification_message WHERE user_id = #{userId} AND is_read = 0 AND (expire_time IS NULL OR expire_time > #{now})")
    Integer countUnread(@Param("userId") Long userId, @Param("now") LocalDateTime now);

    @Select("SELECT * FROM notification_message WHERE user_id = #{userId} AND notification_type = #{type} ORDER BY create_time DESC")
    List<NotificationMessage> selectByType(@Param("userId") Long userId, @Param("type") String type);

    @Update("UPDATE notification_message SET is_read = 1, read_time = #{readTime} WHERE id = #{id}")
    int markAsRead(@Param("id") Long id, @Param("readTime") LocalDateTime readTime);

    @Update("UPDATE notification_message SET is_read = 1, read_time = #{readTime} WHERE user_id = #{userId} AND is_read = 0")
    int markAllAsRead(@Param("userId") Long userId, @Param("readTime") LocalDateTime readTime);
}
