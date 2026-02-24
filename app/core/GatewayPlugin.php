<?php

declare(strict_types=1);

namespace Core;

/**
 * 支付网关插件基类
 *
 * 支付接口插件必须继承此类
 */
abstract class GatewayPlugin extends Plugin
{
    /**
     * @var string 插件类型
     */
    protected string $type = 'gateways';

    /**
     * 处理支付请求
     *
     * @param array $param 支付参数
     * @return array<string, mixed> 支付结果
     */
    abstract public function handle(array $param): array;

    /**
     * 处理支付回调
     *
     * @param array $param 回调参数
     * @return array<string, mixed> 处理结果
     */
    abstract public function callback(array $param): array;

    /**
     * 查询订单状态
     *
     * @param string $orderId 订单号
     * @return array<string, mixed>
     */
    abstract public function query(string $orderId): array;

    /**
     * 退款
     *
     * @param string $orderId 订单号
     * @param float $amount 退款金额
     * @param string $reason 退款原因
     * @return array<string, mixed>
     */
    abstract public function refund(string $orderId, float $amount, string $reason = ''): array;

    /**
     * 获取支付方式列表
     *
     * @return array<int, array<string, mixed>>
     */
    public function getPaymentMethods(): array
    {
        return [
            [
                'name' => $this->info['name'] ?? 'unknown',
                'title' => $this->info['title'] ?? '未知支付',
                'icon' => $this->info['logo_url'] ?? '',
                'description' => $this->info['description'] ?? ''
            ]
        ];
    }

    /**
     * 验证签名
     *
     * @param array $data 数据
     * @param string $sign 签名
     * @return bool
     */
    abstract protected function verifySign(array $data, string $sign): bool;

    /**
     * 生成签名
     *
     * @param array $data 数据
     * @return string
     */
    abstract protected function generateSign(array $data): string;
}
