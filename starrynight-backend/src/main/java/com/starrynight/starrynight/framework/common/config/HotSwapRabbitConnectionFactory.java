package com.starrynight.starrynight.framework.common.config;

import org.springframework.amqp.AmqpException;
import org.springframework.amqp.rabbit.connection.CachingConnectionFactory;
import org.springframework.amqp.rabbit.connection.Connection;
import org.springframework.amqp.rabbit.connection.ConnectionFactory;
import org.springframework.amqp.rabbit.connection.ConnectionListener;
import org.springframework.beans.factory.DisposableBean;
import org.springframework.beans.factory.InitializingBean;
import org.springframework.context.SmartLifecycle;

import java.util.Objects;
import java.util.concurrent.CopyOnWriteArrayList;
import java.util.concurrent.Executors;
import java.util.concurrent.ScheduledExecutorService;
import java.util.concurrent.TimeUnit;
import java.util.concurrent.atomic.AtomicReference;

/**
 * 包装 {@link CachingConnectionFactory}：对外保持同一 {@link ConnectionFactory} 引用，便于运营端热切换 Broker 地址与账号。
 * <p>
 * 通过本类注册的 {@link ConnectionListener} 会在每次 swap 后自动挂到新底层工厂上。
 */
public final class HotSwapRabbitConnectionFactory
        implements ConnectionFactory, InitializingBean, DisposableBean, SmartLifecycle {

    private static final int DESTROY_DELAY_SEC = 3;

    private final AtomicReference<CachingConnectionFactory> delegate = new AtomicReference<>();
    private final CopyOnWriteArrayList<ConnectionListener> connectionListeners = new CopyOnWriteArrayList<>();
    private final ScheduledExecutorService destroyScheduler =
            Executors.newSingleThreadScheduledExecutor(r -> {
                Thread t = new Thread(r, "rabbit-cf-delayed-destroy");
                t.setDaemon(true);
                return t;
            });

    public HotSwapRabbitConnectionFactory(CachingConnectionFactory initial) {
        this.delegate.set(Objects.requireNonNull(initial));
    }

    public synchronized void swapTo(CachingConnectionFactory next) {
        Objects.requireNonNull(next);
        CachingConnectionFactory current = delegate.get();
        if (current == next) {
            return;
        }
        next.afterPropertiesSet();
        next.start();
        for (ConnectionListener listener : connectionListeners) {
            next.addConnectionListener(listener);
        }
        delegate.set(next);
        if (current != null) {
            scheduleDestroy(current);
        }
    }

    private void scheduleDestroy(CachingConnectionFactory oldFactory) {
        destroyScheduler.schedule(() -> {
            try {
                if (oldFactory.isRunning()) {
                    oldFactory.stop();
                }
            } catch (Exception ignored) {
                // ignore
            }
            try {
                oldFactory.destroy();
            } catch (Exception ignored) {
                // ignore
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
        CachingConnectionFactory f = delegate.get();
        if (f != null) {
            f.stop();
        }
    }

    @Override
    public boolean isRunning() {
        CachingConnectionFactory f = delegate.get();
        return f != null && f.isRunning();
    }

    @Override
    public void destroy() {
        destroyScheduler.shutdown();
        CachingConnectionFactory f = delegate.get();
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
    public Connection createConnection() throws AmqpException {
        return delegate.get().createConnection();
    }

    @Override
    public String getHost() {
        return delegate.get().getHost();
    }

    @Override
    public int getPort() {
        return delegate.get().getPort();
    }

    @Override
    public String getVirtualHost() {
        return delegate.get().getVirtualHost();
    }

    @Override
    public String getUsername() {
        return delegate.get().getUsername();
    }

    @Override
    public void addConnectionListener(ConnectionListener listener) {
        connectionListeners.addIfAbsent(listener);
        delegate.get().addConnectionListener(listener);
    }

    @Override
    public boolean removeConnectionListener(ConnectionListener listener) {
        connectionListeners.remove(listener);
        return delegate.get().removeConnectionListener(listener);
    }

    @Override
    public void clearConnectionListeners() {
        connectionListeners.clear();
        delegate.get().clearConnectionListeners();
    }

    @Override
    public ConnectionFactory getPublisherConnectionFactory() {
        return delegate.get().getPublisherConnectionFactory();
    }

    @Override
    public boolean isSimplePublisherConfirms() {
        return delegate.get().isSimplePublisherConfirms();
    }

    @Override
    public boolean isPublisherConfirms() {
        return delegate.get().isPublisherConfirms();
    }

    @Override
    public boolean isPublisherReturns() {
        return delegate.get().isPublisherReturns();
    }

    @Override
    public void resetConnection() {
        delegate.get().resetConnection();
    }

    @Override
    public int getPhase() {
        return delegate.get().getPhase();
    }
}
