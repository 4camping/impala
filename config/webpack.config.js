/** ./node_modules/.bin/webpack -d --watch-poll --watch */
var webpack = require('webpack')
var path = require('path')
var ManifestPlugin = require("webpack-manifest-plugin")
var WebpackMd5HashPlugin = require('webpack-md5-hash')
var __DEV__ = JSON.parse(process.env.BUILD_DEV || "true")
/* var __DEV__ = false /**/
var ASSETS_DIR = 'assets/impala/js'
var BUILD_DIR = path.resolve(__dirname, ASSETS_DIR)
var APP_DIR = path.resolve(__dirname, 'app/Impala/react')

module.exports = {
  entry: {  ContentForm: APP_DIR + '/ContentForm.jsx',
            RowForm: APP_DIR + '/RowForm.jsx',
            Grid: APP_DIR + '/Grid.jsx',
            ImportForm: APP_DIR + '/ImportForm.jsx'
  },
  devServer: {
        proxy: {
            '/app': {
                target: 'http://10.10.0.100/',
                secure: false
            }
        }
  },
  output: {
        path: BUILD_DIR,
        filename: __DEV__ ? '[name].js' : '[name].[chunkhash].js',
        chunkFilename: __DEV__ ? '[name].js' : '[name].[chunkhash].js'
  },
  module : {
    loaders : [
      {
        test : /\.jsx?/,
        exclude:/(node_modules|bower)/,
        include : APP_DIR,
        loader : 'babel-loader',
        query  :{
                presets:['react','es2015']
        }
      },
      { test: /\.css$/, loader: 'css-loader' },
    ]
  },
  plugins: [
    new WebpackMd5HashPlugin(),
    new ManifestPlugin({fileName: 'manifest.json', basePath: '', publicPath: ASSETS_DIR + '/', stripSrc: /\.js/})
  ],
  bail: true
}