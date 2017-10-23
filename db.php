<?php

function connection($dbms, $host, $db, $user, $pass) {
  $dsn = "$dbms:host=$host;dbname=$db";
  $opt = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
  ];
  $pdo = new PDO($dsn, $user, $pass, $opt);
  return $pdo;
}

function clearDestDB($pgsql) {
  echo "Clearing out destination db...";
  $pgsql->query('DELETE FROM "order"    WHERE id > 0');
  $pgsql->query('DELETE FROM "vehicle"  WHERE id > 0');
  $pgsql->query('DELETE FROM "transfer" WHERE id > 0');
  $pgsql->query('DELETE FROM "bol"      WHERE id > 0');
  $pgsql->query('DELETE FROM "driver"   WHERE id > 0');
  $pgsql->query('DELETE FROM "note"     WHERE id > 0');
  $pgsql->query('ALTER SEQUENCE bol_id_seq      RESTART WITH 1');
  $pgsql->query('ALTER SEQUENCE vehicle_id_seq  RESTART WITH 1');
  $pgsql->query('ALTER SEQUENCE transfer_id_seq RESTART WITH 1');
  $pgsql->query('ALTER SEQUENCE order_id_seq    RESTART WITH 1');
  $pgsql->query('ALTER SEQUENCE driver_id_seq   RESTART WITH 1');
  $pgsql->query('ALTER SEQUENCE note_id_seq     RESTART WITH 1');

  $pgsql->query('DELETE FROM "location" l WHERE id <> (SELECT location_id FROM "customer" c WHERE c.name = ' . "'Carmax'" . ')');
  $pgsql->query('SELECT setval(\'location_id_seq\', COALESCE((SELECT MAX(id)+1 FROM location), 1), false)');
  echo "Done.\n";
}

function parseQueries($fn) {
  $str = file_get_contents($fn);
  $rgx = '/--\s*name:\s*(.*?)\s*\n([\s\S]*?)(?:\n\n|\Z)/m';
  preg_match_all($rgx, $str, $matches, PREG_SET_ORDER);
  $queries = [];
  foreach ($matches as $match) {
    $queries[$match[1]] = $match[2];
  }
  return $queries;
}
