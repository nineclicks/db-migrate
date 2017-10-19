<?php

function doDrivers($mysql, $pgsql, $queries) {
  $stmt = $mysql->prepare($queries['get-drivers']);
  $stmt->execute();
  $drivers = $stmt->fetchAll();
  foreach ($drivers as $driver) {
    print_r($driver);
    $stmt = $pgsql->prepare($queries['add-driver!']);
    $stmt->execute([
      $driver['driver_id'],
      $driver['driver_name'],
      "",
      $driver['cell_phone'],
      $driver['alt_phone'],
      $driver['home_phone'],
      $driver['fax'],
      $driver['email'],
      $driver['type'],
      $driver['is_cd'],
      $driver['active'],
      0,
      $driver['notes'],
      $driver['why_dont_use'],
      $driver['start_date'],
      0,
      $driver['trk_reg'],
      $driver['dl_exp_date'],
      $driver['med_exp_date'],
    ]);
  }
}
