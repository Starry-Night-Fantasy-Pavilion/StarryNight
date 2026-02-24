# 会员体系与星夜币管理 API 文档

## 概述

本文档描述了星夜阁会员体系与星夜币管理系统的API接口，包括会员套餐、充值套餐、功能权限、用户限制等相关功能。

## 基础信息

- **Base URL**: `/api/v1`
- **认证方式**: Session认证
- **数据格式**: JSON
- **字符编码**: UTF-8

## 通用响应格式

### 成功响应
```json
{
    "success": true,
    "data": {},
    "message": "操作成功"
}
```

### 错误响应
```json
{
    "success": false,
    "error": "错误代码",
    "message": "错误描述"
}
```

## 会员中心 API

### 获取会员信息
- **URL**: `GET /membership/info`
- **描述**: 获取当前用户的会员信息
- **响应**:
```json
{
    "success": true,
    "data": {
        "user_id": 1,
        "username": "testuser",
        "membership": {
            "type": 1,
            "type_name": "月度会员",
            "start_time": "2024-01-01 00:00:00",
            "end_time": "2024-02-01 00:00:00",
            "is_lifetime": false,
            "days_remaining": 15,
            "auto_renew": false
        },
        "token_balance": {
            "balance": 5000,
            "total_recharged": 10000,
            "total_consumed": 5000,
            "total_bonus": 1000
        }
    }
}
```

### 获取用户限制状态
- **URL**: `GET /membership/limits`
- **描述**: 获取用户的使用限制状态
- **响应**:
```json
{
    "success": true,
    "data": {
        "novels": {
            "current": 3,
            "limit": 5,
            "remaining": 2,
            "percentage": 60,
            "is_unlimited": false
        },
        "prompts": {
            "current": 15,
            "limit": 20,
            "remaining": 5,
            "percentage": 75,
            "is_unlimited": false
        }
    }
}
```

## 会员套餐 API

