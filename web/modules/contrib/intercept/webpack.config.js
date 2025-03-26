/**
 * Webpack JavaScript Compilation
 * Current compilation standards dictate that all JavaScript be compiled using
 * Webpack compilation services - https://webpack.js.org/
 *
 * - 01 - Imports
 * - 02 - Common config
 * - 03 - Common Entries
 * - 04 - interceptClient.js
 * - 05 - interceptTheme.js
 * - 06 - Exports
 */


/*------------------------------------*\
  01 - Imports
  Define the NPM packages to be used during the Webpack compilation, including
  Webpack itself. Even though the Webpack library isn't directly used, it is
  still required to be defined.
\*------------------------------------*/

const path = require('path');
const webpack = require('webpack');


/*------------------------------------*\
  02 - Common config
  Configuration across all entry files.
\*------------------------------------*/

const commonConfig = {
  mode: 'none',
  resolve: {
    extensions: ['.js', '.jsx', '.vue'],
    fallback: {
      'crypto': require.resolve('crypto-browserify'),
      'stream': require.resolve('stream-browserify'),
      'vm': require.resolve('vm-browserify'),
    },
    modules: [
      path.resolve(__dirname, 'js/src'),
      path.resolve(__dirname, 'node_modules'),
      'node_modules'
    ],
  },
  module: {
    rules: [
      {
        test: /\.js$/,
        exclude: /node_modules/,
        use: {
          loader: 'babel-loader', // All Babel plugins and options will be loaded via the babel.config.js file
        },
      },
      {
        test: /\.css$/,
        use: {
          loader: 'style-loader!css-loader'
        }
      }
    ],
  },
}

/*------------------------------------*\
  03 - Common Entries
\*------------------------------------*/

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

const entry = createEntryConfig({
  intercept_event: [
    'eventAddToCalendar',
    'eventAttendanceList',
    'eventRegister',
    'eventRegisterButton',
    'eventRegistrationList',
  ],
  intercept_location: [
    'locationsList'
  ],
  intercept_room_reservation: [
    'reserveRoom',
    'roomReservationActionButton',
    'roomReservationScheduler',
  ],
  intercept_equipment: [
    'reserveEquipment'
  ],
});

const commonEntries = {
  ...commonConfig,
  entry,
  externals: {
    react: 'React',
    'react-dom': 'ReactDOM',
    interceptTheme: 'interceptTheme',
    interceptClient: 'interceptClient',
    Drupal: 'Drupal',
    drupalSettings: 'drupalSettings',
    jQuery: 'jQuery',
    moment: 'moment',
    redis: 'redis',
  },
  optimization: {
    splitChunks: {
      chunks: 'all',
      name: 'modules/intercept_core/js/dist/interceptCommon',
      filename: '[name].js',
      minChunks: 4,
    },
    usedExports: true,
  },
  output: {
    filename: '[name].js',
    path: path.resolve(__dirname),
    chunkLoadingGlobal: 'wpJsonpIntercept',
  },
};


/*------------------------------------*\
  04 - interceptClient.js
  This should function as a standalone library so it needs its own config.
\*------------------------------------*/

const interceptClient = {
  ...commonConfig,
  entry: {
    'modules/intercept_core/js/dist/interceptClient':
      './modules/intercept_core/js/src/interceptClient.js',
  },
  externals: {
    Drupal: 'Drupal',
    drupalSettings: 'drupalSettings',
    moment: 'moment',
    redis: 'redis',
    react: 'React',
    'react-dom': 'ReactDOM',
  },
  optimization: {
    usedExports: true,
  },
  output: {
    filename: '[name].js',
    path: path.resolve(__dirname),
    libraryTarget: 'umd',
    // library: {
    //   name: 'interceptClient',
    //   type: 'umd',
    //   umdNamedDefine: true,
    // }
  },
};


