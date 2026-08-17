import { resolve } from 'node:path'
import { rmSync } from 'node:fs'
import { defineConfig, type Plugin } from 'vite'
import react from '@vitejs/plugin-react'
import tailwindcss from '@tailwindcss/vite'

function removeIndexHtml(): Plugin {
  let outDir = ''

  return {
    name: 'remove-index-html',
    configResolved(config) {
      outDir = config.build.outDir
    },
    closeBundle() {
      rmSync(resolve(outDir, 'index.html'), { force: true })
    },
  }
}

export default defineConfig({
  plugins: [react(), tailwindcss(), removeIndexHtml()],
  build: {
    outDir: '../public/assets/spa',
    assetsDir: '',
    emptyOutDir: true,
    rollupOptions: {
      output: {
        entryFileNames: '[name].js',
        chunkFileNames: '[name].js',
        assetFileNames: '[name].[ext]',
      },
    },
  },
})
