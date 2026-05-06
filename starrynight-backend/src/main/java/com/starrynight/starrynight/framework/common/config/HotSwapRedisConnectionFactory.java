package com.starrynight.starrynight.framework.common.config;

import org.springframework.beans.factory.DisposableBean;
import org.springframework.beans.factory.InitializingBean;
import org.springframework.context.SmartLifecycle;
import org.springframework.dao.DataAccessException;
import org.springframework.data.redis.connection.RedisClusterConnection;
import org.springframework.data.redis.connection.RedisConnection;
import org.springframework.data.redis.connection.RedisConnectionFactory;
import org.springframework.data.redis.connection.ReactiveRedisClusterConnection;
import org.springframework.data.redis.connection.ReactiveRedisConnection;
import org.springframework.data.redis.connection.ReactiveRedisConnectionFactory;
import org.springframework.data.redis.connection.RedisSentinelConnection;
import org.springframework.data.redis.connection.lettuce.LettuceConnectionFactory;

import java.util.Objects;
import java.util.concurrent.Executors;
import java.util.concurrent.ScheduledExecutorService;
import java.util.concurrent.TimeUnit;
import java.util.concurrent.atomic.AtomicReference;

/**
 * 包装 Lettuce 单机工厂：对外仍为同一 {@link RedisConnectionFactory} / {@link ReactiveRedisConnectionFactory} 引用，
 * 底层连接可在运营端修改 {@code system_config} 后热替换。
 */
public final class HotSwapRedisConnectionFactory
        implements RedisConnectionFactory, ReactiveRedisConnectionFactory, InitializingBean, DisposableBean, SmartLifecycle {

    private static final int DESTROY_DELAY_SEC = 3;

    private final AtomicReference<LettuceConnectionFactory> delegate = new AtomicReference<>();
    private final ScheduledExecutorService destroyScheduler =
            Executors.newSingleThreadScheduledExecutor(r -> {
                Thread t = new Thread(r, "redis-lettuce-delayed-destroy");
                t.setDaemon(true);
                return t;
            });

    public HotSwapRedisConnectionFactory(LettuceConnectionFactory initial) {
        this.delegate.set(Objects.requireNonNull(initial));
    }

    /**
     * 切换到新工厂；旧工厂延迟关闭，避免仍有在途连接。
     */
    public synchronized void swapTo(LettuceConnectionFactory next) {
        Objects.requireNonNull(next);
        LettuceConnectionFactory current = delegate.get();
        if (current == next) {
            return;
        }
        next.afterPropertiesSet();
        next.start();
        delegate.set(next);
        if (current != null) {
            scheduleDestroy(current);
        }
    }

    private void scheduleDestroy(LettuceConnectionFactory oldFactory) {
        destroyScheduler.schedule(() -> {
            try {
                if (oldFactory.isRunning()) {
                    oldFactory.stop();
                }
            } catch (Exception ignored) {
                // stop 失败仍尝试 destroy
            }
            try {
                oldFactory.destroy();
            } catch (Exception ignored) {
                // 已关闭或重复 destroy
            }
        }, DESTROY_DELAY_SEC, TimeUnit.SECONDS);
    }

    @Override
    public void afterPropertiesSet() {
        delegate.get().afterPropertiesSet();
    }

    @Override
    public void start() {
        delegate.get().start();
    }

    @Override
    public void stop() {
        LettuceConnectionFactory f = delegate.get();
        if (f != null) {
            f.stop();
        }
    }

    @Override
    public boolean isRunning() {
        LettuceConnectionFactory f = delegate.get();
        return f != null && f.isRunning();
    }

    @Override
    public void destroy() {
        destroyScheduler.shutdown();
        LettuceConnectionFactory f = delegate.get();
        if (f != null) {
            try {
                if (f.isRunning()) {
                    f.stop();
                }
            } catch (Exception ignored) {
                // ignore
            }
            try {
                f.destroy();
            } catch (Exception ignored) {
                // ignore
            }
        }
    }

    @Override
    public boolean getConvertPipelineAndTxResults() {
        return delegate.get().getConvertPipelineAndTxResults();
    }

    @Override
    public RedisConnection getConnection() {
        return delegate.get().getConnection();
    }

    @Override
    public RedisClusterConnection getClusterConnection() {
        return delegate.get().getClusterConnection();
    }

    @Override
    public RedisSentinelConnection getSentinelConnection() {
        return delegate.get().getSentinelConnection();
    }

    @Override
    public DataAccessException translateExceptionIfPossible(RuntimeException ex) {
        return delegate.get().translateExceptionIfPossible(ex);
    }

    @Override
    public ReactiveRedisConnection getReactiveConnection() {
        return delegate.get().getReactiveConnection();
    }

    @Override
    public ReactiveRedisClusterConnection getReactiveClusterConnection() {
        return delegate.get().getReactiveClusterConnection();
    }
}
