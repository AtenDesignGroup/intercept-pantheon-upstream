<?php
// phpcs:ignoreFile
/**
 * @file
 * A database agnostic dump for testing purposes.
 *
 * This file was generated by the Drupal 9.2.4 db-tools.php script.
 */

use Drupal\Core\Database\Database;

$connection = Database::getConnection();

$connection->schema()->createTable('system', array(
  'fields' => array(
    'filename' => array(
      'type' => 'varchar',
      'not null' => TRUE,
      'length' => '255',
      'default' => '',
    ),
    'name' => array(
      'type' => 'varchar',
      'not null' => TRUE,
      'length' => '255',
      'default' => '',
    ),
    'type' => array(
      'type' => 'varchar',
      'not null' => TRUE,
      'length' => '12',
      'default' => '',
    ),
    'owner' => array(
      'type' => 'varchar',
      'not null' => TRUE,
      'length' => '255',
      'default' => '',
    ),
    'status' => array(
      'type' => 'int',
      'not null' => TRUE,
      'size' => 'normal',
      'default' => '0',
    ),
    'bootstrap' => array(
      'type' => 'int',
      'not null' => TRUE,
      'size' => 'normal',
      'default' => '0',
    ),
    'schema_version' => array(
      'type' => 'int',
      'not null' => TRUE,
      'size' => 'small',
      'default' => '-1',
    ),
    'weight' => array(
      'type' => 'int',
      'not null' => TRUE,
      'size' => 'normal',
      'default' => '0',
    ),
    'info' => array(
      'type' => 'blob',
      'not null' => FALSE,
      'size' => 'normal',
    ),
  ),
  'primary key' => array(
    'filename',
  ),
  'indexes' => array(
    'system_list' => array(
      'status',
      'bootstrap',
      'type',
      'weight',
      'name',
    ),
    'type_name' => array(
      'type',
      'name',
    ),
  ),
  'mysql_character_set' => 'utf8mb3',
));

$connection->insert('system')
  ->fields(array(
    'filename',
    'name',
    'type',
    'owner',
    'status',
    'bootstrap',
    'schema_version',
    'weight',
    'info',
  ))
  ->values(array(
  'filename' => 'sites/all/modules/contrib/votingapi/votingapi.module',
  'name' => 'votingapi',
  'type' => 'module',
  'owner' => '',
  'status' => '1',
  'bootstrap' => '0',
  'schema_version' => '7203',
  'weight' => '0',
  'info' => 'a:13:{s:4:"name";s:10:"Voting API";s:11:"description";s:47:"Provides a shared voting API for other modules.";s:7:"package";s:6:"Voting";s:4:"core";s:3:"7.x";s:9:"configure";s:29:"admin/config/search/votingapi";s:5:"files";a:5:{i:0;s:21:"votingapi.migrate.inc";i:1;s:20:"tests/votingapi.test";i:2;s:45:"views/votingapi_views_handler_field_value.inc";i:3;s:47:"views/votingapi_views_handler_sort_nullable.inc";i:4;s:46:"views/votingapi_views_handler_relationship.inc";}s:7:"version";s:8:"7.x-2.15";s:7:"project";s:9:"votingapi";s:9:"datestamp";s:10:"1526810587";s:5:"mtime";i:1526810587;s:12:"dependencies";a:0:{}s:3:"php";s:5:"5.2.4";s:9:"bootstrap";i:0;}',
  ))
  ->execute();


$connection->schema()->createTable('votingapi_vote', array(
  'fields' => array(
    'vote_id' => array(
      'type' => 'serial',
      'not null' => TRUE,
      'size' => 'normal',
      'unsigned' => TRUE,
    ),
    'content_type' => array(
      'type' => 'varchar',
      'not null' => TRUE,
      'length' => '64',
      'default' => 'node',
    ),
    'content_id' => array(
      'type' => 'int',
      'not null' => TRUE,
      'size' => 'normal',
      'default' => '0',
      'unsigned' => TRUE,
    ),
    'value' => array(
      'type' => 'float',
      'not null' => TRUE,
      'size' => 'normal',
      'default' => '0',
    ),
    'value_type' => array(
      'type' => 'varchar',
      'not null' => TRUE,
      'length' => '64',
      'default' => 'percent',
    ),
    'tag' => array(
      'type' => 'varchar',
      'not null' => TRUE,
      'length' => '64',
      'default' => 'vote',
    ),
    'uid' => array(
      'type' => 'int',
      'not null' => TRUE,
      'size' => 'normal',
      'default' => '0',
      'unsigned' => TRUE,
    ),
    'timestamp' => array(
      'type' => 'int',
      'not null' => TRUE,
      'size' => 'normal',
      'default' => '0',
      'unsigned' => TRUE,
    ),
    'vote_source' => array(
      'type' => 'varchar',
      'not null' => FALSE,
      'length' => '255',
    ),
  ),
  'primary key' => array(
    'vote_id',
  ),
  'indexes' => array(
    'content_uid' => array(
      'content_type',
      'content_id',
      'uid',
    ),
    'content_uid_2' => array(
      'content_type',
      'uid',
    ),
    'content_source' => array(
      'content_type',
      'content_id',
      'vote_source',
    ),
    'content_value_tag' => array(
      'content_type',
      'content_id',
      'value_type',
      'tag',
    ),
  ),
  'mysql_character_set' => 'utf8mb3',
));

