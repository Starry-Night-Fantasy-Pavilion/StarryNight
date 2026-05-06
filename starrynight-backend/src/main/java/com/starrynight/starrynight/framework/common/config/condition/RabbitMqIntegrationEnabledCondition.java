package com.starrynight.starrynight.framework.common.config.condition;

import org.springframework.beans.factory.config.ConfigurableListableBeanFactory;
import org.springframework.context.annotation.Condition;
import org.springframework.context.annotation.ConditionContext;
import org.springframework.core.type.AnnotatedTypeMetadata;
import org.springframework.jdbc.core.JdbcTemplate;
import org.springframework.lang.NonNull;
import org.springframework.util.StringUtils;

import java.util.List;

/**
 * 是否启用 RabbitMQ 集成（队列、交换机、监听器及运营端自定义连接）。
 * 读取 {@code system_config} 键 {@value #CONFIG_KEY}，缺省为 {@code true}。
 * 仅<strong>启动期</strong>判定是否注册 Rabbit 相关 Bean；若启动时为 {@code false}，之后改为 {@code true} 需重启进程。
 * 启动时已集成时，可在运营端热关开（停/启监听器）或热切换 {@code spring.rabbitmq.*} 连接。
 *
 * <p>注意：不得在 {@code matches} 中 {@code getBean(RuntimeConfigService)}，否则会在条件解析阶段
 * 过早实例化 {@code RuntimeConfigService}，此时其 {@link JdbcTemplate} 依赖尚未注入，导致字段为 null，
 * 后续运营端保存配置时 {@code reloadFromDatabase()} 会 NPE。</p>
 */
public class RabbitMqIntegrationEnabledCondition implements Condition {

    public static final String CONFIG_KEY = "rabbitmq.integration.enabled";

    @Override
    public boolean matches(@NonNull ConditionContext context, @NonNull AnnotatedTypeMetadata metadata) {
        try {
            ConfigurableListableBeanFactory bf = context.getBeanFactory();
            if (bf == null || !bf.containsBean("jdbcTemplate")) {
                return true;
            }
            Object bean = bf.getBean("jdbcTemplate");
            if (!(bean instanceof JdbcTemplate jt)) {
                return true;
            }
            List<String> vals = jt.query(
                    "SELECT config_value FROM system_config WHERE config_key = ? LIMIT 1",
                    (rs, rowNum) -> rs.getString(1),
                    CONFIG_KEY);
            if (vals == null || vals.isEmpty()) {
                return true;
            }
            String raw = vals.get(0);
            if (!StringUtils.hasText(raw)) {
                return true;
            }
            return Boolean.parseBoolean(raw.trim());
        } catch (Exception e) {
            return true;
        }
    }
}
