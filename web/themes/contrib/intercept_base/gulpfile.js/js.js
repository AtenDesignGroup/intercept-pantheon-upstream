const gulp = require('gulp');
const { dest, src, watch, lastRun } = gulp;
const webpack = require('webpack-stream');
const webpackCompiler = require('webpack');
const webpackConfig = require('../webpack.config');
const named = require('vinyl-named');
const path = require('path');
const argv = require('yargs').argv;

const config = require('../config').js;
const browserSync = require('../config').browserSync.instance;

const jsTask = function () {
  console.log('jsTask', config.inputs);
  return src(config.inputs, {
    since: lastRun(jsTask),
  })
    .pipe(
      named((file) => {
        return path.relative(config.base, file.path).slice(0, -3);
      })
    )
    .pipe(webpack(webpackConfig(argv), webpackCompiler))
    .pipe(dest('build'))
    .pipe(browserSync.stream());
};

const jsWatch = function (cb) {
  watch(
    config.inputs,
    {
      ignoreInitial: false,
    },
    jsTask
  );
  cb();
};

gulp.task('jsWatch', jsWatch);
gulp.task('js', argv.watch ? jsWatch : jsTask);
