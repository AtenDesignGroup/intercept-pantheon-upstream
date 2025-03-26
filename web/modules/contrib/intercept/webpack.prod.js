/**
 * Webpack JavaScript Compilation
 * Current compilation standards dictate that all JavaScript be compiled using
 * Webpack compilation services - https://webpack.js.org/
 *
 * - 01 - Imports
 * - 02 - Exports
 */

/*------------------------------------*\
  01 - Imports
  Define the NPM packages to be used during the Webpack compilation, including
  Webpack itself. Even though the Webpack library isn't directly used, it is
  still required to be defined.
\*------------------------------------*/
const BundleAnalyzerPlugin = require('webpack-bundle-analyzer').BundleAnalyzerPlugin;
const TerserPlugin = require('terser-webpack-plugin');
const { merge } = require('webpack-merge');
const common = require('./webpack.config.js');

/*------------------------------------*\
  02 - Exports
  Prepare all resources using Webpack to be exported as distributed and
  compiled files. Here, Babel (https://babeljs.io/) is being used to convert
  any ES6 syntax to earlier versions, if necessary.
\*------------------------------------*/
module.exports = common.map((entry) => merge(entry, {
  mode: 'production',
  devtool: false,
  optimization: {
    minimize: true,
    minimizer: [
      new TerserPlugin({
        extractComments: false,
      }),
    ],
  },
  plugins: [
    // Uncomment to analyze bundle sizes.
    // new BundleAnalyzerPlugin({
    //   analyzerMode: 'static',
    // })
  ],
}));
