/**
 * 生成本地 data:image/svg+xml 占位图，不请求外网（避免 via.placeholder.com 等不可用）。
 */
export function svgDataPlaceholder(
  width: number,
  height: number,
  backgroundHex: string,
  textHex: string,
  text: string
): string {
  const bg = backgroundHex.replace(/^#/, '')
  const fg = textHex.replace(/^#/, '')
  const safe = text
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;')
  const fontSize = Math.max(10, Math.round(Math.min(width, height) * 0.2))
  const svg = `<svg xmlns="http://www.w3.org/2000/svg" width="${width}" height="${height}"><rect fill="#${bg}" width="100%" height="100%"/><text x="50%" y="50%" dominant-baseline="middle" text-anchor="middle" fill="#${fg}" font-family="system-ui,sans-serif" font-size="${fontSize}">${safe}</text></svg>`
  return `data:image/svg+xml,${encodeURIComponent(svg)}`
}
