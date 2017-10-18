<?php
include_once('connect.php');
include_once('order.php');

$mysql = connection("mysql","127.0.0.1","l43qdmlx_orders","root","");
$pgsql = connection("pgsql","127.0.0.1","cvat","postgres","");

clearDestDB($pgsql);
doOrders($mysql, $pgsql);
