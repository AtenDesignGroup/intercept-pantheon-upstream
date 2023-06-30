<?php

declare(strict_types = 1);

// @codingStandardsIgnoreFile
/**
 * @file
 * A database agnostic dump for testing purposes.
 *
 * This file was generated by the Drupal 8.0 db-tools.php script.
 */

use Drupal\Core\Database\Database;

$connection = Database::getConnection();

$connection->schema()->createTable('sms_user', array(
  'fields' => array(
    'uid' => array(
      'type' => 'int',
      'not null' => TRUE,
      'size' => 'normal',
      'unsigned' => TRUE,
    ),
    'delta' => array(
      'type' => 'int',
      'not null' => TRUE,
      'size' => 'normal',
      'unsigned' => TRUE,
    ),
    'number' => array(
      'type' => 'varchar',
      'not null' => TRUE,
      'length' => '32',
    ),
    'status' => array(
      'type' => 'int',
      'not null' => TRUE,
      'size' => 'normal',
      'unsigned' => TRUE,
    ),
    'code' => array(
      'type' => 'varchar',
      'not null' => TRUE,
      'length' => '16',
    ),
    'gateway' => array(
      'type' => 'text',
      'not null' => FALSE,
      'size' => 'normal',
    ),
  ),
  'primary key' => array(
    'uid',
    'delta',
  ),
  'mysql_character_set' => 'utf8',
));

$connection->insert('sms_user')
->fields(array(
  'uid',
  'delta',
  'number',
  'status',
  'code',
  'gateway',
))
->values(array(
  'uid' => '40',
  'delta' => '0',
  'number' => '1234567890',
  'status' => '2',
  'code' => '',
  'gateway' => 'N;',
))
->values(array(
  'uid' => '41',
  'delta' => '0',
  'number' => '87654321190',
  'status' => '1',
  'code' => '8002',
  'gateway' => 'N;',
))
->execute();

$connection->insert('users')
->fields(array(
  'uid',
  'name',
  'pass',
  'mail',
  'created',
  'access',
  'login',
  'status',
  'init',
))
->values(array(
  'uid' => '40',
  'name' => 'joe',
  'pass' => '63a9f0ea7bb98050796b649e85481845',
  'mail' => 'joe@localhost',
  'created' => '1505771205',
  'access' => '1505772205',
  'login' => '1505771593',
  'status' => '1',
  'init' => 'joe@localhost',
))
->values(array(
  'uid' => '41',
  'name' => 'jill',
  'pass' => '671cc45b3e2c6eb751d6a554dc5a5fe7',
  'mail' => 'jill@example.com',
  'created' => '1391150052',
  'access' => '1391259672',
  'login' => '1391152253',
  'status' => '1',
  'init' => 'jill@example.com',
))
->values(array(
  'uid' => '42',
  'name' => 'jack',
  'pass' => '93a70546e6c032c135499fed70cfe438',
  'mail' => 'jack@example.com',
  'created' => '1391150053',
  'access' => '1391259673',
  'login' => '1391152254',
  'status' => '1',
  'init' => 'jack@example.com',
))
->execute();
