var gulp = require('gulp');
var config = require('../config').browserSync;
var browserSync = config.instance;
const argv = require('yargs').argv;

gulp.task('browser-sync', function() {
  browserSync.init(null, {
    proxy: argv.proxy || config.proxy || 'localhost:7000',
    files: argv.files || config.files || ['public/**/*.*'],
    browser: argv.browser || config.browser || 'google chrome',
    port: argv.port || config.port || 7000,
    open: argv.open || config.open || 'local',
    reloadDebounce: argv.reloadDebounce || config.reloadDebounce || 0,
    reloadDelay: argv.reloadDelay || config.reloadDelay || 0,
    reloadThrottle: argv.reloadThrottle || config.reloadThrottle || 0
  });

  // gulp.watch(config.files).on('change', browserSync.reload('*.js'));
});
