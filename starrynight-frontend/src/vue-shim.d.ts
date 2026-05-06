/** 必须先解析 axios 本体，否则下方 augmentation 会覆盖成仅含 authPortal 的 AxiosRequestConfig，丢失 params 等 */
import 'axios'

declare module 'axios' {
  interface AxiosRequestConfig {
    /** 指定使用用户端或运营端 Bearer；缺省时按 URL（admin/）推断 */
    authPortal?: 'USER' | 'OPS'
  }
}
