<?php
return [
  'database' => [
    'driver' => 'pdo_mysql',
    'host' => 'localhost',
    'port' => '',
    'charset' => 'utf8mb4',
    'dbname' => 'learn_espocrm_7_2_2',
    'user' => 'root',
    'password' => ''
  ],
  'logger' => [
    'path' => 'data/logs/espo.log',
    'level' => 'WARNING',
    'rotation' => true,
    'maxFileNumber' => 30,
    'printTrace' => false
  ],
  'restrictedMode' => false,
  'webSocketMessager' => 'ZeroMQ',
  'clientSecurityHeadersDisabled' => false,
  'clientCspDisabled' => false,
  'clientCspScriptSourceList' => [
    0 => 'https://maps.googleapis.com'
  ],
  'isInstalled' => true,
  'microtimeInternal' => 1663128270.402415,
  'passwordSalt' => '7f377cefcbd09bb0',
  'cryptKey' => '647889aa648ec6a7eb7eb3fc94549cd3',
  'hashSecretKey' => 'a4e7e22c25a655524f25f59e3d5b3950',
  'actualDatabaseType' => 'mariadb',
  'actualDatabaseVersion' => '10.4.24'
];
