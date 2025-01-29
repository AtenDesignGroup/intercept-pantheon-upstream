/**
 * Webpack config
 */
const BundleAnalyzerPlugin = require('webpack-bundle-analyzer').BundleAnalyzerPlugin;

/**
 * Webpack Bundle Analyzer config
 * See: https://www.npmjs.com/package/webpack-bundle-analyzer
 */
const BundleAnalyzerConfig = {
  analyzerMode: 'static',
  reportFilename: 'build/webpack-analysis/index.html',
  openAnalyzer: false
}

/**
 * Base shared config
 */
const baseConfig = {
  module: {
    rules: [
      {
        test: /\.m?js$/,
        exclude: /(node_modules|bower_components)/,
        use: {
          loader: 'babel-loader',
        },
      },
    ],
  },
  externals: {
    Drupal: 'Drupal',
    drupalSettings: 'drupalSettings',
    jQuery: 'jQuery',
    $: 'jQuery',
  },
  plugins: [],
};

/**
 * Mode specific config
 */
module.exports = (argv) => {
  // Default to production mode if not set.
  const mode = argv.mode || 'production';

  const config = {
    ...baseConfig,
    mode
  }

  if (mode === 'development') {
    config.devtool = 'inline-source-map';
  }

  if (mode === 'production') {
    config.plugins.push(new BundleAnalyzerPlugin(BundleAnalyzerConfig))
  }

  return config;
};
