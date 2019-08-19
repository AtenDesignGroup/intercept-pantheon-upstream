<?php

/**
 * Always ignore these modules on export/import.
 */
$command_specific['config-export']['skip-modules'] = [
  'devel',
  'kint',
  'stage_file_proxy',
  'mailog',
];
$command_specific['config-import']['skip-modules'] = [
  'devel',
  'kint',
  'stage_file_proxy',
  'mailog',
];

/**
 * If there is a local drushrc file, then include it.
 */
$local_drushrc = __DIR__ . "/drushrc.local.php";
if (file_exists($local_drushrc)) {
  include $local_drushrc;
}
