var browserSync = require('browser-sync');
var path = require('path');

var themeDir = './';
var sourceDir = path.join(themeDir, '/src');
var buildDir = path.join(themeDir, '/build');
var incPaths = [
  './node_modules/breakpoint-sass/stylesheets',
  './partials'
];
var host = 'https://richlandlibrary.lndo.site';

module.exports = {
  css: {
    inputs: [
      themeDir + 'components/**/*.scss',
      themeDir + 'libraries/**/*.scss',
      themeDir + 'partials/**/*.scss'
    ],
    output: buildDir,
    base: themeDir,
    sourcemapsDir: '.',
    options: {
      errLogToConsole: true,
      includePaths: incPaths,
      silenceDeprecations: ['legacy-js-api', 'mixed-decls'],
    },
    autoprefixer: {
      cascade: true,
      grid: true,
    },
  },
  watch: {
    css: [sourceDir + '/scss/**/*.scss'],
    js: sourceDir + '/js/**/*.{js,jsx}',
  },
  browserSync: {
    https: true,
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
    reloadThrottle: 0,
  },
  js: {
    inputs: [
      path.join(themeDir, '/components/**/*.js'),
      path.join(themeDir, '/libraries/**/*.js')
    ],
    output: buildDir,
    base: themeDir,
  },
  svg: {
    filesSource: sourceDir + '/img/svg',
    filesBuild: buildDir + '/img/svg'
  },
};