$connection->insert('votingapi_vote')
->fields(array(
  'vote_id',
  'content_type',
  'content_id',
  'value',
  'value_type',
  'tag',
  'uid',
  'timestamp',
  'vote_source',
))
->values(array(
  'vote_id' => '53',
  'content_type' => 'node',
  'content_id' => '8',
  'value' => '77',
  'value_type' => 'percent',
  'tag' => 'vote',
  'uid' => '4',
  'timestamp' => '1635759875',
  'vote_source' => '127.0.0.1',
))
->values(array(
  'vote_id' => '56',
  'content_type' => 'node',
  'content_id' => '8',
  'value' => '25',
  'value_type' => 'percent',
  'tag' => 'vote',
  'uid' => '2',
  'timestamp' => '1635760044',
  'vote_source' => '127.0.0.1',
))
->values(array(
  'vote_id' => '59',
  'content_type' => 'comment',
  'content_id' => '6',
  'value' => '77',
  'value_type' => 'percent',
  'tag' => 'vote',
  'uid' => '1',
  'timestamp' => '1635760436',
  'vote_source' => '127.0.0.1',
))
->values(array(
  'vote_id' => '60',
  'content_type' => 'node',
  'content_id' => '9',
  'value' => '1',
  'value_type' => 'points',
  'tag' => 'vote',
  'uid' => '1',
  'timestamp' => '1635761780',
  'vote_source' => '127.0.0.1',
))
->values(array(
  'vote_id' => '61',
  'content_type' => 'comment',
  'content_id' => '6',
  'value' => '56',
  'value_type' => 'percent',
  'tag' => 'vote',
  'uid' => '1',
  'timestamp' => '1636347536',
  'vote_source' => '127.0.0.1',
))
->values(array(
  'vote_id' => '62',
  'content_type' => 'comment',
  'content_id' => '7',
  'value' => '100',
  'value_type' => 'percent',
  'tag' => 'vote',
  'uid' => '1',
  'timestamp' => '1636347687',
  'vote_source' => '127.0.0.1',
))
->values(array(
  'vote_id' => '64',
  'content_type' => 'comment',
  'content_id' => '7',
  'value' => '77',
  'value_type' => 'percent',
  'tag' => 'vote',
  'uid' => '3',
  'timestamp' => '1636372210',
  'vote_source' => '127.0.0.1',
))
->values(array(
  'vote_id' => '65',
  'content_type' => 'node',
  'content_id' => '9',
  'value' => '1',
  'value_type' => 'points',
  'tag' => 'vote',
  'uid' => '3',
  'timestamp' => '1636372224',
  'vote_source' => '127.0.0.1',
))
->values(array(
  'vote_id' => '66',
  'content_type' => 'comment',
  'content_id' => '6',
  'value' => '100',
  'value_type' => 'percent',
  'tag' => 'vote',
  'uid' => '3',
  'timestamp' => '1636372256',
  'vote_source' => '127.0.0.1',
))
->values(array(
  'vote_id' => '67',
  'content_type' => 'node',
  'content_id' => '8',
  'value' => '77',
  'value_type' => 'percent',
  'tag' => 'vote',
  'uid' => '3',
  'timestamp' => '1636372266',
  'vote_source' => '127.0.0.1',
))
->execute();


