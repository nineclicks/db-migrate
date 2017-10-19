<?php
include_once('db.php');
include_once('order.php');

$mysql = connection("mysql","127.0.0.1","l43qdmlx_orders","root","");
$pgsql = connection("pgsql","127.0.0.1","cvat","postgres","");
$queries = parseQueries('queries.sql');

clearDestDB($pgsql, $queries);
doOrders($mysql, $pgsql, $queries);
