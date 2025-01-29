
const gulp = require('gulp');
const { dest, src, watch } = gulp;
const argv = require('yargs').argv;
const sourcemaps = require('gulp-sourcemaps');
const postcss = require('gulp-postcss');
const browserSync = require('../config').browserSync.instance;
const sass = require('gulp-sass')(require('sass'));
const dependents = require('gulp-dependents');
const { lastRun } = require('gulp');

// Use Dart Sass.
sass.compiler = require('sass');

const config = require('../config').css;

const scssTask = function () {
  const mode = argv.mode || 'production';

  // Find all `.scss` files from the `stylesheets/` folder
  let stream = src(config.inputs, {
    base: config.base,
    since: lastRun(scssTask),
  });

  if (mode === 'development') {
    stream = stream
      // Initialize sourcemaps
      .pipe(sourcemaps.init());
  }

  stream = stream
    .pipe(dependents())
    // Run Sass on those files
    .pipe(mode === 'development'
      // Swallow errors in development mode so they don't crash the watch task.
      ? sass(config.options).on('error', sass.logError)
      // Throw errors in production mode to ensure the build fails.
      : sass(config.options)
    )
    // Add CSS hacks for older browsers
    .pipe(postcss());

  if (mode === 'development') {
    stream = stream
      // Write sourcemaps
      .pipe(sourcemaps.write());
  }

  stream = stream
    // Write the resulting CSS in the output folder
    .pipe(dest(config.output))
    // Update browser-sync
    .pipe(browserSync.stream());

  return stream;
};

const scssWatch = function (cb) {
  watch(
    [
      ...config.inputs,
      config.base + '.postcssrc.js',
    ],
    {
      ignoreInitial: false,
    },
    scssTask
  );
  cb();
};

gulp.task('scss', scssTask);
gulp.task('cssWatch', scssWatch);
gulp.task('css', argv.watch ? scssWatch : scssTask);