$connection->schema()->createTable('votingapi_cache', array(
  'fields' => array(
    'vote_cache_id' => array(
      'type' => 'serial',
      'not null' => TRUE,
      'size' => 'normal',
      'unsigned' => TRUE,
    ),
    'content_type' => array(
      'type' => 'varchar',
      'not null' => TRUE,
      'length' => '64',
      'default' => 'node',
    ),
    'content_id' => array(
      'type' => 'int',
      'not null' => TRUE,
      'size' => 'normal',
      'default' => '0',
      'unsigned' => TRUE,
    ),
    'value' => array(
      'type' => 'float',
      'not null' => TRUE,
      'size' => 'normal',
      'default' => '0',
    ),
    'value_type' => array(
      'type' => 'varchar',
      'not null' => TRUE,
      'length' => '64',
      'default' => 'percent',
    ),
    'tag' => array(
      'type' => 'varchar',
      'not null' => TRUE,
      'length' => '64',
      'default' => 'vote',
    ),
    'function' => array(
      'type' => 'varchar',
      'not null' => TRUE,
      'length' => '64',
      'default' => '',
    ),
    'timestamp' => array(
      'type' => 'int',
      'not null' => TRUE,
      'size' => 'normal',
      'default' => '0',
      'unsigned' => TRUE,
    ),
  ),
  'primary key' => array(
    'vote_cache_id',
  ),
  'indexes' => array(
    'content' => array(
      'content_type',
      'content_id',
    ),
    'content_function' => array(
      'content_type',
      'content_id',
      'function',
    ),
    'content_tag_func' => array(
      'content_type',
      'content_id',
      'tag',
      'function',
    ),
    'content_vtype_tag' => array(
      'content_type',
      'content_id',
      'value_type',
      'tag',
    ),
    'content_vtype_tag_func' => array(
      'content_type',
      'content_id',
      'value_type',
      'tag',
      'function',
    ),
  ),
  'mysql_character_set' => 'utf8mb3',
));

$connection->insert('votingapi_cache')
->fields(array(
  'vote_cache_id',
  'content_type',
  'content_id',
  'value',
  'value_type',
  'tag',
  'function',
  'timestamp',
))
->values(array(
  'vote_cache_id' => '45',
  'content_type' => 'node',
  'content_id' => '8',
  'value' => '2',
  'value_type' => 'percent',
  'tag' => 'vote',
  'function' => 'count',
  'timestamp' => '1635850483',
))
->values(array(
  'vote_cache_id' => '46',
  'content_type' => 'node',
  'content_id' => '8',
  'value' => '51',
  'value_type' => 'percent',
  'tag' => 'vote',
  'function' => 'average',
  'timestamp' => '1635850483',
))
->values(array(
  'vote_cache_id' => '47',
  'content_type' => 'node',
  'content_id' => '9',
  'value' => '1',
  'value_type' => 'points',
  'tag' => 'vote',
  'function' => 'count',
  'timestamp' => '1635850483',
))
->values(array(
  'vote_cache_id' => '48',
  'content_type' => 'node',
  'content_id' => '9',
  'value' => '1',
  'value_type' => 'points',
  'tag' => 'vote',
  'function' => 'average',
  'timestamp' => '1635850483',
))
->values(array(
  'vote_cache_id' => '49',
  'content_type' => 'node',
  'content_id' => '9',
  'value' => '1',
  'value_type' => 'points',
  'tag' => 'vote',
  'function' => 'sum',
  'timestamp' => '1635850483',
))
->values(array(
  'vote_cache_id' => '50',
  'content_type' => 'node',
  'content_id' => '9',
  'value' => '1',
  'value_type' => 'points',
  'tag' => 'vote',
  'function' => 'positives',
  'timestamp' => '1635850483',
))
->values(array(
  'vote_cache_id' => '53',
  'content_type' => 'comment',
  'content_id' => '6',
  'value' => '2',
  'value_type' => 'percent',
  'tag' => 'vote',
  'function' => 'count',
  'timestamp' => '1636371538',
))
->values(array(
  'vote_cache_id' => '54',
  'content_type' => 'comment',
  'content_id' => '6',
  'value' => '66.5',
  'value_type' => 'percent',
  'tag' => 'vote',
  'function' => 'average',
  'timestamp' => '1636371538',
))
->values(array(
  'vote_cache_id' => '55',
  'content_type' => 'comment',
  'content_id' => '7',
  'value' => '1',
  'value_type' => 'percent',
  'tag' => 'vote',
  'function' => 'count',
  'timestamp' => '1636371538',
))
->values(array(
  'vote_cache_id' => '56',
  'content_type' => 'comment',
  'content_id' => '7',
  'value' => '100',
  'value_type' => 'percent',
  'tag' => 'vote',
  'function' => 'average',
  'timestamp' => '1636371538',
))
->execute();


