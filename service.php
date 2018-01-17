<?php

include "config.php";

$mysqli = new mysqli($mysql_host, $mysql_user, $mysql_pass, $mysql_database);

if (isset($_REQUEST["unit"])){
    $unit = isset($_REQUEST["unit"])?$_REQUEST["unit"]:"d";
    $from = isset($_REQUEST["from"])?$_REQUEST["from"]:null;
    $to = isset($_REQUEST["to"])?$_REQUEST["to"]:null;

    $fields = [];

    if ($unit != "d"){
        /* for non-lower aggregation unit, we need to use proper aggregation method */
        $fields[] = "min(dat) as dat";
        $fields[] = "min(bid) as bid";
        $fields[] = "max(ask) as ask";
    } else {
        /* for lower-level data, we return actual values in non-aggregated form */
        $fields[] = "dat";
        $fields[] = "bid";
        $fields[] = "ask";
    }
    /* apply mysql grouping based on unit */
    $group_by = "";
    if ($unit == "y"){
        /* group by years */
        $group_by = "date_format(dat, '%Y')";
    } else if ($unit == "m"){
        /* group by months */
        $group_by = "date_format(dat, '%Y%m')";
    }
    /* timechart works more optimally if data is sorted on the db side */
    $q = "select " . implode(",", $fields) . " from rate " . ($group_by?"group by $group_by":"") . " order by dat asc";

    $result = $mysqli->query($q);
    while ($result && ($row = $result->fetch_array())){
        $out[] = [strtotime($row["dat"])*1000, (float)$row["bid"], (float)$row["ask"]];
    }
    $response = ["unit" => $unit, "values" => $out, "from" => $from, "to" => $to]; 

    header("Content-type: application/json");
    echo json_encode($response);
    exit;
}
header($_SERVER['SERVER_PROTOCOL'] . ' 500 Internal Server Error', true, 500);
echo "Invalid request";