/*------------------------------------*\
  05 - interceptTheme.js
  This should function as a standalone library so it needs its own config.
\*------------------------------------*/

const interceptTheme = {
  ...commonConfig,
  entry: {
    'modules/intercept_core/js/dist/interceptTheme':
      './modules/intercept_core/js/src/interceptTheme.js',
  },
  externals: {
    Drupal: 'Drupal',
    drupalSettings: 'drupalSettings',
    react: 'React',
    'react-dom': 'ReactDOM',
  },
  optimization: {
    usedExports: true,
  },
  output: {
    filename: '[name].js',
    path: path.resolve(__dirname),
    libraryTarget: 'umd',
  },
};


/*------------------------------------*\
  06 - Exports
  Prepare all resources using Webpack to be exported as distributed and
  compiled files. Here, Babel (https://babeljs.io/) is being used to convert
  any ES6 syntax to earlier versions, if necessary.
\*------------------------------------*/

/**
 * Webpack config
 */
module.exports = [
  commonEntries,
  interceptClient,
  interceptTheme,
];

// module.exports = {
//   entry,
//   mode: 'none',
//   output: {
//     filename: '[name].js',
//     path: path.resolve(__dirname, 'js/dist'),
//     chunkLoadingGlobal: 'wpJsonpPolarisSearch',
//   },
//   devtool: "source-map",
//   externals: {
//     react: 'React',
//     'react-dom': 'ReactDOM',
//     interceptTheme: 'interceptTheme',
//     interceptClient: 'interceptClient',
//     Drupal: 'Drupal',
//     drupalSettings: 'drupalSettings',
//     jQuery: 'jQuery',
//     moment: 'moment',
//     redis: 'redis',
//   },
//   resolve: {
//     extensions: ['.js', '.jsx', '.vue'],
//     modules: [
//       path.resolve(__dirname, 'js/src'),
//       'node_modules'
//     ],
//   },
//   optimization: {
//     splitChunks: {
//       chunks: 'all',
//       name: 'common',
//       filename: '[name].js',
//       minChunks: 4,
//     },
//     usedExports: true,
//   },
//   module: {
//     rules: [
//       {
//         test: /\.js$/,
//         exclude: /node_modules/,
//         use: {
//           loader: 'babel-loader', // All Babel plugins and options will be loaded via the babel.config.js file
//         },
//       },
//     ],
//   },
// }



// // const babelLoader = {
// //   test: /\.js$/,
// //   loader: 'babel-loader',
// //   exclude: /node_modules/,
// //   query: {
// //     presets: [
// //       '@babel/preset-react',
// //       [
// //         '@babel/preset-env',
// //         {
// //           corejs: 3,
// //           debug: true,
// //           targets: {
// //             browsers: ['last 2 version', '> .25%', 'ie 11', 'android 4', 'ios 9', 'edge 18'],
// //           },
// //           useBuiltIns: 'usage',
// //           modules: false,
// //         },
// //       ],
// //     ],
// //     plugins: [
// //       '@babel/plugin-external-helpers',
// //       '@babel/plugin-transform-runtime',
// //       '@babel/plugin-proposal-class-properties',
// //     ],
// //   },
// // };



// module.exports = function config(env, argv) {
//   const isProduction = argv.mode === 'production';