$connection->schema()->createTable('node', array(
  'fields' => array(
    'nid' => array(
      'type' => 'serial',
      'not null' => TRUE,
      'size' => 'normal',
      'unsigned' => TRUE,
    ),
    'vid' => array(
      'type' => 'int',
      'not null' => FALSE,
      'size' => 'normal',
      'unsigned' => TRUE,
    ),
    'type' => array(
      'type' => 'varchar',
      'not null' => TRUE,
      'length' => '32',
      'default' => '',
    ),
    'language' => array(
      'type' => 'varchar',
      'not null' => TRUE,
      'length' => '12',
      'default' => '',
    ),
    'title' => array(
      'type' => 'varchar',
      'not null' => TRUE,
      'length' => '255',
      'default' => '',
    ),
    'uid' => array(
      'type' => 'int',
      'not null' => TRUE,
      'size' => 'normal',
      'default' => '0',
    ),
    'status' => array(
      'type' => 'int',
      'not null' => TRUE,
      'size' => 'normal',
      'default' => '1',
    ),
    'created' => array(
      'type' => 'int',
      'not null' => TRUE,
      'size' => 'normal',
      'default' => '0',
    ),
    'changed' => array(
      'type' => 'int',
      'not null' => TRUE,
      'size' => 'normal',
      'default' => '0',
    ),
    'comment' => array(
      'type' => 'int',
      'not null' => TRUE,
      'size' => 'normal',
      'default' => '0',
    ),
    'promote' => array(
      'type' => 'int',
      'not null' => TRUE,
      'size' => 'normal',
      'default' => '0',
    ),
    'sticky' => array(
      'type' => 'int',
      'not null' => TRUE,
      'size' => 'normal',
      'default' => '0',
    ),
    'tnid' => array(
      'type' => 'int',
      'not null' => TRUE,
      'size' => 'normal',
      'default' => '0',
      'unsigned' => TRUE,
    ),
    'translate' => array(
      'type' => 'int',
      'not null' => TRUE,
      'size' => 'normal',
      'default' => '0',
    ),
    'moderate' => array(
      'type' => 'int',
      'not null' => TRUE,
      'size' => 'normal',
      'default' => '0',
    ),
  ),
  'primary key' => array(
    'nid',
  ),
  'unique keys' => array(
    'vid' => array(
      'vid',
    ),
  ),
  'indexes' => array(
    'node_changed' => array(
      'changed',
    ),
    'node_created' => array(
      'created',
    ),
    'node_frontpage' => array(
      'promote',
      'status',
      'sticky',
      'created',
    ),
    'node_status_type' => array(
      'status',
      'type',
      'nid',
    ),
    'node_title_type' => array(
      'title',
      array(
        'type',
        '4',
      ),
    ),
    'node_type' => array(
      array(
        'type',
        '4',
      ),
    ),
    'uid' => array(
      'uid',
    ),
    'tnid' => array(
      'tnid',
    ),
    'translate' => array(
      'translate',
    ),
    'language' => array(
      'language',
    ),
  ),
  'mysql_character_set' => 'utf8mb3',
));

$connection->insert('node')
->fields(array(
  'nid',
  'vid',
  'type',
  'language',
  'title',
  'uid',
  'status',
  'created',
  'changed',
  'comment',
  'promote',
  'sticky',
  'tnid',
  'translate',
  'moderate',
))
->values(array(
  'nid' => '8',
  'vid' => '9',
  'type' => 'article',
  'language' => 'und',
  'title' => 'TEst 11',
  'uid' => '1',
  'status' => '1',
  'created' => '1634799245',
  'changed' => '1634799245',
  'comment' => '2',
  'promote' => '1',
  'sticky' => '0',
  'tnid' => '0',
  'translate' => '0',
  'moderate' => '0',
))
->values(array(
  'nid' => '9',
  'vid' => '10',
  'type' => 'page',
  'language' => 'und',
  'title' => 'something just like this.',
  'uid' => '1',
  'status' => '1',
  'created' => '1635761774',
  'changed' => '1636347604',
  'comment' => '2',
  'promote' => '0',
  'sticky' => '0',
  'tnid' => '0',
  'translate' => '0',
  'moderate' => '0',
))
->execute();


