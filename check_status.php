<?php
$host = "localhost";
$user = "root";
$pass = "";
$db = "cafe_db";
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die(json_encode(["status" => "error", "message" => "DB failed"]));
}

$order_id = intval($_GET['order_id'] ?? 0);

if ($order_id > 0) {
    $result = $conn->query("SELECT status FROM orders WHERE id = $order_id");
    if ($result && $row = $result->fetch_assoc()) {
        echo json_encode(["status" => strtolower($row['status'])]); // lowercase consistency
    } else {
        echo json_encode(["status" => "not_found"]);
    }
} else {
    echo json_encode(["status" => "invalid"]);
}
?>
