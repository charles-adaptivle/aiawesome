import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';

// https://vitejs.dev/config/
export default defineConfig({
  plugins: [react()],
  define: {
    'process.env.NODE_ENV': JSON.stringify('production'),
    'process': JSON.stringify({}),
    'global': 'globalThis',
  },
  build: {
    lib: {
      entry: {
        boot: 'amd/src/boot.js',
        simple_app: 'amd/src/simple_app.js',
        sse: 'amd/src/sse.js',
      },
      formats: ['amd'],
      fileName: (format, name) => `${name}.js`
    },
    outDir: 'amd/build',
    rollupOptions: {
      external: [
        'core/str',
        'core/notification',
        'core/ajax',
      ],
      output: {
        amd: {
          autoId: false,
        }
      }
    },
    minify: 'terser',
    sourcemap: false,
    target: 'es2020',
  },
  esbuild: {
    target: 'es2020'
  }
});