$connection->schema()->createTable('comments', array(
  'fields' => array(
    'cid' => array(
      'type' => 'serial',
      'not null' => TRUE,
      'description' => 'Primary Key: Unique comment ID.',
    ),
    'pid' => array(
      'type' => 'int',
      'not null' => TRUE,
      'default' => 0,
      'description' => 'The {comments}.cid to which this comment is a reply. If set to 0, this comment is not a reply to an existing comment.',
    ),
    'nid' => array(
      'type' => 'int',
      'not null' => TRUE,
      'default' => 0,
      'description' => 'The {node}.nid to which this comment is a reply.',
    ),
    'uid' => array(
      'type' => 'int',
      'not null' => TRUE,
      'default' => 0,
      'description' => 'The {users}.uid who authored the comment. If set to 0, this comment was created by an anonymous user.',
    ),
    'subject' => array(
      'type' => 'varchar',
      'length' => 64,
      'not null' => TRUE,
      'default' => '',
      'description' => 'The comment title.',
    ),
    'comment' => array(
      'type' => 'text',
      'not null' => TRUE,
      'size' => 'big',
      'description' => 'The comment body.',
    ),
    'hostname' => array(
      'type' => 'varchar',
      'length' => 128,
      'not null' => TRUE,
      'default' => '',
      'description' => "The author's host name.",
    ),
    'timestamp' => array(
      'type' => 'int',
      'not null' => TRUE,
      'default' => 0,
      'description' => 'The time that the comment was created, or last edited by its author, as a Unix timestamp.',
    ),
    'status' => array(
      'type' => 'int',
      'unsigned' => TRUE,
      'not null' => TRUE,
      'default' => 0,
      'size' => 'tiny',
      'description' => 'The published status of a comment. (0 = Published, 1 = Not Published)',
    ),
    'format' => array(
      'type' => 'int',
      'size' => 'small',
      'not null' => TRUE,
      'default' => 0,
      'description' => 'The {filter_formats}.format of the comment body.',
    ),
    'thread' => array(
      'type' => 'varchar',
      'length' => 255,
      'not null' => TRUE,
      'description' => "The vancode representation of the comment's place in a thread.",
    ),
    'name' => array(
      'type' => 'varchar',
      'length' => 60,
      'not null' => FALSE,
      'description' => "The comment author's name. Uses {users}.name if the user is logged in, otherwise uses the value typed into the comment form.",
    ),
    'mail' => array(
      'type' => 'varchar',
      'length' => 64,
      'not null' => FALSE,
      'description' => "The comment author's e-mail address from the comment form, if user is anonymous, and the 'Anonymous users may/must leave their contact information' setting is turned on.",
    ),
    'homepage' => array(
      'type' => 'varchar',
      'length' => 255,
      'not null' => FALSE,
      'description' => "The comment author's home page address from the comment form, if user is anonymous, and the 'Anonymous users may/must leave their contact information' setting is turned on.",
    )
  ),
  'indexes' => array(
    'pid'    => array('pid'),
    'nid'    => array('nid'),
    'comment_uid'    => array('uid'),
    'status' => array('status'), // This index is probably unused
  ),
  'primary key' => array('cid'),
  'mysql_character_set' => 'utf8mb3',
));

$connection->insert('comments')
->fields(array(
  'cid',
  'pid',
  'nid',
  'uid',
  'subject',
  'comment',
  'hostname',
  'timestamp',
  'status',
  'format',
  'thread',
  'name',
  'mail',
  'homepage',
))
->values(array(
  'cid' => '6',
  'pid' => '0',
  'nid' => '8',
  'uid' => '1',
  'subject' => 'asfasfasf',
  'comment' => 'asfasfasf',
  'hostname' => '127.0.0.1',
  'timestamp' => '1635168911',
  'status' => '1',
  'format' => '1',
  'thread' => '01/',
  'name' => 'admin',
  'mail' => '',
  'homepage' => '',
))
->values(array(
  'cid' => '7',
  'pid' => '0',
  'nid' => '9',
  'uid' => '1',
  'subject' => 'kjgj',
  'comment' => 'kjgj',
  'hostname' => '127.0.0.1',
  'timestamp' => '1636347674',
  'status' => '1',
  'format' => '1',
  'thread' => '01/',
  'name' => 'admin',
  'mail' => '',
  'homepage' => '',
))
->execute();
