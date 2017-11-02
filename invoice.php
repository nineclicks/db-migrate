<?php

function doInvoices($mysql, $pgsql, $queries) {
  $invoiceCount = 0;
  $vehicleCount = 0;
  $stmt = $pgsql->prepare($queries['get-vehicles-with-bols']);
  $stmt->execute();
  $bol_vehs = $stmt->fetchAll(PDO::FETCH_COLUMN|PDO::FETCH_GROUP);
  foreach($bol_vehs as $bol_id => $veh_list) {
    $stmt = $pgsql->prepare($queries['get-order-by-bol-id']);
    $stmt->execute([$bol_id]);
    $order = $stmt->fetchAll()[0];

    $stmt = $pgsql->prepare($queries['add-invoice!']);
    $stmt->execute([
      $order['base_cost'],
      $order['fuel_surcharge_percent'],
      $order['fuel_surcharge_amt'],
      $order['price_per_load'],
      $order['price_per_unit'],
      $order['additional_charge'],
      $order['additional_charge_desc'],
      $order['bol_date_created']
    ]);
    $invoice_id = $order = $stmt->fetchAll()[0]['id'];
    $invoiceCount++;

    foreach ($veh_list as $vehicle_id) {
      $stmt = $pgsql->prepare($queries['update-vehicle-invoice-id!']);
      $stmt->execute([$invoice_id, $vehicle_id]);
      $vehicleCount++;
    }
  }
  echo blue($invoiceCount) . " invoices created.\n";
  echo blue($vehicleCount) . " vehicles assigned to invoices.\n";
}
