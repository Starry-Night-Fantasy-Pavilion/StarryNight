import dayjs from 'dayjs'
import utc from 'dayjs/plugin/utc'
import timezone from 'dayjs/plugin/timezone'

dayjs.extend(utc)
dayjs.extend(timezone)

/** 中国标准时间（东八区），展示为 YYYY-MM-DD HH:mm:ss */
export function formatChinaDateTime(value: string | undefined | null): string {
  if (value == null || String(value).trim() === '') return '—'
  const s = String(value).trim()
  const offsetOrZ = /Z$/i.test(s) || /[+-]\d{2}:?\d{2}$/.test(s)
  let d = offsetOrZ ? dayjs.utc(s).tz('Asia/Shanghai') : dayjs.tz(s, 'Asia/Shanghai')
  if (!d.isValid()) {
    const fb = dayjs(s)
    d = fb.isValid() ? fb.tz('Asia/Shanghai') : d
  }
  if (!d.isValid()) return s
  return d.format('YYYY-MM-DD HH:mm:ss')
}

export function chinaDateTimeTitle(value: string | undefined | null): string {
  const t = formatChinaDateTime(value)
  return t === '—' ? '' : t
}
