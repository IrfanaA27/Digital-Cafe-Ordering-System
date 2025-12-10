<?php
$host = "localhost";
$user = "root";
$pass = "";
$db = "cafe_db";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    echo json_encode(["success" => false, "error" => "DB connection failed"]);
    exit;
}

$order_id = isset($_GET['order_id']) ? (int)$_GET['order_id'] : 0;
if ($order_id <= 0) {
    echo json_encode(["success" => false, "error" => "Invalid order ID"]);
    exit;
}

$result = $conn->query("SELECT status FROM orders WHERE id = $order_id");
if ($result && $row = $result->fetch_assoc()) {
    echo json_encode(["success" => true, "status" => $row['status']]);
} else {
    echo json_encode(["success" => false, "error" => "Order not found"]);
}
?>
