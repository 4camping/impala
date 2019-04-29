/** ./node_modules/.bin/webpack -p --config webpack.prod.js */
const webpack = require('webpack');
const merge = require('webpack-merge');
const common = require('./webpack.common.js');
var path = require('path');
module.exports = merge(common, {
    output: {
        chunkFilename: '[name].[chunkhash].js',
        filename: '[name].[chunkhash].js',
        path: path.resolve(__dirname, './node_modules/npm-impala')
    }});