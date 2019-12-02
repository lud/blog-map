import svelte from 'rollup-plugin-svelte'
import livereload from 'rollup-plugin-livereload'
// import resolve from 'rollup-plugin-node-resolve'
import replace from 'rollup-plugin-replace'
import commonjs from 'rollup-plugin-commonjs'
import buble from 'rollup-plugin-buble'
import {
  terser
} from 'rollup-plugin-terser'
import postcss from 'rollup-plugin-postcss'
import {
  writeFileSync
} from 'fs'

const production = process.env.NODE_ENV === 'production'

function createConfig(config) {

  const baseConfig = {
    plugins: [
      replace({
        include: '*/**',
        'process.env.NODE_ENV': JSON.stringify(production ? 'production' : 'dev'),
      }),
      // resolve({
      //   module: true,
      //   jsnext: true,
      //   browser: true
      // }),
      // json(),
      svelte({
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
      // commonjs(),
      // If we're building for production (npm run build
      // instead of npm run dev), transpile and minify
      production && buble({
        include: ['src/**', 'node_modules/**']
      }),
      production && terser()
    ]
  }
  if (config.plugins) {
    const shallow = Object.assign({}, config)
    shallow.plugins = baseConfig.plugins.concat(config.plugins)
    config = shallow
  }
  return Object.assign(baseConfig, config)
}

export default [
createConfig({
  input: 'src/js/post-admin.js',
  output: {
    sourcemap: true,
    format: 'iife',
    name: 'wpmapPostAdmin',
    file: production 
      ? 'public/admin/bundle-post-admin.min.js'
      : 'public/admin/bundle-post-admin.js'
  },
  plugins: [livereload({watch: 'public'})]
})
  // createConfig({
  //   input: 'src/js/admin/app.js',
  //   output: {
  //     sourcemap: true,
  //     format: 'iife',
  //     name: 'wpmapAdmin', // export in global namespace
  //     file: production ?
  //       'public/admin/bundle-adm.min.js' : 'public/admin/bundle-adm.js'
  //   }
  // }),
  // createConfig({
  //   input: 'src/js/client/app.js',
  //   output: {
  //     sourcemap: true,
  //     format: 'iife',
  //     name: 'wpmap', // export in global namespace
  //     file: production ?
  //       'public/widget/bundle.min.js' : 'public/widget/bundle.js'
  //   }
  // }),
]
