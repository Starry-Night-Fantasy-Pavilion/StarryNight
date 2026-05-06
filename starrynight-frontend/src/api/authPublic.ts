import { authGatewayGet } from '@/utils/authGateway'

export interface RegisterOptionsVO {
  emailRegisterEnabled: boolean
  phoneRegisterEnabled: boolean
  realNameVerificationEnabled: boolean
  /** 实名关闭时为 basic；开启后为 alipay | ovooa（喵雨欣） */
  realNameVerifyProvider?: string
}

export function fetchRegisterOptions() {
  return authGatewayGet<RegisterOptionsVO>('/auth/register-options')
}
