<?php

function doBols($mysql, $pgsql, $queries) {
  $stmt = $mysql->prepare($queries['get-bols']);
  $stmt->execute();
  $bols = $stmt->fetchAll();

  $firstBolCount = count($bols);
  echo blue($firstBolCount) . " BOLs found.\n";
  $bolCount = 0;
  foreach ($bols as $bol) {
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
        $bol['arrivced_pickup_time'], 
        $bol['delivered_time'], 
        $bol['date_created'], 
        $bol['date_deleted']
      ]);
    } catch (Exception $e) {
      warn("Problem with bol " . $bol['table_id'] . ", skipping.");
    }
    $bolCount++;
  }
  echo blue($bolCount) . " BOLs copied.\n";
}
