<?php

$limit = 30;
$missing_po = 1;
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
    $stmt->execute([$cid,$name,$street_address,$city,$state,substr($zip,0,5),$country,$address_type,$non_us_street_address,$lat,$lng,$date_created]);
  } catch (PDOEXCEPTION $e) {
    if (strpos($e, 'duplicate key value') === false) {
      echo "addOrGetLocation:\n";
      echo $e . "\n\n";
    }
  }
  $stmt = $dbcon->prepare("SELECT * FROM location WHERE cid = ?");
  $stmt->execute([$cid]);
  $pgsqlLocation = $stmt->fetchAll();
  if (count($pgsqlLocation) < 1) {
    echo "addOrGetLocation error:\n";
    echo $cid . " not found.\n\n";
  } else {
    return $pgsqlLocation[0]['id'];
  }
}


$pgsql->query('DELETE FROM "order"    WHERE id > 0');
$pgsql->query('DELETE FROM "vehicle"  WHERE id > 0');
$pgsql->query('DELETE FROM "transfer" WHERE id > 0');
$pgsql->query('DELETE FROM "bol"      WHERE id > 0');
$pgsql->query('DELETE FROM "driver"   WHERE id > 0');
$pgsql->query('DELETE FROM "location" WHERE id > 0');
$pgsql->query('DELETE FROM "note"     WHERE id > 0');

$stmt = $pgsql->query("SELECT id FROM customer WHERE name = 'Carmax';");
$carmax_id = $stmt->fetchAll()[0]['id'];

$stmt = $mysql->query('SELECT * FROM `order`;');
$orders = $stmt->fetchAll();
foreach ($orders as $order) {
  //if ($limit-- < 1) break;
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

  if (is_null($pickup_location_cid) or is_null($dropoff_location_cid)) {
    echo "Order $mysql_order_id missing pickup or dropoff location id in vehicle '{$vehicles[0]['vin']}', skipping\n";
    continue;
  }

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

  $stmt = $pgsql->prepare('INSERT INTO "order" (pickup_location_id, dropoff_location_id, customer_id, fuel_surcharge_amt, fuel_surcharge_percent, price_per_load, price_per_unit, additional_charge, additional_charge_desc, important, cod, cop, move_type, eta, date_created, date_deactivated) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?);');
  try {
    $stmt->execute([
      $pickup_location_id,
      $dropoff_location_id,
      $carmax_id,
      $order['fuel_surcharge_amt'],
      $order['fuel_surcharge_percent'],
      $order['price_per_load'],
      $order['price_per_unit'],
      $order['additional_charge'],
      $order['additional_charge_desc'],
      (int)($order['important'] == "1"),
      (int)($order['cod'] == "1"),
      (int)($order['cod'] == "1"),
      'OTHER_TO_STORE',
      $order['eta'],
      $order['date_created'],
      $order['date_deleted']
    ]);
  } catch (Exception $e) {
    echo "Error inserting order $mysql_order_id, skipping.";
    echo $e . "\n";
    continue;
  }
  $order_id = $pgsql->lastInsertId();

  foreach ($vehicles as $vehicle) {

    if (is_null($vehicle['po_number']))
      $vehicle['po_number'] = "missing_po_" . $missing_po++;

    if (is_null($vehicle['year']))
      $vehicle['year'] = 0;

    if (is_null($vehicle['make']))
      $vehicle['make'] = "";

    if (is_null($vehicle['model']))
      $vehicle['model'] = "";

    $stmt = $pgsql->prepare('INSERT INTO "vehicle" (order_id, year, make, model, vin, type, classification, po_number, move_id, curb_weight, doors, move_reason, important, promise_date, date_created, date_cancelled) VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?);');
    try {
      $stmt->execute([
        $order_id,
        $vehicle['year'],
        $vehicle['make'],
        $vehicle['model'],
        $vehicle['vin'],
        $vehicle['type'],
        $vehicle['classification'],
        $vehicle['po_number'],
        $vehicle['transfer_id'],
        $vehicle['curb_weight'],
        $vehicle['doors'],
        $vehicle['move_reason'],
        $vehicle['important'],
        $vehicle['promise_date'],
        $vehicle['date_created'],
        $vehicle['date_deleted']
      ]);
    } catch (Exception $e) {
      echo $e . "\n";
      print_r($vehicle);
      exit();
    }
  }
}
