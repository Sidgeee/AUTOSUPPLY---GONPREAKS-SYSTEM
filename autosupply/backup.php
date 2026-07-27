<?php
session_start();
if($_SESSION['role'] !== 'Admin') { die("Access Denied"); }

include 'db_connect.php';

// File naming (e.g., autosupply_backup_2026-03-13.sql)
$filename = "autosupply_backup_" . date("Y-m-d") . ".sql";

// Set headers to force download
header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . $filename . '"');

// Get all tables
$tables = [];
$result = $conn->query("SHOW TABLES");
while ($row = $result->fetch_row()) { $tables[] = $row[0]; }

$sql_dump = "";

foreach ($tables as $table) {
    // 1. Create Table Structure
    $res = $conn->query("SHOW CREATE TABLE $table");
    $row = $res->fetch_row();
    $sql_dump .= "\n\n" . $row[1] . ";\n\n";

    // 2. Get Table Data
    $res = $conn->query("SELECT * FROM $table");
    while ($row = $res->fetch_assoc()) {
        $keys = array_keys($row);
        $values = array_map([$conn, 'real_escape_string'], array_values($row));
        $sql_dump .= "INSERT INTO $table (`" . implode("`, `", $keys) . "`) VALUES ('" . implode("', '", $values) . "');\n";
    }
}

echo $sql_dump;
exit();
?>