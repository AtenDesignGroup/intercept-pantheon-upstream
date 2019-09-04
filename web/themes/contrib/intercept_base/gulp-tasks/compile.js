const gulp = require('gulp');
const argv = require('yargs').argv;

let tasks = [
  'css',
  'js',
  'svg-sprite'
];

if (argv.serve) { tasks.push('browser-sync'); }

/**
 * Compile task
 */
gulp.task('compile', tasks);
