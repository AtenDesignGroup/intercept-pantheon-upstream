const argv = require('yargs').argv;
const config = require('../config').svgSprite;
const gulp = require('gulp');
const path = require('path');
const rename = require('gulp-rename');
const svgmin = require('gulp-svgmin');
const svgstore = require('gulp-svgstore');

const svgTask = function() {
  const entries = gulp
    .src(config.inputs)
    .pipe(svgmin(function (file) {
      const prefix = path.basename(file.relative, path.extname(file.relative));
      return {
        plugins: [{
          cleanupIDs: {
            prefix: prefix + '-',
            minify: true
          }
        }]
      };
    }));

  // Twig template for inline svg.
  entries
    .pipe(svgstore({inlineSvg: true}))
    .pipe(rename(config.templateOutputFile))
    .pipe(gulp.dest(config.templateOutputDir));

  // SVG file for external svg.
  entries
    .pipe(svgstore())
    .pipe(rename(config.svgOutputFile))
    .pipe(gulp.dest(config.svgOutputDir));

  return entries;
};

const svgWatch = function () {
  svgTask();
  return gulp.watch(config.inputs, ['svg']);
};

gulp.task('svg', svgTask);
// Switch between standard and watch tasks.
gulp.task('svg-sprite', argv.watch ? svgWatch : svgTask);
