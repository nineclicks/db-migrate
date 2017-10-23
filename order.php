<?php
function addOrGetLocation($dbcon, $queries, $cid,$name,$street_address,$city,$state,$zip,$country,$address_type,$non_us_street_address,$lat,$lng,$date_created) {
  $stmt = $dbcon->prepare($queries['insert-location!']);
  try {
    $stmt->execute([$cid,$name,$street_address,$city,$state,substr($zip,0,5),$country,$address_type,$non_us_street_address,$lat,$lng,$date_created]);
  } catch (PDOEXCEPTION $e) {
    if (strpos($e, 'duplicate key value') === false) {
      warn("addOrGetLocation exception: . $e");
    }
  }
  $stmt = $dbcon->prepare($queries['get-location-by-cid']);
  $stmt->execute([$cid]);
  $pgsqlLocation = $stmt->fetchAll();
  if (count($pgsqlLocation) < 1) {
    warn("Location '$cid' not found even though it should have just been added.");
  } else {
    return $pgsqlLocation[0]['id'];
  }
}

function doOrders($mysql, $pgsql, $queries) {
  $stmt = $pgsql->query($queries['get-carmax-id']);
  $carmax_id = $stmt->fetchAll()[0]['id'];

  $stmt = $mysql->query($queries['get-orders']);
  $orders = $stmt->fetchAll();
  $stmt = $mysql->query($queries['get-vehicle-count']);
  $firstVehicleCount = $stmt->fetchAll()[0]['count'];
  $missing_po = 1;
  $firstOrderCount = count($orders);
  echo blue($firstOrderCount) . " orders found.\n";
  echo blue($firstVehicleCount) . " vehicles found.\n";
  $orderCount = 0;
  $vehicleCount = 0;
  $vehicleSkip = 0;
  $orderMissingVeh = 0;
  foreach ($orders as $order) {
    $mysql_order_id = $order['id'];
    $stmt = $mysql->prepare($queries['get-vehicles-by-order-id']);
    $stmt->execute([$mysql_order_id]);
    $vehicles = $stmt->fetchAll();
    $stmt = $mysql->prepare($queries['get-location-by-id']);
    $stmt->execute([$order['pickup_location_id']]);
    $pickup_location = $stmt->fetchAll()[0];
    $stmt = $mysql->prepare($queries['get-location-by-id']);
    $stmt->execute([$order['delivery_location_id']]);
    $dropoff_location = $stmt->fetchAll()[0];

    if (count($vehicles) < 1) {
      $orderMissingVeh++;
      continue; // Skip orders with no vehicles
    }

    $pickup_location_cid = $vehicles[0]['pickup_location_id'];
    $dropoff_location_cid = $vehicles[0]['delivery_location_id'];

    if (is_null($pickup_location_cid) or is_null($dropoff_location_cid)) {
      warn("Order $mysql_order_id missing pickup or dropoff location id in vehicle '{$vehicles[0]['vin']}', skipping");
      warn(count($vehicles) . " vehicles being skipped due to not adding order $mysql_order_id.");
      $vehicleSkip += count($vehicles);
      continue;
    }

    $pickup_location_id = addOrGetLocation(
      $pgsql,
      $queries,
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
      $queries,
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

    $stmt = $pgsql->prepare($queries['add-order!']);
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
        (int)($order['cod'] == "1"),
        (int)($order['cod'] == "1"),
        'OTHER_TO_STORE',
        $order['eta'],
        $order['date_created'],
        $order['date_deleted']
      ]);
    } catch (Exception $e) {
      warn("Error inserting order $mysql_order_id: $e");
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

      $stmt = $pgsql->prepare($queries['add-vehicle!']);
      try {
        $stmt->execute([
          $order_id,
          $vehicle['year'],
          $vehicle['make'],
          $vehicle['model'],
          $vehicle['vin'],
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
        $vehicle_id = $stmt->fetchAll()[0]['id'];
        $stmt = $pgsql->prepare($queries['add-transfer!']);
        $stmt->execute([$vehicle_id, $pickup_location_id, $dropoff_location_id]);
        $vehicleCount++;
      } catch (Exception $e) {
        warn("Error adding vehicle: $e");
        print_r($vehicle);
      }
    }
    $orderCount++;
  }
  echo blue($orderCount) . " orders copied.\n";
  echo blue($vehicleCount) . " vehicles copied.\n";
  echo blue($orderMissingVeh) . " orders with no vehicles.\n";
  echo blue($firstOrderCount - $orderCount - $orderMissingVeh) . " orders not added because of errors.\n";
  echo blue($vehicleSkip) . " vehicles skipped due to order being skipped.\n";
  echo blue($firstVehicleCount - $vehicleCount - $vehicleSkip) . " other vehicles not copied.\n";
}
