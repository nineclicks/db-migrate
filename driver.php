<?php

function to_date($d) {
  if (is_null($d) || $d === '') {
    return null;
  }
  return date("c", strtotime($d));
}

function doDrivers($mysql, $pgsql, $queries) {
  $stmt = $mysql->prepare($queries['get-drivers']);
  $stmt->execute();
  $drivers = $stmt->fetchAll();
  foreach ($drivers as $driver) {
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
      (int)($driver['is_cd'] == 1),
      (int)($driver['active'] == 1),
      (int)($driver['message'] == 1),
      $driver['notes'],
      $driver['why_dont_use'],
      to_date($driver['start_date']),
      0,
      to_date($driver['trk_reg']),
      to_date($driver['dl_exp_date']),
      to_date($driver['med_exp_date']),
    ]);
  }
}
