<?php
/*
 * DVDdb database layer - PHP 8.x / mysqli compatibility update.
 * Original application API (doquery/restoarray) is preserved so the
 * rest of DVDdb can remain close to the upstream code.
 */
$sql_vars = array(
    "host" => "localhost",      // SQL server host
    "username" => "xxx",       // Username
    "password" => "xxx",       // Password
    "dbname" => "xxx",         // Database name
    "db" => null,               // mysqli connection handle
);

function sql_conn()
{
    global $sql_vars;

    mysqli_report(MYSQLI_REPORT_OFF);
    $db = mysqli_connect(
        $sql_vars["host"],
        $sql_vars["username"],
        $sql_vars["password"],
        $sql_vars["dbname"]
    );

    if ($db === false) {
        die("Database connection failed: " . mysqli_connect_error());
    }

    $sql_vars["db"] = $db;
}

function doquery($query)
{
    global $sql_vars;

    $result = mysqli_query($sql_vars["db"], $query);
    return $result;
}

/* Replacement for the removed mysql_result() helper used by DVDdb. */
function db_result($result, $row = 0, $field = 0)
{
    if (!($result instanceof mysqli_result)) {
        return false;
    }
    if (!mysqli_data_seek($result, (int)$row)) {
        return false;
    }
    $data = mysqli_fetch_array($result, MYSQLI_BOTH);
    return $data[$field] ?? false;
}

function db_error()
{
    global $sql_vars;
    return mysqli_error($sql_vars["db"]);
}

function db_errno()
{
    global $sql_vars;
    return mysqli_errno($sql_vars["db"]);
}

function db_server_info()
{
    global $sql_vars;
    return mysqli_get_server_info($sql_vars["db"]);
}

function db_insert_id()
{
    global $sql_vars;
    return mysqli_insert_id($sql_vars["db"]);
}

function restoarray($resdata)
{
    $data = array();
    while ($row = mysqli_fetch_row($resdata)) {
        $data[] = $row;
    }
    return $data;
}

sql_conn();
?>
