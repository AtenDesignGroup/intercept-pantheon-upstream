const gulp = require('gulp');
const gutil = require('gulp-util');
const glob = require('glob');
const path = require('path');
const source = require('vinyl-source-stream');
const browserify = require('browserify');
const fs = require('fs');
const factor = require('factor-bundle');
const watchify = require('watchify');
const errorify = require('errorify');
const argv = require('yargs').argv;

const config = require('../config').js;
const browserSync = require('../config').browserSync.instance;

const defaults = {
  inputs: '/assets/js/*.js',
  output: '/public/js',
  babelPresets: ['es2015', 'react'],
  babelPlugins: ['transform-object-rest-spread']
};

const options = Object.assign({}, defaults, config.theme);

/**
 * Resolves a destination path the preserves the directory structure
 * of the input file.
 * @param  {string} file     The path to the file being processed.
 * @param  {string} buildDir The bath to the base build directory.
 * @param  {string} base     The path to the common parent directory.
 * @return {string}          The destination file path within buildDir.
 */
function getBuildDest(file, buildDir, base) {
  return path.join(buildDir, path.relative(base, file));
}

/**
 * Resolves a destination folder the preserves the directory structure
 * of the input file.
 * @param  {string} file     The path to the file being processed.
 * @param  {string} buildDir The bath to the base build directory.
 * @param  {string} base     The path to the common parent directory.
 * @return {string}          The destination directory within buildDir.
 */
function getBuildDestDir(file, buildDir, base) {
  return path.dirname(getBuildDest(file, buildDir, base));
}

/**
 * Returns the path to a file if it were in the given directory.
 * @param  {string} file     The path to the file being processed.
 * @param  {string} buildDir The bath to the build directory.
 */
function getFilePathInDirectory(file, buildDir) {
  return path.join(buildDir, path.basename(file));
}

/**
 * Short form of getBuildDest
 * @param  {string} file              The path to the file being processed.
 * @param  {object} options           Task configuration.
 * @param  {string} options.buildDir  The bath to the base build directory.
 * @param  {string} options.base      The path to the common parent directory.
 * @return {string}          The destination directory within buildDir.
 */
function getJSBuildDir(file, options) {
  return getBuildDest(file, options.output, options.base);
}

/**
 * Creates a flattened array of paths from an array of glob patterns.
 * @param  {string[]} patterns An array of glob pattern strings.
 * @return {string[]}          An array of found files.
 */
function globArray(patterns) {
  const filesList = [].concat(patterns).map(p => glob.sync(p));
  return [].concat.apply([], filesList);
}

function isThemeFile(file, themeBase) {
  return file.indexOf(themeBase) !== -1;
}

/**
 * Bundle JS
 */
gulp.task('js', function(cb) {
  // Get a combined list of files.
  const files = globArray([].concat(options.inputs));

  // Set plugins.
  var plugins = [];

  // Enable watching
  if (argv.watch) { plugins.push('watchify'); }

  var b = browserify({
    entries: files,
    cache: {},
    packageCache: {},
    plugin: plugins
  })
  // Exclude global libraries
  .exclude('jquery')
  .exclude('Drupal')
  // Babelify settings
  .transform('babelify', {
    // Browserify tries to resolve presets and plugins relative to the
    // input file if the plugin or preset is provided as a simple name.
    // Since our task and node_modules folder is not an ancestor of the
    // input file, we must pass in pre-resolved paths.
    presets: options.babelPresets.map(require.resolve),
    plugins: options.babelPlugins.map(require.resolve)
  })
  // Split common code out.
  .plugin(factor, { outputs: files.map(
    filePath => {
      var dir = getBuildDestDir(filePath, options.output, options.base);
      if (!fs.existsSync(dir)) {
        fs.mkdirSync(dir);
      }
      return getJSBuildDir(filePath, options);
    })
  })
  .plugin(errorify)
  // Browserify error handler
  .on('error', function(err){
    gutil.log(err.message);
    gutil.beep();
    this.emit('end');
  })
  // Watchify update handler
  .on('update', function(id){
    gutil.log(id);
    bundle();
  })
  // Watchify log handler
  .on('log', function(msg){
    gutil.log(msg);
  });

  // Initial Bundle
  bundle();

  function bundle() {
    b.bundle()
      .pipe(source('common.js'))
      .pipe(gulp.dest(options.commonDir))
      .pipe(browserSync.stream({once: true}));
  }
});
