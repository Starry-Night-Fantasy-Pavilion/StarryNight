import { authGatewayPost } from '@/utils/authGateway'

export interface RealnameStartVO {
  mode: string
  redirectUrl: string
  feeChargedYuan?: number
}

export interface RealnameFeePayVO {
  payUrl: string
  recordNo: string
  amountYuan: number
  payType?: string
}

/** 需携带用户 JWT（Authorization: Bearer …） */
export function startRealnameVerification(accessToken: string) {
  return authGatewayPost<RealnameStartVO>(
    '/auth/realname/start',
    {},
    accessToken ? { Authorization: `Bearer ${accessToken}` } : {}
  )
}

/** 创建实名认证费易支付跳转 URL */
export function createRealnameFeePay(accessToken: string, body?: { payType?: string }) {
  return authGatewayPost<RealnameFeePayVO>(
    '/auth/realname/fee/create-pay',
    body ?? {},
    accessToken ? { Authorization: `Bearer ${accessToken}` } : {}
  )
}
