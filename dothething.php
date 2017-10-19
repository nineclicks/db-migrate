<?php

include_once('db.php');
include_once('order.php');
include_once('driver.php');

$mysql = connection("mysql","127.0.0.1","l43qdmlx_orders","root","");
$pgsql = connection("pgsql","127.0.0.1","cvat","postgres","");

echo "WARNING: This is going to clear your destination database. Type yes to continue.\n";
$line = readLine(": ");
if ($line != "yes") {
  echo "Exiting with no changes.\n";
  exit();
}
echo "Beginning migration.\n";

$queries = parseQueries('queries.sql');

clearDestDB($pgsql, $queries);
doDrivers($mysql, $pgsql, $queries);
doOrders($mysql, $pgsql, $queries);
