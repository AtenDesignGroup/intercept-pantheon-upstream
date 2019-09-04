'use strict';

const argv = require('yargs').argv;
const autoprefixer = require('gulp-autoprefixer');
const browserSync = require('../config').browserSync.instance;
const gulp = require('gulp');
const sass = require('gulp-sass');

const config = require('../config').css;

const scssTask = function() {
  return gulp
    // Find all `.scss` files from the `stylesheets/` folder
    .src(config.inputs, { base: config.base })
    // Run Sass on those files
    .pipe(sass(config.options).on('error', sass.logError))
    // Add CSS hacks for older browsers
    .pipe(autoprefixer(config.autoprefixer))
    // Write the resulting CSS in the output folder
    .pipe(gulp.dest(config.output))
    // Update browser-sync
    .pipe(browserSync.stream());
};

const scssWatch = function () {
  scssTask();
  return gulp.watch(config.inputs, ['scss']);
};

gulp.task('scss', scssTask);
gulp.task('css', argv.watch ? scssWatch : scssTask);