### 获取会员套餐列表
- **URL**: `GET /membership/packages`
- **描述**: 获取所有可用的会员套餐
- **响应**:
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "name": "月度会员",
            "type": 1,
            "duration_days": 30,
            "original_price": 30.00,
            "actual_price": 25.00,
            "discount": "8.3折",
            "saved": 5.00,
            "features": ["AI音乐创作", "AI动漫制作", "无限存储"],
            "is_recommended": false,
            "is_enabled": true
        }
    ]
}
```

### 购买会员套餐
- **URL**: `POST /membership/purchase`
- **描述**: 购买会员套餐
- **参数**:
```json
{
    "package_id": 1,
    "payment_method": "alipay",
    "auto_renew": 0
}
```
- **响应**:
```json
{
    "success": true,
    "data": {
        "order_no": "VIP202401010001",
        "record_id": 123,
        "amount": 25.00
    },
    "message": "订单创建成功"
}
```

## 充值中心 API

### 获取充值套餐列表
- **URL**: `GET /membership/recharge-packages`
- **描述**: 获取所有可用的充值套餐
- **响应**:
```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "name": "基础包",
            "tokens": 5000,
            "bonus_tokens": 500,
            "price": 25.00,
            "actual_price": 20.00,
            "discount": "8折",
            "saved": 5.00,
            "is_hot": true,
            "is_enabled": true
        }
    ]
}
```

### 充值星夜币
- **URL**: `POST /membership/recharge`
- **描述**: 充值星夜币
- **参数**:
```json
{
    "package_id": 1,
    "payment_method": "alipay"
}
```
- **响应**:
```json
{
    "success": true,
    "data": {
        "order_no": "RC202401010001",
        "record_id": 456,
        "amount": 20.00,
        "tokens": 5500
    },
    "message": "订单创建成功"
}
```

## 订单管理 API

### 获取订单列表
- **URL**: `GET /membership/orders`
- **描述**: 获取用户的订单列表
- **参数**:
  - `type`: 订单类型 (membership/recharge)
  - `status`: 订单状态 (pending/paid/failed/refunded)
  - `page`: 页码
  - `per_page`: 每页数量
- **响应**:
```json
{
    "success": true,
    "data": {
        "records": [...],
        "total": 100,
        "page": 1,
        "per_page": 20,
        "total_pages": 5
    }
}
```

### 获取订单详情
- **URL**: `GET /membership/orders/{id}`
- **描述**: 获取订单详细信息
- **响应**:
```json
{
    "success": true,
    "data": {
        "id": 123,
        "order_no": "VIP202401010001",
        "user_id": 1,
        "package_name": "月度会员",
        "payment_status": "paid",
        "created_at": "2024-01-01 10:00:00",
        "payment_time": "2024-01-01 10:05:00"
    }
}
```

## 消费记录 API

### 获取消费记录
- **URL**: `GET /membership/token-records`
- **描述**: 获取用户的星夜币消费记录
- **参数**:
  - `type`: 消费类型
  - `page`: 页码
  - `per_page`: 每页数量
- **响应**:
```json
{
    "success": true,
    "data": {
        "records": [
            {
                "id": 789,
                "tokens": -100,
                "balance_before": 5000,
                "balance_after": 4900,
                "consumption_type": "ai_generation",
                "description": "AI生成消费",
                "created_at": "2024-01-01 15:30:00"
            }
        ],
        "total": 50,
        "page": 1,
        "per_page": 20,
        "total_pages": 3
    }
}
```

## 功能权限 API

### 检查功能权限
- **URL**: `POST /membership/check-feature`
- **描述**: 检查用户是否有权限使用某功能
- **参数**:
```json
{
    "feature_key": "ai_music_composition"
}
```
- **响应**:
```json
{
    "success": true,
    "data": {
        "has_access": true,
        "require_vip": true,
        "message": "功能可用"
    }
}
```

### 获取可用功能列表
- **URL**: `GET /membership/features`
- **描述**: 获取用户可使用的功能列表
- **响应**:
```json
{
    "success": true,
    "data": [
        {
            "feature_key": "ai_novel_generation",
            "feature_name": "AI小说生成",
            "category": "ai_creation",
            "description": "使用AI生成小说内容",
            "require_vip": 0,
            "is_enabled": true
        }
    ]
}
```

## 支付回调 API

### 会员支付回调
- **URL**: `POST /membership/payment-callback`
- **描述**: 处理会员购买支付回调
- **参数**:
```json
{
    "order_no": "VIP202401010001",
    "status": "success",
    "transaction_id": "txn_123456789"
}
```

### 充值支付回调
- **URL**: `POST /membership/payment-callback`
- **描述**: 处理充值支付回调
- **参数**:
```json
{
    "order_no": "RC202401010001",
    "status": "success",
    "transaction_id": "txn_123456789"
}
```

## 错误代码

| 错误代码 | 描述 |
|---------|------|
| 1001 | 用户未登录 |
| 1002 | 参数错误 |
| 1003 | 套餐不存在 |
| 1004 | 套餐已下架 |
| 1005 | 余额不足 |
| 1006 | 权限不足 |
| 1007 | 订单不存在 |
| 1008 | 订单状态错误 |
| 1009 | 支付失败 |
| 1010 | 系统错误 |

## 使用示例

### JavaScript 示例

```javascript
// 获取会员信息
fetch('/api/v1/membership/info', {
    method: 'GET',
    headers: {
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
    }
})
.then(response => response.json())
.then(data => {
    if (data.success) {
        console.log('会员信息:', data.data);
    } else {
        console.error('错误:', data.message);
    }
});

// 购买会员套餐
fetch('/api/v1/membership/purchase', {
    method: 'POST',
    headers: {
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
    },
    body: JSON.stringify({
        package_id: 1,
        payment_method: 'alipay',
        auto_renew: 0
    })
})
.then(response => response.json())
.then(data => {
    if (data.success) {
        console.log('订单创建成功:', data.data);
        // 跳转到支付页面
        window.location.href = '/payment/' + data.data.order_no;
    } else {
        alert('购买失败：' + data.message);
    }
});
```

### PHP 示例

```php
// 检查功能权限
$featureKey = 'ai_music_composition';
$result = User::checkFeatureAccess($userId, $featureKey);

if ($result['status']) {
    echo "功能可用";
} else {
    echo "功能不可用：" . $result['message'];
}

// 获取用户余额
$balance = User::getTokenBalance($userId);
echo "当前余额：" . $balance['balance'] . " 星夜币";
```

## 注意事项

1. **认证要求**: 所有API都需要用户登录，通过Session进行认证
2. **频率限制**: 部分API有频率限制，请合理使用
3. **数据格式**: 所有请求和响应均为JSON格式
4. **错误处理**: 请根据错误代码进行相应的错误处理
5. **支付安全**: 支付回调需要验证签名确保安全性
6. **余额检查**: 消费星夜币前请检查余额是否充足
7. **权限验证**: 使用付费功能前请检查用户权限

## 更新日志

### v1.0.0 (2024-01-01)
- 初始版本
- 实现基础会员体系功能
- 支持会员套餐购买
- 支持星夜币充值
- 支持订单管理
- 支持消费记录查询