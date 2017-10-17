<?php

$limit = 10;
$mysql = connection("mysql","127.0.0.1","l43qdmlx_orders","root","");
$pgsql = connection("pgsql","127.0.0.1","cvat","postgres","");


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

function addOrGetLocation($dbcon, $cid,$name,$street_address,$city,$state,$zip,$country,$address_type,$non_us_street_address,$lat,$lng,$date_created) {
  $stmt = $dbcon->prepare('INSERT INTO location (cid, name, street_address, city, state, zip, country, address_type, non_us_street_address, lat, lng, date_created)'
    . ' VALUES(?,?,?,?,?,?,?,?,?,?,?,?)');
  try {
    $stmt->execute([$cid,$name,$street_address,$city,$state,$zip,$country,$address_type,$non_us_street_address,$lat,$lng,$date_created]);
  } catch (PDOEXCEPTION $e) {};
  $stmt = $dbcon->query("SELECT * FROM location WHERE cid = '" . $cid . "'");
  $pgsqlLocation = $stmt->fetchAll()[0];
  return $pgsqlLocation['id'];
}


$pgsql->query('DELETE FROM "order"    WHERE id > 0');
$pgsql->query('DELETE FROM "vehicle"  WHERE id > 0');
$pgsql->query('DELETE FROM "transfer" WHERE id > 0');
$pgsql->query('DELETE FROM "bol"      WHERE id > 0');
$pgsql->query('DELETE FROM "driver"   WHERE id > 0');
$pgsql->query('DELETE FROM "location" WHERE id > 0');
$pgsql->query('DELETE FROM "customer" WHERE id > 0');
$pgsql->query('DELETE FROM "note"     WHERE id > 0');

$stmt = $mysql->query('SELECT * FROM `order` where date_deleted is null;');
$orders = $stmt->fetchAll();
foreach ($orders as $order) {
  if ($limit-- < 1) break;
  $mysql_order_id = $order['id'];
  $stmt = $mysql->query('SELECT * FROM vehicle where order_id = ' . $mysql_order_id);
  $vehicles = $stmt->fetchAll();
  $stmt = $mysql->query('SELECT * FROM location where id = ' . $order['pickup_location_id'] . ' limit 1;');
  $pickup_location = $stmt->fetchAll()[0];
  $stmt = $mysql->query('SELECT * FROM location where id = ' . $order['delivery_location_id'] . ' limit 1;');
  $dropoff_location = $stmt->fetchAll()[0];

  if (count($vehicles) < 1) continue; // Skip orders with no vehicles

  $pickup_location_cid = $vehicles[0]['pickup_location_id'];
  $dropoff_location_cid = $vehicles[0]['delivery_location_id'];

  $pickup_location_id = addOrGetLocation(
    $pgsql,
    $pickup_location_cid,
    $pickup_location['name'],
    $pickup_location['street_address'],
    $pickup_location['city'],
    $pickup_location['state'],
    $pickup_location['zip'],
    $pickup_location['country'],
    'COMMERCIAL',
    'false',
    $pickup_location['lat'],
    $pickup_location['lng'],
    $order['date_created']);

  $dropoff_location_id = addOrGetLocation(
    $pgsql,
    $dropoff_location_cid,
    $dropoff_location['name'],
    $dropoff_location['street_address'],
    $dropoff_location['city'],
    $dropoff_location['state'],
    $dropoff_location['zip'],
    $dropoff_location['country'],
    'COMMERCIAL',
    'false',
    $dropoff_location['lat'],
    $dropoff_location['lng'],
    $order['date_created']);


}
