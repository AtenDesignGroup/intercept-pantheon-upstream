const { merge } = require('webpack-merge');
const common = require('./webpack.config.js');

module.exports = common.map((entry) => merge(entry, {
  mode: 'development',
  devtool: 'eval-cheap-module-source-map',
  devServer: {
    static: './js/dist',
  },
  optimization: {
    minimize: false,
  },
}));
