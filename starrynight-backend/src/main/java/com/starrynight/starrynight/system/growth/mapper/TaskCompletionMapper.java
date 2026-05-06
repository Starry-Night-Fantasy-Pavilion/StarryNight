package com.starrynight.starrynight.system.growth.mapper;

import com.baomidou.mybatisplus.core.mapper.BaseMapper;
import com.starrynight.starrynight.system.growth.entity.TaskCompletion;
import org.apache.ibatis.annotations.Mapper;
import org.apache.ibatis.annotations.Param;
import org.apache.ibatis.annotations.Select;

import java.time.LocalDate;
import java.util.List;

@Mapper
public interface TaskCompletionMapper extends BaseMapper<TaskCompletion> {

    @Select("SELECT * FROM task_completion WHERE user_id = #{userId} AND completion_date = #{date}")
    List<TaskCompletion> findByUserIdAndDate(@Param("userId") Long userId, @Param("date") LocalDate date);

    @Select("SELECT * FROM task_completion WHERE user_id = #{userId} AND task_code = #{taskCode} AND completion_date = #{date}")
    TaskCompletion findByUserIdAndTaskCodeAndDate(@Param("userId") Long userId, @Param("taskCode") String taskCode, @Param("date") LocalDate date);

    @Select("SELECT SUM(completion_count) FROM task_completion WHERE user_id = #{userId} AND task_code = #{taskCode} AND completion_date = #{date}")
    Integer sumCompletionCountByDate(@Param("userId") Long userId, @Param("taskCode") String taskCode, @Param("date") LocalDate date);
}
