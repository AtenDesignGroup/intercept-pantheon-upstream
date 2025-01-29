var gulp = require('gulp');
var config = require('../config').browserSync;
var browserSync = config.instance;
const argv = require('yargs').argv;

gulp.task('browser-sync', function(cb) {
  browserSync.init(null, {
    proxy:
      argv.proxy || process.env.BS_PROXY || config.proxy || 'localhost:7000',
    https: argv.https || process.env.BS_HTTPS || config.https || true,
    files: argv.files ||
      process.env.BS_FILES ||
      config.files || ['public/**/*.*'],
    browser:
      argv.browser ||
      process.env.BS_BROWSER ||
      config.browser ||
      'google chrome',
    port: argv.port || process.env.BS_PORT || config.port || 7000,
    open: argv.open || process.env.BS_OPEN || config.open || 'local',
    reloadDebounce:
      argv.reloadDebounce ||
      process.env.BS_DEBOUNCE ||
      config.reloadDebounce ||
      0,
    reloadDelay:
      argv.reloadDelay || process.env.BS_DELAY || config.reloadDelay || 0,
    reloadThrottle:
      argv.reloadThrottle ||
      process.env.BS_THROTTLE ||
      config.reloadThrottle ||
      0,
  });

  cb();
});
