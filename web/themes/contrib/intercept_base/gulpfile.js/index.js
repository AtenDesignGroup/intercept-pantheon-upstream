require('dotenv').config();
const argv = require('yargs').argv;
const gulp = require('gulp');

const { parallel } = gulp;

// Load all the tasks.
require('./browser-sync');
require('./css');
require('./js');

let devTasks = [
  'cssWatch',
  'jsWatch',
];

if (argv.serve) {
  devTasks = [
    'browser-sync',
    ...devTasks
  ];
}

gulp.task('dev', parallel(devTasks));
gulp.task('build', parallel('js', 'css'));
