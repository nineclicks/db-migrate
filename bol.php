<?php

$bol_statuses = array(
  "accepted_time" => "ACCEPTED",
  "assigned_time" => "ASSIGNED",
  "arrived_pickup_time" => "ARRIVED_PICKUP",
  "delivery_started_time" => "ARRIVED_DELIVERY",
  "in_transit_time" => "INTRANSIT",
  "delivered_time" => "DELIVERED"
);

function doBols($mysql, $pgsql, $queries) {
  $stmt = $mysql->prepare($queries['get-bols']);
  $stmt->execute();
  $bols = $stmt->fetchAll();

  $firstBolCount = count($bols);
  echo blue($firstBolCount) . " BOLs found.\n";
  $bolCount = 0;
  $transferCount = 0;
  $bolStatusCount = 0;
  foreach ($bols as $bol) {
    $mysql_bol_id = $bol['table_id'];
    $stmt = $pgsql->prepare($queries['add-bol!']);
    try {
      @$stmt->execute([
        $bol['driver_id'],
        $bol['shipment_id'], 
        null,
        $bol['p_name'], 
        $bol['p_street_address'], 
        $bol['p_city'], 
        $bol['d_name'], 
        $bol['d_street_address'], 
        $bol['d_city'], 
        $bol['arrived_pickup_time'], 
        $bol['delivered_time'], 
        $bol['date_created'], 
        $bol['date_deleted']
      ]);
      $added_bol_id = $stmt->fetchAll()[0]['id'];
      global $bol_statuses;
      foreach ($bol_statuses as $bol_status => $bol_status_type) {
        if (array_key_exists($bol_status, $bol)) {
          $stmt = $pgsql->prepare($queries['add-bol-status!']);
          $stmt->execute([$added_bol_id, $bol_status_type, $bol[$bol_status]]);
          $bolStatusCount++;
        }
      }
      $stmt = $mysql->prepare($queries['get-vehicles-by-bol-id']);
      $stmt->execute([$mysql_bol_id]);
      $vehicles = $stmt->fetchAll();
      foreach ($vehicles as $vehicle) {
        $stmt = $pgsql->prepare($queries['update-transfer-bol-by-move-id!']);
        $stmt->execute([$added_bol_id, $vehicle['transfer_id']]); 
        $transferCount++;
      }
    } catch (Exception $e) {
      warn("Problem with bol " . $bol['table_id'] . ", skipping.");
    }
    $bolCount++;
  }
  echo blue($bolCount) . " BOLs copied.\n";
  echo blue($transferCount) . " transfers assigned to BOLs.\n";
  echo blue($bolStatusCount) . " status updates added to BOLs.\n";
}
