const path = require('path');
const webpack = require('webpack');
const BundleAnalyzerPlugin = require('webpack-bundle-analyzer').BundleAnalyzerPlugin;

// Track webpack build pipeline performance.
const SpeedMeasurePlugin = require('speed-measure-webpack-plugin');

const smp = new SpeedMeasurePlugin();

// Create an object map of entry file arrays keyed by submodule.
const entries = {
  intercept_event: [
    'eventAddToCalendar',
    'eventAttendanceList',
    'eventCustomerEvaluation',
    'eventCustomerEvaluations',
    'eventList',
    'eventRegister',
    'eventRegisterButton',
    'eventRegistrationList',
  ],
  intercept_location: ['locationsList'],
  intercept_room_reservation: [
    'reserveRoom',
    'roomReservationActionButton',
    'roomReservationList',
    'roomReservationScheduler',
  ],
  intercept_equipment: ['reserveEquipment'],
};

const babelLoader = {
  test: /\.js$/,
  loader: 'babel-loader',
  exclude: /node_modules/,
  query: {
    presets: [
      '@babel/preset-react',
      [
        '@babel/preset-env',
        {
          corejs: 3,
          debug: true,
          targets: {
            browsers: ['last 2 version', '> .25%', 'ie 11', 'android 4', 'ios 9', 'edge 18'],
          },
          useBuiltIns: 'usage',
          modules: false,
        },
      ],
    ],
    plugins: [
      '@babel/plugin-external-helpers',
      '@babel/plugin-transform-runtime',
      '@babel/plugin-proposal-class-properties',
    ],
  },
};

/**
 * Turn the entry map into a valid entry config object.
 *
 * @param {Object} entries
 *   Map of entry file arrays keyed by submodule.
 *   Example: { "submodule_name": ["entry1, entry2"]}
 * @return {Object}
 *   A valid Webpack entry config object
 */
function createEntryConfig(entries) {
  const output = {};

  // Loop over each module.
  Object.keys(entries).forEach((module) => {
    // Loop over each entry.
    entries[module].forEach((entry) => {
      // Set the entry name to a relative path to the output directory so it
      // can be used as a dynamic placeholder in the output config.
      output[`modules/${module}/js/dist/${entry}`] = `./modules/${module}/js/src/${entry}.js`;
    });
  });

  return output;
}

/**
 * Webpack config
 */
module.exports = function config(env, argv) {
  const isProduction = argv.mode === 'production';

  return [
    {
      entry: createEntryConfig(entries),
      mode: isProduction ? 'production' : 'development',
      devtool: (() => (isProduction ? 'none' : 'cheap-module-eval-source-map'))(),
      output: {
        filename: '[name].js',
        path: path.resolve(__dirname),
        jsonpFunction: 'wpJsonpIntercept',
      },
      resolve: {
        // Allow common modules in the root module to be referenced.
        modules: [
          path.resolve(__dirname, 'js/src'),
          path.resolve(__dirname, 'node_modules'),
          'node_modules',
        ],
      },
      optimization: {
        splitChunks: {
          cacheGroups: {
            commons: {
              name: 'modules/intercept_core/js/dist/interceptCommon',
              chunks: 'initial',
              minChunks: 4,
            }
          }
        }
      },
      // plugins: [
      //   new BundleAnalyzerPlugin()
      // ],
      externals: (() => {
        const prod = {
          react: 'React',
          'react-dom': 'ReactDOM',
          interceptTheme: 'interceptTheme',
          interceptClient: 'interceptClient',
          Drupal: 'Drupal',
          drupalSettings: 'drupalSettings',
          jQuery: 'jQuery',
          moment: 'moment',
          redis: 'redis',
        };
        const dev = {
          interceptTheme: 'interceptTheme',
          interceptClient: 'interceptClient',
          Drupal: 'Drupal',
          drupalSettings: 'drupalSettings',
          jQuery: 'jQuery',
          redis: 'redis',
          moment: 'moment',
        };

        return isProduction ? prod : dev;
      })(),
      module: {
        rules: [
          babelLoader,
          { test: /\.css$/, loader: 'style-loader!css-loader' }
        ],
      },
    },

    //
    // interceptClient.js
    //   This should function as a standalone library so it needs its own config.
    //
    {
      entry: {
        'modules/intercept_core/js/dist/interceptClient':
          './modules/intercept_core/js/src/interceptClient.js',
      },
      mode: isProduction ? 'production' : 'development',
      devtool: (() => (isProduction ? 'none' : 'cheap-module-eval-source-map'))(),
      output: {
        filename: '[name].js',
        path: path.resolve(__dirname),
        libraryTarget: 'umd',
        library: 'interceptClient',
      },
      resolve: {
        // Allow common modules in the root module to be referenced.
        modules: [
          path.resolve(__dirname, 'js/src'),
          path.resolve(__dirname, 'node_modules'),
          'node_modules',
        ],
      },
      // plugins: [
      //   new BundleAnalyzerPlugin()
      // ],
      externals: (() => {
        const prod = {
          Drupal: 'Drupal',
          drupalSettings: 'drupalSettings',
          moment: 'moment',
          redis: 'redis',
          react: 'React',
          'react-dom': 'ReactDOM',
        };
        const dev = {
          Drupal: 'Drupal',
          drupalSettings: 'drupalSettings',
          moment: 'moment',
          redis: 'redis',
        };

        return isProduction ? prod : dev;
      })(),
      module: {
        rules: [babelLoader],
      },
    },

    //
    // interceptTheme.js
    //   This should function as a standalone library so it needs its own config.
    //
    {
      entry: {
        'modules/intercept_core/js/dist/interceptTheme':
          './modules/intercept_core/js/src/interceptTheme.js',
      },
      mode: isProduction ? 'production' : 'development',
      devtool: (() => (isProduction ? 'none' : 'cheap-module-eval-source-map'))(),
      output: {
        filename: '[name].js',
        path: path.resolve(__dirname),
        libraryTarget: 'umd',
      },
      resolve: {
        // Allow common modules in the root module to be referenced.
        modules: [path.resolve(__dirname, 'js/src'), 'node_modules'],
      },
      externals: (() => {
        const prod = {
          Drupal: 'Drupal',
          drupalSettings: 'drupalSettings',
          react: 'React',
          'react-dom': 'ReactDOM',
        };
        const dev = {
          Drupal: 'Drupal',
          drupalSettings: 'drupalSettings',
        };

        return isProduction ? prod : dev;
      })(),
      module: {
        rules: [babelLoader],
      },
    },
  ].map(smp.wrap);
};
