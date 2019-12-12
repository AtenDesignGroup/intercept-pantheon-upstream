var browserSync = require('browser-sync');
var path = require('path');

var themeDir = './';
var sourceDir = path.join(themeDir, '/src');
var buildDir = path.join(themeDir, '/build');
var iconsDir = path.join(themeDir, '/images/icons');
var componentsDir = path.join(themeDir, '/components');
var templatesDir = path.join(themeDir, '/templates');
var incPaths = [
  './node_modules/breakpoint-sass/stylesheets',
  themeDir + '/partials'
];
var host = 'intercept.test';

module.exports = {
  css: {
    inputs: [
      themeDir + '/components/**/*.scss',
      themeDir + '/libraries/**/*.scss',
      themeDir + '/partials/**/*.scss'
    ],
    output: buildDir,
    base: themeDir,
    sourcemapsDir: '.',
    options: {
      errLogToConsole: true,
      includePaths: incPaths
    },
    autoprefixer: {
      cascade: true,
      grid: true,
      flexbox: true,
      supports: true,
    },
  },
  watch: {
    css: [sourceDir + '/scss/**/*.scss'],
    js: sourceDir + '/js/**/*.{js,jsx}',
  },
  browserSync: {
    instance: browserSync.create(),
    proxy: process.env.BSPROXY || host,
    files: [
      themeDir + '/**/*.theme',
      themeDir + '/**/*.twig',
      themeDir + '/**/*.php',
      themeDir + '/**/*.yml',
    ],
    reloadDebounce: 0,
    reloadDelay: 0,
    reloadThrottle: 0
  },
  js: {
    theme: {
      inputs: [
        themeDir + '/components/**/*.js',
        themeDir + '/libraries/*/*.js'
      ],
      output: buildDir,
      base: themeDir,
      babelPresets: [
        'babel-preset-es2015',
        'babel-preset-react'
      ],
      babelPlugins: [],
      paths: [path.resolve(process.cwd(), './node_modules')],
      commonDir: buildDir + '/libraries/global'
    },
    filesBundles: sourceDir + '/js/*.js',
    filesSource: sourceDir + '/js/**/*.js',
    filesBuild: buildDir + '/js',
    babelPresets: [
      './node_modules/babel-preset-es2015',
      './node_modules/babel-preset-react'
    ],
    babelPlugins: []
  },
  svg: {
    filesSource: sourceDir + '/img/svg',
    filesBuild: buildDir + '/img/svg'
  },
  svgSprite: {
    inputs: iconsDir + '/*.svg',
    templateOutputDir: path.join(componentsDir, '/icons'),
    templateOutputFile: 'sprite.svg.twig',
    svgOutputDir: path.join(buildDir, '/images'),
    svgOutputFile: 'sprite.svg'
  }
};
