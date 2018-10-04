import svelte from 'rollup-plugin-svelte'
import resolve from 'rollup-plugin-node-resolve'
import replace from 'rollup-plugin-replace'
import commonjs from 'rollup-plugin-commonjs'
import buble from 'rollup-plugin-buble'
import {
  uglify
} from 'rollup-plugin-uglify'
import postcss from 'rollup-plugin-postcss'
import json from 'rollup-plugin-json'

import {
  writeFileSync
} from 'fs'

const production = !process.env.ROLLUP_WATCH

function createConfig(config) {

  const baseConfig = {
    plugins: [
      replace({
        include: '*/**',
        'process.env.NODE_ENV': JSON.stringify(production ? 'production' : 'dev'),
      }),
      resolve({
        module: true,
        jsnext: true,
        browser: true
      }),
      json(),
      svelte({
        // opt in to v3 behaviour today
        skipIntroByDefault: true,
        nestedTransitions: true,

        // enable run-time checks when not in production
        dev: !production,
        // we'll extract any component CSS out into
        // a separate file — better for performance
        emitCss: true,
        css: true
      }),
      postcss({
        extract: true,
        minimize: production
      }),
      // If you have external dependencies installed from
      // npm, you'll most likely need these plugins. In
      // some cases you'll need additional configuration —
      // consult the documentation for details:
      // https://github.com/rollup/rollup-plugin-commonjs
      commonjs(),
      // If we're building for production (npm run build
      // instead of npm run dev), transpile and minify
      production && buble({
        include: ['resources/**', 'node_modules/svelte/shared.js']
      }),
      production && uglify()
    ]
  }
  return Object.assign(baseConfig, config)
}

export default [
  createConfig({
    input: 'src/js/admin/app.js',
    output: {
      sourcemap: true,
      format: 'iife',
      name: 'wpmapAdmin', // export in global namespace
      file: production ?
        'public/admin/bundle-adm.min.js' : 'public/admin/bundle-adm.js'
    }
  }),
  createConfig({
    input: 'src/js/client/app.js',
    output: {
      sourcemap: true,
      format: 'iife',
      name: 'wpmap', // export in global namespace
      file: production ?
        'public/widget/bundle.min.js' : 'public/widget/bundle.js'
    }
  }),
]
