import { defineConfig, loadEnv } from 'vite'
import vue from '@vitejs/plugin-vue'
import { resolve } from 'path'
import AutoImport from 'unplugin-auto-import/vite'
import Components from 'unplugin-vue-components/vite'
import { ElementPlusResolver } from 'unplugin-vue-components/resolvers'

/** 开发代理目标：默认 127.0.0.1，避免 Windows 上 localhost 解析到 ::1 而后端仅监听 IPv4 时出现 502 */
const DEFAULT_API_PROXY = 'http://127.0.0.1:8080'

export default defineConfig(({ mode }) => {
  const env = loadEnv(mode, process.cwd(), '')
  const apiProxyTarget = (env.VITE_DEV_API_PROXY || DEFAULT_API_PROXY).replace(/\/+$/, '')

  const apiProxy = {
    target: apiProxyTarget,
    changeOrigin: true,
    configure(proxy) {
      proxy.on('error', (err) => {
        console.error(`[vite proxy] /api -> ${apiProxyTarget} 失败:`, err.message)
      })
    }
  }

  return {
    plugins: [
      vue(),
      AutoImport({
        resolvers: [ElementPlusResolver()],
        imports: ['vue', 'vue-router', 'pinia'],
        dts: 'src/auto-imports.d.ts'
      }),
      Components({
        resolvers: [ElementPlusResolver()],
        dts: 'src/components.d.ts'
      })
    ],
    resolve: {
      alias: {
        '@': resolve(__dirname, 'src')
      }
    },
    server: {
      port: 3000,
      host: '0.0.0.0',
      proxy: {
        '/api': apiProxy
      }
    },
    css: {
      preprocessorOptions: {
        scss: {
          additionalData: '@use "@/styles/variables.scss" as *;'
        }
      }
    }
  }
})
