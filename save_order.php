<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);
session_start();

header('Content-Type: application/json'); // Always return JSON

$host = "localhost";
$user = "root";
$pass = "";
$db = "cafe_db";

$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    echo json_encode(["success" => false, "error" => "DB Connection failed"]);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    try {
        // --- Get POST data ---
        $customer_name = trim($conn->real_escape_string($_POST['customer_name'] ?? ''));
        $table_number  = trim($conn->real_escape_string($_POST['table_number'] ?? ''));
        $order_type    = trim($conn->real_escape_string($_POST['order_type'] ?? 'Dine-In')); // ✅ Added

        // Helper: safely decode arrays
        function safeArray($key) {
            if (!isset($_POST[$key])) return [];
            if (is_array($_POST[$key])) return $_POST[$key];
            $decoded = json_decode($_POST[$key], true);
            return is_array($decoded) ? $decoded : [];
        }

        $items          = safeArray('items');
        $quantities     = safeArray('quantities');
        $customizations = safeArray('customizations');
        $payment_proof_data = $_POST['payment_proof'] ?? '';

        if (!$customer_name || !$table_number || empty($items)) {
            throw new Exception("Missing required fields.");
        }

        // --- Save payment proof ---
        $payment_proof_filename = "";
        if ($payment_proof_data) {
            if (!file_exists("uploads")) mkdir("uploads", 0777, true);

            $payment_proof_filename = "uploads/" . time() . "_proof.png";
            $image_parts = explode(",", $payment_proof_data);
            if (count($image_parts) != 2) throw new Exception("Invalid image data.");
            file_put_contents($payment_proof_filename, base64_decode($image_parts[1]));
        }

        // --- Calculate total ---
        $order_total = 0;
        foreach ($items as $i => $item_id) {
            $qty = intval($quantities[$i] ?? 1);

            $res = $conn->query("SELECT price, category_id FROM menu WHERE id=$item_id");
            if (!$res || $res->num_rows == 0) continue;
            $row = $res->fetch_assoc();
            $price = $row['price'];
            $category_id = $row['category_id'];

            // ✅ Bloom Platter (category_id = 5) → fixed ₹349 (ignore qty/customizations)
            if ($category_id == 5) {
                $order_total += 349;
                continue;
            }

            $extra = 0;
            if (isset($customizations[$item_id])) {
                foreach ($customizations[$item_id] as $c) {
                    $parts = explode("|", $c);
                    if (count($parts) === 3) {
                        list($custom_id, $custom_name, $extra_price) = $parts;
                        $extra += floatval($extra_price);
                    }
                }
            }

            $order_total += ($price + $extra) * $qty;
        }

        $gst = $order_total * 0.05;
        $grand_total = $order_total + $gst;

        // --- Insert order (with order_type) ---
        $stmt = $conn->prepare("INSERT INTO orders (customer_name, table_number, order_type, gst_percent, total, status, payment_proof) VALUES (?, ?, ?, ?, ?, 'Paid', ?)");
        $stmt->bind_param("sssdds", $customer_name, $table_number, $order_type, $gst, $grand_total, $payment_proof_filename);
        $stmt->execute();
        $order_id = $stmt->insert_id;
        $stmt->close();

        // --- Insert order items ---
        $stmt_item = $conn->prepare("INSERT INTO order_items (order_id, menu_id, quantity, price) VALUES (?, ?, ?, ?)");
        foreach ($items as $i => $item_id) {
            $qty = intval($quantities[$i] ?? 1);

            $res = $conn->query("SELECT price, category_id FROM menu WHERE id=$item_id");
            if (!$res || $res->num_rows == 0) continue;
            $row = $res->fetch_assoc();
            $price = $row['price'];
            $category_id = $row['category_id'];

            // ✅ Bloom Platter: save as fixed 349
            if ($category_id == 5) {
                $fixed = 349.00;
                $stmt_item->bind_param("iiid", $order_id, $item_id, $qty, $fixed);
                $stmt_item->execute();
                continue;
            }

            $stmt_item->bind_param("iiid", $order_id, $item_id, $qty, $price);
            $stmt_item->execute();

            $order_item_id = $conn->insert_id;

            // --- Save customizations ---
            if (isset($customizations[$item_id])) {
                $stmt_cust = $conn->prepare("INSERT INTO order_item_customizations (order_item_id, customization_id, customization_name, extra_price) VALUES (?, ?, ?, ?)");
                foreach ($customizations[$item_id] as $c) {
                    $parts = explode("|", $c);
                    if (count($parts) === 3) {
                        list($custom_id, $custom_name, $extra_price) = $parts;
                        $extra_price = floatval($extra_price);
                        $stmt_cust->bind_param("iisd", $order_item_id, $custom_id, $custom_name, $extra_price);
                        $stmt_cust->execute();
                    }
                }
                $stmt_cust->close();
            }
        }
        $stmt_item->close();

        echo json_encode(["success" => true, "order_id" => $order_id]);

    } catch (Exception $e) {
        echo json_encode(["success" => false, "error" => $e->getMessage()]);
    }
    exit;
}
?>
