<?php
include_once('term.php');
confirm();
include_once('db.php');
include_once('order.php');
include_once('driver.php');
include_once('bol.php');

$mysql = connection("mysql", "127.0.0.1", "l43qdmlx_orders", "root",     "");
$pgsql = connection("pgsql", "127.0.0.1", "cvat",            "postgres", "");

$queries = parseQueries('queries.sql');

clearDestDB($pgsql, $queries);
doDrivers ($mysql, $pgsql, $queries);
doOrders  ($mysql, $pgsql, $queries);
doBols    ($mysql, $pgsql, $queries);
