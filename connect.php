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
  $pgsql->query('DELETE FROM "order"    WHERE id > 0');
  $pgsql->query('DELETE FROM "vehicle"  WHERE id > 0');
  $pgsql->query('DELETE FROM "transfer" WHERE id > 0');
  $pgsql->query('DELETE FROM "bol"      WHERE id > 0');
  $pgsql->query('DELETE FROM "driver"   WHERE id > 0');
  $pgsql->query('DELETE FROM "location" WHERE id > 0');
  $pgsql->query('DELETE FROM "note"     WHERE id > 0');
}