//   return [
//     {
//       entry: createEntryConfig(entries),
//       mode: isProduction ? 'production' : 'development',
//       devtool: (() => (isProduction ? 'none' : 'cheap-module-eval-source-map'))(),
//       output: {
//         filename: '[name].js',
//         path: path.resolve(__dirname),
//         chunkLoadingGlobal: 'wpJsonpIntercept',
//       },
//       resolve: {
//         // Allow common modules in the root module to be referenced.
//         modules: [
//           path.resolve(__dirname, 'js/src'),
//           path.resolve(__dirname, 'node_modules'),
//           'node_modules',
//         ],
//       },
//       optimization: {
//         splitChunks: {
//           cacheGroups: {
//             commons: {
//               name: 'modules/intercept_core/js/dist/interceptCommon',
//               chunks: 'initial',
//               minChunks: 4,
//             }
//           }
//         }
//       },
//       // plugins: [
//       //   new BundleAnalyzerPlugin()
//       // ],
//       externals: (() => {
//         const prod = {
//           react: 'React',
//           'react-dom': 'ReactDOM',
//           interceptTheme: 'interceptTheme',
//           interceptClient: 'interceptClient',
//           Drupal: 'Drupal',
//           drupalSettings: 'drupalSettings',
//           jQuery: 'jQuery',
//           moment: 'moment',
//           redis: 'redis',
//         };
//         const dev = {
//           interceptTheme: 'interceptTheme',
//           interceptClient: 'interceptClient',
//           Drupal: 'Drupal',
//           drupalSettings: 'drupalSettings',
//           jQuery: 'jQuery',
//           redis: 'redis',
//           moment: 'moment',
//         };

//         return isProduction ? prod : dev;
//       })(),
//       module: {
//         rules: [
//           babelLoader,
//           { test: /\.css$/, loader: 'style-loader!css-loader' }
//         ],
//       },
//     },

//     //
//     // interceptClient.js
//     //   This should function as a standalone library so it needs its own config.
//     //
//     {
//       entry: {
//         'modules/intercept_core/js/dist/interceptClient':
//           './modules/intercept_core/js/src/interceptClient.js',
//       },
//       mode: isProduction ? 'production' : 'development',
//       devtool: (() => (isProduction ? 'none' : 'cheap-module-eval-source-map'))(),
//       output: {
//         filename: '[name].js',
//         path: path.resolve(__dirname),
//         libraryTarget: 'umd',
//         library: 'interceptClient',
//       },
//       resolve: {
//         // Allow common modules in the root module to be referenced.
//         modules: [
//           path.resolve(__dirname, 'js/src'),
//           path.resolve(__dirname, 'node_modules'),
//           'node_modules',
//         ],
//       },
//       // plugins: [
//       //   new BundleAnalyzerPlugin()
//       // ],
//       externals: (() => {
//         const prod = {
//           Drupal: 'Drupal',
//           drupalSettings: 'drupalSettings',
//           moment: 'moment',
//           redis: 'redis',
//           react: 'React',
//           'react-dom': 'ReactDOM',
//         };
//         const dev = {
//           Drupal: 'Drupal',
//           drupalSettings: 'drupalSettings',
//           moment: 'moment',
//           redis: 'redis',
//         };

//         return isProduction ? prod : dev;
//       })(),
//       module: {
//         rules: [babelLoader],
//       },
//     },

//     //
//     // interceptTheme.js
//     //   This should function as a standalone library so it needs its own config.
//     //
//     {
//       entry: {
//         'modules/intercept_core/js/dist/interceptTheme':
//           './modules/intercept_core/js/src/interceptTheme.js',
//       },
//       mode: isProduction ? 'production' : 'development',
//       devtool: (() => (isProduction ? 'none' : 'cheap-module-eval-source-map'))(),
//       output: {
//         filename: '[name].js',
//         path: path.resolve(__dirname),
//         libraryTarget: 'umd',
//       },
//       resolve: {
//         // Allow common modules in the root module to be referenced.
//         modules: [path.resolve(__dirname, 'js/src'), 'node_modules'],
//       },
//       externals: (() => {
//         const prod = {
//           Drupal: 'Drupal',
//           drupalSettings: 'drupalSettings',
//           react: 'React',
//           'react-dom': 'ReactDOM',
//         };
//         const dev = {
//           Drupal: 'Drupal',
//           drupalSettings: 'drupalSettings',
//         };

//         return isProduction ? prod : dev;
//       })(),
//       module: {
//         rules: [babelLoader],
//       },
//     },
//   ].map(smp.wrap);
// };
