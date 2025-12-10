<?php
// order.php

session_start();

// Database connection
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "cafe_db";

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

function getCustomizationsForItem($conn, $menu_id) {
    $customizations = [];
    $sql = "SELECT id, name, extra_price FROM menu_customizations WHERE menu_id = $menu_id";
    $result = $conn->query($sql);
    while ($row = $result->fetch_assoc()) {
        $customizations[] = $row;
    }
    return $customizations;
}

// Helper: redirect
function redirect($page) {
    header("Location: $page");
    exit();
}

// Step 1: Logo Page
if (!isset($_GET['step'])) {
    ?>
   <!DOCTYPE html>
<html>
<head>
    <title>Cafe Order System - Splash</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html, body {
            height: 100%;
            width: 100%;
            overflow: hidden;
            font-family: Arial, sans-serif;
            cursor: pointer;
        }

        .video-bg {
            position: fixed;
            top: 0;
            left: 0;
            height: 100%;
            width: 100%;
            object-fit: cover;
            z-index: -1;
        }

        .overlay {
            position: absolute;
            top: 0;
            left: 0;
            height: 100%;
            width: 100%;
        }

        /* Optional fade or tap text */
        .tap-text {
            position: absolute;
            bottom: 40px;
            width: 100%;
            text-align: center;
            font-size: 20px;
            color: #ffffffcc;
            animation: blink 1.5s infinite;
        }

        @keyframes blink {
            0%, 100% { opacity: 1; }
            50% { opacity: 0.3; }
        }
    </style>
</head>
<body onclick="goToNext()">
    <!-- Background animation video -->
    <video class="video-bg" autoplay muted loop>
        <source src="assets/anilogo.mp4" type="video/mp4">
        Your browser does not support the video tag.
    </video>

    <!-- Overlay to capture full-screen clicks -->
    <div class="overlay"></div>

    <!-- Optional "Tap Anywhere to Continue" message -->
    <div class="tap-text">Tap Anywhere to Continue</div>

    <script>
        function goToNext() {
            window.location.href = "?step=category";
        }
    </script>
</body>
</html>

    <?php
    $conn->close();
    exit();
}

// Step 2: Category Selection
if ($_GET['step'] == "category") {
    // Fetch categories
    $categories = [];
    $result = $conn->query("SELECT id, name FROM categories");
    if (!$result) {
        die("Query failed: " . $conn->error);
    }
    while ($row = $result->fetch_assoc()) {
        $categories[] = $row;
    }
    ?>
<?php
// order.php - category layout
$categories = [];
$result = $conn->query("SELECT id, name FROM categories");
if (!$result) {
    die("Query failed: " . $conn->error);
}
while ($row = $result->fetch_assoc()) {
    // Assign left/right side based on name
    $row['side'] = ($row['name'] === 'The Bloom Platter') ? 'right' : 'left';
    $categories[] = $row;
}

?><!DOCTYPE html>
<html>
<head>
    <title>Select Category - Cafe Order System</title>
    <style>
        html, body {
    height: 100%;
    margin: 0;
    padding: 0;
    overflow: hidden;/* disables scroll*/
    font-family: Arial, sans-serif;
    background-image: url('assets/logo.jpg'); /* Correct path */
    background-size: contain;
    background-position: top center; /* move image to top */
    background-repeat: no-repeat;
    position: relative;
}

body::before {
    content: "";
    position: absolute;
    top: 0;
    left: 0;
    height: 100%;
    width: 100%;
    background-color: rgba(255, 255, 255, 0.6); /* transparent white overlay */
    z-index: 0;
}

/* To keep content above the transparent overlay */
body > * {
    position: relative;
    z-index: 1;
}

        h2 {
            text-align: center;
            margin: 0;
            padding: 20px;
            font-size: 2em;
            color: #494444ff;
            position: absolute;
            top: 20px;
            left: 0;
            right: 0;
            z-index: 2;
        }
        .container {
            height: 100vh; /* Full screen height */
            display: flex;
            justify-content: space-between; 
            align-items: center; /* vertically center content */
            padding: 20px;
            box-sizing: border-box;
        }
        .category-wrapper{
            display: flex;
            gap: 100px;
        }
        .left-column, .right-column {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        .right-column{
            display: flex;
            flex-direction:column ;
            gap: 20px;
            margin-top: -300px; /* move upward, adjust vale as needed */
        }
        .category-box {
            background-color: rgba(255, 255, 255, 0.7);
            background-image: url('assets/common.png');
            background-size: cover;
            background-repeat: no-repeat;
            background-position: center;
            padding: 30px 50px;
            border-radius: 12px;
            text-align: left;
            font-family: 'Brush Script MT', cursive;  /* 💥 your custom font here */;
            font-size: 25px;
            font-weight: bold;
            width: 25vw;
            color: #5d4037;
            text-decoration: none;
            box-shadow: 0 4px 8px rgba(0,0,0,0.1);
            transition: transform 0.2s;
        }
        .category-box:hover {
            transform: scale(1.05);
            cursor: pointer;
        }
    </style>
</head>
<body>
    <h2 style="color: #5d4037;  font-family: 'Brush Script MT', cursive; font-size: 48px; "><b>Pick Delicious</b></h2>
    <div class="container">
        <div class="left-column">
            <?php foreach ($categories as $cat): ?>
    <?php if ($cat['side'] === 'left'): ?>
        <a href='order.php?step=items&category_id=<?= $cat['id'] ?>' class='category-box'>
            <?= htmlspecialchars($cat['name']) ?>
        </a>
    <?php endif; ?>
<?php endforeach; ?>

        </div>
      <div class="right-column" style="margin-left: 100px; text-align:center;">
    <?php foreach ($categories as $cat): ?>
        <?php if ($cat['side'] === 'right'): ?>
            <a href='order.php?step=items&category_id=<?= $cat['id'] ?>' class='category-box'>
                <?= htmlspecialchars($cat['name']) ?>
            </a>

            <!-- ✅ Only for The Bloom Platter -->
            <?php if (trim($cat['name']) === 'The Bloom Platter'): ?>
                <div 
                    onclick="openTerms()" 
                    style="margin-top: 10px; font-size: 13px; color: #6b4f4f; font-family: Arial, sans-serif; background-color: #f7ede2; padding: 6px 10px; border-radius: 10px; display: inline-block; cursor: pointer; text-decoration: underline;">
                    <b>Terms & Conditions</b>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    <?php endforeach; ?>
</div>

<!-- ✅ Popup Dialog Box -->
<div id="termsModal" style="
    display: none; 
    position: fixed; 
    top: 0; left: 0; 
    width: 100%; height: 100%;
    background-color: rgba(0,0,0,0.4);
    justify-content: center; align-items: center; z-index: 999;">
    <div style="
        background: #fff; 
        padding: 25px 30px; 
        border-radius: 15px; 
        width: 550px; 
        text-align: left;
        box-shadow: 0 4px 10px rgba(0,0,0,0.3);
        font-family: Arial, sans-serif;">
        <h3 style="color: #5d4037; text-align:center;">Terms & Conditions</h3>
      <ul style="font-size: 14px; color: #4e342e; line-height: 1.6;">
    <li>All food and beverages are freshly prepared upon receiving your order.</li>
    <li>Please confirm your order details before making the payment.</li>
    <li>Orders once placed cannot be modified or cancelled after confirmation.</li>
    <li>All prices are inclusive of applicable GST and service charges.</li>
    <li>We do not allow substitutions or custom changes for fixed platters.</li>
    <li>Estimated preparation time is 15–25 minutes depending on order volume.</li>
    <li>Online payments must be completed within 15 minutes of order placement.</li>
    <li>Unpaid orders will be automatically cancelled after the timeout period.</li>
    <li>Please ensure that your contact and table number details are entered correctly.</li>
    <li>Orders are served at the table number provided during checkout.</li>
    <li>We take hygiene and food safety seriously; all items are made in a sanitized kitchen.</li>
    <li>Outside food, beverages, or reusable containers are not allowed inside the café.</li>
    <li>Images shown on the menu are for illustration purposes only; actual presentation may vary.</li>
    <li>Our café reserves the right to modify menu items and prices without prior notice.</li>
    <li>Offers, discounts, and combos cannot be combined with other promotions.</li>
    <li>All disputes are subject to local jurisdiction.</li>
    <li>We appreciate your cooperation and patience while we prepare your order.</li>
    <li>By placing an order, you agree to abide by Mug Life Café’s policies and terms.</li>
</ul>

        <div style="text-align: center; margin-top: 15px;">
            <button onclick="closeTerms()" style="
                background-color: #6b4f4f; 
                color: white; 
                border: none; 
                border-radius: 8px; 
                padding: 8px 20px; 
                cursor: pointer;">Close</button>
        </div>
    </div>
</div>

<script>
function openTerms() {
    document.getElementById('termsModal').style.display = 'flex';
}
function closeTerms() {
    document.getElementById('termsModal').style.display = 'none';
}
</script>

</body>
</html>
<?php
    $conn->close();
    exit();
}

// Step 3: Items in Category & Place Order
if ($_GET['step'] == "items" && isset($_GET['category_id'])) {
    $category_id = intval($_GET['category_id']);

    // Fetch category name
    $cat_result = $conn->query("SELECT name FROM categories WHERE id=$category_id");
    if (!$cat_result){
        die("Query failed: " . $conn->error);
    }
    $cat_row = $cat_result ? $cat_result->fetch_assoc() : null;
    $category_name = $cat_row ? $cat_row['name'] : "Unknown";

    // Fetch items in category
   $grouping_categories = [2, 5]; // Only group for Hot Beverages & The Bloom Platter

$items_grouped = [];

if (in_array($category_id, $grouping_categories)) {
    // Group by group_name
    $result = $conn->query("SELECT * FROM menu WHERE category_id=$category_id ORDER BY group_name, name");
   while ($row = $result->fetch_assoc()) {
    $group = $row['group_name'] ?: ''; // This is the part to REPLACE
    $items_grouped[$group][] = $row;
}


    $use_grouping = true;
} else {
    // No grouping – all items in one group with empty key
    $result = $conn->query("SELECT * FROM menu WHERE category_id=$category_id ORDER BY name");
    while ($row = $result->fetch_assoc()) {
        $items_grouped[''][] = $row;
    }
    $use_grouping = false;
}

$order_success = false;
$error = "";

// --- HANDLE ORDER SUBMISSION ---
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['order'])) {
    $customer_name = trim($conn->real_escape_string($_POST['customer_name'] ?? ''));
    $table_number  = trim($conn->real_escape_string($_POST['table_number'] ?? ''));
    $order_type    = trim($conn->real_escape_string($_POST['order_type'] ?? 'Dine-In'));

    if (!$customer_name || !$table_number) {
        $error = "Please enter customer name and table number.";
    } else {
        $order_total = 0;
        $order_items = [];

        // --- Normal Items ---
        $item_ids   = $_POST['items'] ?? [];
        $quantities = $_POST['quantities'] ?? [];

        foreach ($item_ids as $index => $item_id) {
            $qty = intval($quantities[$index] ?? 0);
            if ($qty <= 0) continue;

            $res = $conn->query("SELECT name, price FROM menu WHERE id=" . intval($item_id));
            if (!$res || $res->num_rows == 0) continue;
            $row = $res->fetch_assoc();

            $item_name = $row['name'];
            $item_price = $row['price'];

            // --- Customizations ---
            $custom_total = 0;
            if (!empty($_POST['customizations'][$item_id])) {
                foreach ($_POST['customizations'][$item_id] as $c) {
                    list($cid, $cname, $cextra) = explode("|", $c);
                    $custom_total += floatval($cextra);
                }
            }

            $subtotal = ($item_price + $custom_total) * $qty;
            $order_total += $subtotal;

            $order_items[] = [
                'id' => $item_id,
                'name' => $item_name,
                'qty' => $qty,
                'price' => $item_price + $custom_total,
                'subtotal' => $subtotal,
                'customizations' => $_POST['customizations'][$item_id] ?? []
            ];
        }

        // --- Bloom Platter ---
        if (!empty($_POST['bloom_qty']) && intval($_POST['bloom_qty']) > 0) {
            $bloom_qty   = intval($_POST['bloom_qty']);
            $bloom_price = 349;
            $subtotal    = $bloom_price * $bloom_qty;
            $order_total += $subtotal;

            $order_items[] = [
                'id' => intval($_POST['bloom_platter_id']),
                'name' => "The Bloom Platter",
                'qty' => $bloom_qty,
                'price' => $bloom_price,
                'subtotal' => $subtotal,
                'customizations' => []
            ];
        }

        // --- Save Order ---
        if (!empty($order_items)) {
            $gst_percent = 5;
            $gst_amount  = ($order_total * $gst_percent) / 100;
            $grand_total = $order_total + $gst_amount;

            $stmt = $conn->prepare(
                "INSERT INTO orders (customer_name, table_number, order_type, gst_percent, total, status) 
                 VALUES (?, ?, ?, ?, ?, 'Pending')"
            );
            $stmt->bind_param("sssdd", $customer_name, $table_number, $order_type, $gst_percent, $grand_total);

            if ($stmt->execute()) {
                $order_id = $stmt->insert_id;
                $stmt->close();

                // --- Insert Order Items ---
                $stmt_item = $conn->prepare(
                    "INSERT INTO order_items (order_id, menu_id, item_name, quantity, price, subtotal) 
                     VALUES (?, ?, ?, ?, ?, ?)"
                );

                foreach ($order_items as $oi) {
                    $menu_id = $oi['id'] > 0 ? $oi['id'] : NULL;
                    $stmt_item->bind_param(
                        "iisidd",
                        $order_id,
                        $menu_id,
                        $oi['name'],
                        $oi['qty'],
                        $oi['price'],
                        $oi['subtotal']
                    );
                    $stmt_item->execute();
                    $order_item_id = $stmt_item->insert_id;

                    // --- Insert Customizations ---
                    foreach ($oi['customizations'] as $c) {
                        list($cid, $cname, $cextra) = explode("|", $c);
                        $cextra = floatval($cextra);
                        $cname  = $conn->real_escape_string($cname);
                        $conn->query(
                            "INSERT INTO order_item_customizations (order_item_id, customization_name, extra_price) 
                             VALUES ($order_item_id, '$cname', $cextra)"
                        );
                    }
                }
                $stmt_item->close();
                $order_success = true;
            } else {
                $error = "Failed to place order: " . $conn->error;
            }
        }
    }
}
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Order Items - Cafe Order System</title>
        <style>
            .flavor-options td {
    padding-left: 20px;
    font-size: 14px;
}
.flavor-options label {
    display: block;
    margin-bottom: 5px;
}
.flavor-row label {
    display: block;
    margin-bottom: 5px;
}


            body {margin: 0; padding: 0; background: #f7f7f7; font-family: Arial, sans-serif; 
                background-image: url('assets/logo.jpg');  /* 🔁 Use your actual image path */
                background-size: cover;                 /* stretch and cover whole screen */
                background-position: center;
                background-repeat: no-repeat;
                height: 100%;
                overflow-y: auto; /* ✅ Enable vertical scrolling */}
            body::before {
            content: "";
            position: fixed;
            top: 0;
            left: 0;
            height: 100%;
            width: 100%;
            background-color: rgba(255, 255, 255, 0.7); /* soft white overlay */
            z-index: -1;
}
.qty-wrapper {
    display: flex;
    align-items: center;
    gap: 6px;
}

.qty-wrapper input[type="number"] {
    width: 50px;
    text-align: center;
    font-size: 16px;
}

.qty-btn {
    width: 28px;
    height: 28px;
    font-size: 20px;
    font-weight: bold;
    border: none;
    background-color: #6d4c41;
    color: white;
    border-radius: 4px;
    cursor: pointer;
}

.qty-btn:hover {
    background-color: #4e342e;
}
.menu-container {
    display: flex;
    flex-direction: column;
    gap: 22px;
}

.menu-box {
     background-image: url('assets/common.png');
    background-size: cover;
    background-position: center;
    background-repeat: no-repeat;
    background-color: rgba(255, 255, 255, 0.85); /* white overlay*/
    padding: 16px 20px;
    border-radius: 12px;
    box-shadow: 0 2px 6px rgba(0,0,0,0.1);
    width: 100%;
    max-width: 600px;
    margin: 0 auto;
    margin-left: 20px;
}

.item-name {
    font-size: 20px;
    font-weight: bold;
    color: #4e342e;
    margin-bottom: 8px;
}

.item-price {
    font-size: 16px;
    color: #444;
    margin-bottom: 8px;
}

.qty-wrapper {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-left: 20px;
}

.qty-btn {
    width: 36px;
    height: 36px;
    font-size: 20px;
    background-color: #6d4c41;
    color: #fff;
    border: none;
    border-radius: 6px;
    cursor: pointer;
}

.qty-input {
    width: 50px;
    text-align: center;
    font-size: 16px;
    padding: 6px;
    border-radius: 6px;
    border: 1px solid #ccc;
}

.customization-box {
    font-size: 14px;
    padding-top: 5px;
}


            .container { width: 90vw; margin: 0; background: transparent; border-radius: 0; box-shadow: none; padding: 30px 60px; max-width: none; height: 100vh;}
            h2 { text-align: center; color: #4e342e; }
            table { width: 100%; border-collapse: collapse; margin-bottom: 18px; }
            th, td { padding: 10px; border-bottom: 1px solid #eee; text-align: left; }
            th { background: #d7ccc8; color: #4e342e; }
            input[type="number"] { width: 60px; padding: 5px; }
            .btn { background: #6d4c41; color: #fff; border: none; padding: 10px 22px; border-radius: 4px; cursor: pointer; font-size: 16px;  width: 220px;
    display: block;
    margin: 20px auto 0; }
            .btn:hover { background: #4e342e; }
            .success { background: #c8e6c9; color: #256029; padding: 12px; border-radius: 4px; margin-bottom: 18px; text-align: center; }
            .error { background: #ffcdd2; color: #b71c1c; padding: 12px; border-radius: 4px; margin-bottom: 18px; text-align: center; }
            /* Popup size, border radius, border color */
.my-swal-popup {
    width: 400px !important;         /* width */
    max-width: 90%;                  /* responsive */
    border-radius: 20px !important;  /* rounded corners */
    border: 4px solid #f0b36ec7;       /* border color and thickness */
    box-shadow: 0 5px 20px rgba(0,0,0,0.3);
}

/* Title color & font size */
.my-swal-title {
    color: #b85310ff;
    font-size: 28px;
}

/* Text content color */
.my-swal-content {
    color: #333;
    font-size: 16px;
}

              </style>
    </head>
    <body>
        <div class="container">
            <h2 style="color: #5d4037;  font-family: 'Brush Script MT', cursive; font-size: 40px;"><?php echo htmlspecialchars($category_name); ?> </h2>
            <?php if ($order_success): ?>
                <div class="success">Order placed successfully!</div>
            <?php elseif ($error): ?>
                <div class="error"><?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <form method="post">
                <label for="customer_name"><b>Customer Name:</b></label>
                <input type="text" name="customer_name" id="customer_name" required style="margin-bottom:12px; width: 100%; padding: 8px; border-radius: 4px; border: 1px solid #ccc;"><br>
                <label for="table_number"><b>Table Number:</b></label>
                <input type="text" name="table_number" id="table_number" required style="margin-bottom:12px; width: 100%; padding: 8px; border-radius: 4px; border: 1px solid #ccc;"><br>
              <div style="margin-bottom: 15px;">
    <label><b>Order Type : </b></label>

    <?php $selected_type = $_POST['order_type'] ?? 'Dine-In'; ?>

    <input type="radio" id="dinein" name="order_type" value="Dine-In"
        <?= $selected_type=='Dine-In' ? 'checked' : '' ?> required>
    <label for="dinein" style="margin-right:25px;">Dine-In</label>

    <input type="radio" id="takeaway" name="order_type" value="Takeaway"
        <?= $selected_type=='Takeaway' ? 'checked' : '' ?> required>
    <label for="takeaway">Takeaway</label>
</div>
<div class="menu-container">

<?php if ($category_id == 5): ?>
    <!-- Special layout for The Bloom Platter -->
    <div class="menu-box">
        <div style="display: flex; flex-direction: row; align-items: flex-start; justify-content: space-between;">
            
            <!-- ✅ Left: List of items -->
            <div style="width: 65%;">
                <?php foreach ($items_grouped as $group => $group_items): ?>
                    <?php if (!empty($group_items)): ?>
                        <div style="margin-top: 10px; font-weight: bold; color: #6d4c41;">
                            <?= htmlspecialchars($group) ?>
                        </div>
                        <ul style="margin: 5px 0 10px 20px; padding: 0;">
                            <?php foreach ($group_items as $item): ?>
                                <li><?= htmlspecialchars($item['name']) ?></li>
                            <?php endforeach; ?>
                        </ul>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div> <!-- ✅ CLOSE LEFT SIDE -->

            <!-- ✅ Right: image -->
            <div style="width: 150px; text-align: center;">
                <img src="assets/main.jpeg" alt="The Bloom Platter"
                     style="width: 160px; height: 200px; object-fit: cover; border-radius: 12px; margin-bottom: 10px;">
            </div>
<div style="margin-top: 10px; text-align: center;">
    <label><b>Quantity:</b></label>
    <div class="qty-wrapper" style="justify-content:center;">
        <button type="button" class="qty-btn" onclick="updateBloomQty(-1)">−</button>
        <!-- Bloom Platter Quantity -->
<input type="number" id="bloom_qty_display" value="1" min="1" max="4" class="qty-input" readonly>
<input type="hidden" id="bloom_qty" name="bloom_qty" value="1">
<input type="hidden" name="bloom_platter_id" value="26"> <!-- actual menu id of Bloom Platter -->
        <button type="button" class="qty-btn" onclick="updateBloomQty(1)">+</button>
    </div>
</div>

        </div> <!-- flex container -->
    </div> <!-- menu-box -->
<?php else: ?>


    <?php foreach ($items_grouped as $group => $group_items): ?>
        <?php if (!empty($group)): ?>
            <h3 class="group-heading"><?= htmlspecialchars($group) ?></h3>
        <?php endif; ?>

        <?php foreach ($group_items as $item): ?>
           <div class="menu-box">
              <div style="display: flex; justify-content: space-between; align-items: center;">
            <!-- Left side: Item details -->
            <div style="flex: 1;">
                <div class="item-name"><?= htmlspecialchars($item['name']) ?></div>

                <input type="hidden" name="items[]" value="<?= $item['id'] ?>" data-price="<?= $item['price'] ?>">
                <div class="item-price">₹<?= number_format($item['price'], 2) ?></div>

                <div class="qty-wrapper">
                    <button type="button" class="qty-btn minus">−</button>
                    <input type="number" name="quantities[]" min="0" max="4" value="0" class="qty-input">
                    <button type="button" class="qty-btn plus">+</button>
                </div>


       <?php
$customizations = getCustomizationsForItem($conn, $item['id']);
if (!empty($customizations)) {
    echo "<div class='customization-box' id='flavor-row-{$item['id']}' style='display: none;'>";
    echo "<strong>Customize:</strong><br>";
    foreach ($customizations as $flavor) {
        echo "<label>
            <input type='checkbox' 
                   name='customizations[{$item['id']}][]' 
                   value='{$flavor['id']}|{$flavor['name']}|{$flavor['extra_price']}' 
                   data-extra='{$flavor['extra_price']}'> 
            {$flavor['name']} (+₹{$flavor['extra_price']})
        </label><br>";
    }
    echo "</div>";
}
?>
            </div>
            <!-- Right side: Image -->
            <?php if (!empty($item['image'])): ?>
                <div style="margin-left: 15px;">
                    <img src="<?= htmlspecialchars($item['image']) ?>" alt="<?= htmlspecialchars($item['name']) ?>" style="width: 80px; height: 80px; object-fit: cover; border-radius: 8px;">
                </div>
            <?php endif; ?>
        </div>
    </div>
<?php endforeach; ?>
        <?php endforeach; ?>
<?php endif; ?>

</div>    
<!-- Totals Table -->
<table id="totals-table" style="margin-top: 20px; width: 100%; font-size: 16px;">
<?php if ($category_id != 5): ?>
    <tr>
        <td><b>Subtotal:</b></td>
        <td>₱<span id="subtotal">0.00</span></td>
    </tr>
    <tr>
        <td><b>GST (5%):</b></td>
        <td>₱<span id="gst">0.00</span></td>
    </tr>
    <tr>
        <td><b>Total:</b></td>
        <td>₱<span id="grand-total">0.00</span></td>
    </tr>
<?php else: ?>
    <tr>
        <td><b>Subtotal:</b></td>
        <td>₹<span id="bloom_subtotal">349.00</span></td>
    </tr>
    <tr>
        <td><b>GST (5%):</b></td>
        <td>₹<span id="bloom_gst">17.45</span></td>
    </tr>
    <tr>
        <td><b>Total:</b></td>
        <td>₹<span id="bloom_total">366.45</span></td>
    </tr>
<?php endif; ?>

</table>
    <button type="submit" name="order" class="btn" style="width: 220px; display: block; margin: 20px auto 0;box-shadow: 0 0 10px #a8a5a5ff;">Place Order</button>
    <!-- QR Code Display -->
    <div id="qr-section" style="display:none; text-align:center; margin-top: 20px;">
    <h3>Scan to Pay</h3>
    <p>Use any UPI app to scan and complete the payment</p>
    <div id="qr-container" style="margin-bottom: 15px;"></div>
 <p style="color: #b71c1c;"><b>Payment must be completed within 15 minutes.</b></p>
    <p id="countdown-timer" style="font-size: 14px; color: #777;"></p>
</div>
<div id="camera-section" style="display:none; text-align:center;">
  <video id="cam" autoplay playsinline width="300" height="200" style="border:1px solid black;"></video><br>
  <button id="capture" type="button">📸 Capture</button>
  <canvas id="snapshot" width="300" height="200" style="display:none; border:1px solid red;"></canvas>
</div>
</form>

<?php
// ================== HANDLE REVIEW SUBMISSION ==================
if (isset($_POST['submit_review'])) {
    $rating = intval($_POST['rating'] ?? 0);
    $comment = trim($_POST['comment'] ?? '');

    if ($rating > 0 && $comment !== '') {
        $insert = $conn->prepare("INSERT INTO reviews (rating, comment, created_at) VALUES (?, ?, NOW())");
        if ($insert) {
            $insert->bind_param("is", $rating, $comment);
            if ($insert->execute()) {
                $success = true; // flag to show SweetAlert later
            } else {
                $error_message = "Failed to submit review. Please try again.";
            }
            $insert->close();
        } else {
            $error_message = "Database error. Please try again.";
        }
    } else {
        $error_message = "Please provide both a star rating and a comment.";
    }
}
?>
<div style="margin-top:15px; padding:15px; background:#fff7f0; border-radius:10px; width:100%; box-shadow:0 2px 6px rgba(0,0,0,0.1);">
    <h3 style="color:#6d4c41; margin-bottom:10px;">Leave a Review</h3>

    <form method="POST" id="review-form">
        <!-- Star Rating -->
        <div id="star-rating" style="font-size:24px; color:#ccc; cursor:pointer; margin-bottom:10px;">
            ★★★★★
        </div>
        <input type="hidden" name="rating" id="rating-value" required>

        <!-- Comment -->
        <textarea name="comment" placeholder="Write your review here..." required
            style="width:95%; padding:10px; border:1px solid #ccc; border-radius:8px; resize:none; height:80px; margin-bottom:10px;"></textarea>

        <button type="submit" name="submit_review"
            style="background:#6d4c41; color:#fff; padding:8px 16px; border:none; border-radius:6px; cursor:pointer;">
            Submit Review
        </button>
    </form>
</div>

<!-- ================== CONTACT DETAILS ================== -->
<div style="margin-top:20px; font-size:14px; color:#5d4037;">
    <p>📩 Email: irfanaalatif@gmail.com</p>
    <p>📱 Instagram: muglife__offl</p>
    <p>📞 Contact: +91 7708085846</p>
</div>

<!-- ======= Scripts ======= -->
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
<script src="https://cdn.jsdelivr.net/npm/canvas-confetti@1.6.0/dist/confetti.browser.min.js"></script>

<script>
let selectedStars = 0;
const starsContainer = document.getElementById('star-rating');
const ratingInput = document.getElementById('rating-value');

// Render stars dynamically
starsContainer.innerHTML = '';
for (let i = 1; i <= 5; i++) {
    starsContainer.innerHTML += `<span data-star="${i}" style="cursor:pointer;">★</span>`;
}

// Click to select rating
starsContainer.addEventListener('click', e => {
    if (e.target.dataset.star) {
        selectedStars = parseInt(e.target.dataset.star);
        ratingInput.value = selectedStars;
        updateStars();
    }
});

function updateStars() {
    [...starsContainer.children].forEach((span, i) => {
        span.style.color = i < selectedStars ? '#ff9800' : '#ccc';
    });
}

// Optional: Show SweetAlert + confetti after successful submission
// Optional: Show SweetAlert + confetti after successful submission
<?php if(isset($success) && $success): ?>
Swal.fire({
    title: 'We love hearing from you 🫶🏻!',
    text: "Your review warms our hearts ☕❤️",
    showConfirmButton: false,
    timer: 5000, // 5 seconds alert
    willOpen: () => {
        // Confetti effect
        confetti({
            particleCount: 100,
            spread: 70,
            origin: { y: 0.6 }
        });
    }
}).then(() => {
    // Redirect after a total of 5 minutes (300000 ms)
    setTimeout(() => {
        window.location.href = "order.php";
    }, 300000); // 5 minutes
});
<?php endif; ?>


<?php if(isset($error_message)): ?>
Swal.fire({
    title: 'Oops!',
    text: '<?php echo $error_message; ?>',
    icon: 'error'
});
<?php endif; ?>

// Form validation before submit
document.getElementById('review-form').addEventListener('submit', function(e) {
    if (selectedStars === 0 || !this.comment.value.trim()) {
        e.preventDefault();
        Swal.fire('Please select a star rating and write a review!');
    }
});

// =================== QUANTITY & FLAVOR HANDLING ===================
document.addEventListener('DOMContentLoaded', () => {
    const qtyInputs = document.querySelectorAll('input[name="quantities[]"]');
    const itemInputs = document.querySelectorAll('input[name="items[]"]');

    qtyInputs.forEach((input, index) => {
        input.addEventListener('input', () => {
            const qty = parseInt(input.value) || 0;
            const itemId = itemInputs[index].value;
            const flavorRow = document.getElementById('flavor-row-' + itemId);
            if (flavorRow) flavorRow.style.display = qty > 0 ? 'block' : 'none';
            calculateTotal();
        });
    });

    // Plus/Minus buttons
    document.querySelectorAll('.qty-wrapper').forEach(wrapper => {
        const input = wrapper.querySelector('input');
        const plus = wrapper.querySelector('.plus, .qty-btn.plus');
        const minus = wrapper.querySelector('.minus, .qty-btn.minus');

        if (plus) plus.addEventListener('click', () => {
            let val = parseInt(input.value) || 0;
            if (val < 4) { input.value = val + 1; input.dispatchEvent(new Event('input')); }
        });

        if (minus) minus.addEventListener('click', () => {
            let val = parseInt(input.value) || 0;
            if (val > 0) { input.value = val - 1; input.dispatchEvent(new Event('input')); }
        });

        input.addEventListener('input', () => {
            let val = parseInt(input.value) || 0;
            val = Math.max(0, Math.min(4, val));
            input.value = val;
            input.dispatchEvent(new Event('input'));
        });
    });

    calculateTotal();
    document.querySelectorAll('input[type="checkbox"]').forEach(cb => {
        cb.addEventListener('change', calculateTotal);
    });
});

// =================== TOTAL CALCULATION ===================
function calculateTotal() {
    let subtotal = 0;
    const qtyInputs = document.querySelectorAll('input[name="quantities[]"]');
    const itemInputs = document.querySelectorAll('input[name="items[]"]');

    qtyInputs.forEach((input, i) => {
        const qty = parseInt(input.value) || 0;
        const itemId = itemInputs[i].value;
        const basePrice = parseFloat(itemInputs[i].dataset.price) || 0;
        let extra = 0;

        document.querySelectorAll(`input[name^="customizations[${itemId}][]"]:checked`).forEach(cb => {
            extra += parseFloat(cb.dataset.extra) || 0;
        });

        subtotal += (basePrice + extra) * qty;
    });

    // Bloom Platter
    const bloomQty = parseInt(document.getElementById('bloom_qty')?.value) || 0;
    subtotal += 349 * bloomQty;

    const gst = subtotal * 0.05;
    const total = subtotal + gst;

    const st = document.getElementById('subtotal');
    const g = document.getElementById('gst');
    const gt = document.getElementById('grand-total');
    if (st) st.textContent = subtotal.toFixed(2);
    if (g) g.textContent = gst.toFixed(2);
    if (gt) gt.textContent = total.toFixed(2);

    // Bloom Platter display
    if (document.getElementById('bloom_qty')) {
        const price = 349 * bloomQty;
        const gstBloom = price * 0.05;
        document.getElementById('bloom_subtotal').textContent = price.toFixed(2);
        document.getElementById('bloom_gst').textContent = gstBloom.toFixed(2);
        document.getElementById('bloom_total').textContent = (price + gstBloom).toFixed(2);
    }
}

// =================== BLOOM PLATTER QUANTITY ===================
function updateBloomQty(change) {
    const input = document.getElementById('bloom_qty');
    const display = document.getElementById('bloom_qty_display');
    let qty = parseInt(input.value) || 0;
    qty = Math.max(0, Math.min(4, qty + change));
    input.value = qty;
    display.value = qty;
    calculateTotal();
}

// =================== UPI PAYMENT ===================
const form = document.querySelector('form');
const qrSection = document.getElementById('qr-section');
const qrContainer = document.getElementById('qr-container');
const countdownTimer = document.getElementById('countdown-timer');
const upiId = "irfanaalatif@okaxis";

if (form) {
    form.addEventListener('submit', e => {
        e.preventDefault();

        const bloomQtyInput = document.getElementById('bloom_qty');
        const isBloom = bloomQtyInput && parseInt(bloomQtyInput.value) > 0;

        let total = isBloom ? document.getElementById('bloom_total').textContent : document.getElementById('grand-total').textContent;
        const label = isBloom ? "Bloom+Platter+Order" : "Table+Order";

        if (!total || parseFloat(total) <= 0) {
            alert("Please select at least 1 item to proceed to payment!");
            return;
        }

        const upiURL = `upi://pay?pa=${upiId}&pn=${label}&am=${total}&cu=INR`;
        qrContainer.innerHTML = `<img src="https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=${encodeURIComponent(upiURL)}" alt="UPI QR Code">`;

        const proceedBtn = document.createElement('button');
        proceedBtn.textContent = "I Have Paid → Next";
        proceedBtn.type = "button";
        proceedBtn.className = "btn btn-primary mt-3";
        proceedBtn.onclick = gotoStep2;
        qrSection.appendChild(proceedBtn);

        qrSection.style.display = "block";
        document.querySelector('button[name="order"]').style.display = "none";

        form.querySelectorAll('input, select, textarea, button').forEach(el => {
            if (el !== proceedBtn && el.id !== "capture") el.disabled = true;
        });

        let timeLeft = 15 * 60;
        const timerInterval = setInterval(() => {
            const minutes = Math.floor(timeLeft / 60);
            const seconds = timeLeft % 60;
            countdownTimer.textContent = `Auto-submitting in ${minutes}:${seconds.toString().padStart(2,'0')}`;
            timeLeft--;
            if (timeLeft < 0) clearInterval(timerInterval);
        }, 1000);

        setTimeout(() => {
            qrSection.innerHTML = `<div style="color:red; font-weight:bold; font-size:18px;">❌ Order cancelled </div>`;
            countdownTimer.textContent = "";
            setTimeout(() => location.reload(), 5000);
        }, 15 * 60 * 1000);
    });
}

// =================== CAMERA CAPTURE ===================
function gotoStep2() {
    qrSection.style.display = "none";
    const cameraSection = document.getElementById('camera-section');
    cameraSection.style.display = "block";
    const video = document.getElementById('cam');

    navigator.mediaDevices.getUserMedia({ video: true })
        .then(stream => { video.srcObject = stream; video.play(); })
        .catch(err => { alert("Camera access denied: " + err.message); console.error(err); });
}

// =================== CAPTURE & SEND ===================
const captureBtn = document.getElementById('capture');
if (captureBtn) {
    const canvas = document.getElementById('snapshot');
    const successSection = document.getElementById('success-section');
    const cameraSection = document.getElementById('camera-section');

    captureBtn.addEventListener('click', () => {
        const ctx = canvas.getContext('2d');
        const video = document.getElementById('cam');
        ctx.drawImage(video, 0, 0, canvas.width, canvas.height);
        canvas.style.display = "block";
        video.style.display = "none";

        const imageData = canvas.toDataURL("image/png");
        const formData = new FormData();

       // Customer info
const customerName = document.getElementById('customer_name').value.trim();
const tableNumber = document.getElementById('table_number').value.trim();
if (!customerName || !tableNumber) {
    alert("Please enter your name and table number!");
    return; 
}
formData.append("customer_name", customerName);
formData.append("table_number", tableNumber);

// ✅ Add order type
const orderType = document.querySelector('input[name="order_type"]:checked')?.value || 'Dine-In';
formData.append('order_type', orderType);

        // Collect items
        const items = [];
        const quantities = [];

        document.querySelectorAll('input[name="items[]"]').forEach((el, i) => {
            const qty = parseInt(document.querySelectorAll('input[name="quantities[]"]')[i].value) || 0;
            if (qty > 0) {
                items.push(el.value);
                quantities.push(qty);
            }
        });

        // Bloom Platter
        const bloomIdInput = document.querySelector('input[name="bloom_platter_id"]');
        const bloomQtyInput = document.getElementById('bloom_qty');
        if (bloomIdInput && bloomQtyInput) {
            const bloomQty = parseInt(bloomQtyInput.value) || 0;
            if (bloomQty > 0) {
                items.push(bloomIdInput.value);
                quantities.push(bloomQty);
            }
        }

        if (items.length === 0) {
            alert("Please select at least 1 item!");
            return;
        }

        items.forEach((id, i) => formData.append("items[]", id));
        quantities.forEach((q, i) => formData.append("quantities[]", q));

        // Customizations
        document.querySelectorAll('input[type="checkbox"]:checked').forEach(cb => {
            const match = cb.name.match(/customizations\[(\d+)\]/);
            if (match) formData.append(`customizations[${match[1]}][]`, cb.value);
        });

        // Payment proof
        formData.append("payment_proof", imageData);

        fetch("save_order.php", { method: "POST", body: formData })
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    cameraSection.style.display = 'none';

                   // =================== SUCCESS FLOW (SweetAlert + Confetti DURING popup) ===================
                    Swal.fire({
                        title: "Thanks🎊! We got your order",
                        text: "Your order has been processed",
                        confirmButtonText: "OK",
                        didOpen: () => {
                            // 🎉 Confetti plays WHILE popup is visible
                            const duration = 2000; // 2 seconds
                            const end = Date.now() + duration;

                            (function frame() {
                                confetti({
                                    particleCount: 5,
                                    angle: 60,
                                    spread: 80,
                                    origin: { x: 0 },
                                    colors: ['#ff0a54','#ff477e','#ff7096','#ff85a1','#fbb1b9']
                                });
                                confetti({
                                    particleCount: 5,
                                    angle: 120,
                                    spread: 80,
                                    origin: { x: 1 },
                                    colors: ['#ff0a54','#ff477e','#ff7096','#ff85a1','#fbb1b9']
                                });
                                if (Date.now() < end) {
                                    requestAnimationFrame(frame);
                                }
                            })();
                        }
                    }).then(() => {
                        Swal.fire({
                            title: "Your order is being prepared...",
                            html: `
                                <p class="mt-3">Processing🥐...</p>
                            `,
                            allowOutsideClick: false,
                            showConfirmButton: false,
                            didOpen: () => {
                                Swal.showLoading();
                                pollOrderStatus(data.order_id);
                            }
                        });
                    });
                } else {
                    alert("Order saving failed: " + data.error);
                }
            })
            .catch(err => alert("Error: " + err));
    });
}

// =================== POLL ORDER STATUS ===================
function pollOrderStatus(orderId) {
    let interval = setInterval(() => {
        fetch("check_status.php?order_id=" + orderId)
            .then(res => res.json())
            .then(data => {
                if (data.status === "ready") {
                    clearInterval(interval);
                    Swal.close();

                    // ALERT: Order ready + confetti
                    Swal.fire({
                        title: "Your order is ready!",
                        text: "Time to collect your order🍜",
                        confirmButtonText: "OK",
                        willOpen: () => {
                            // Confetti for 4 seconds
                            const duration = 4000;
                            const end = Date.now() + duration;

                            (function frame() {
                                confetti({
                                    particleCount: 5,
                                    angle: 60,
                                    spread: 80,
                                    origin: { x: 0 },
                                    colors: ['#ff0a54','#ff477e','#ff7096','#ff85a1','#fbb1b9']
                                });
                                confetti({
                                    particleCount: 5,
                                    angle: 120,
                                    spread: 80,
                                    origin: { x: 1 },
                                    colors: ['#ff0a54','#ff477e','#ff7096','#ff85a1','#fbb1b9']
                                });

                                if (Date.now() < end) {
                                    requestAnimationFrame(frame);
                                }
                            })();
                        }
                    }).then(() => {
                        // Second alert: Happy eating + confetti
                        Swal.fire({
                            title: "Happy eating🍔!",
                            text: "Where Every Meal Feels Like Home",
                            timer: 6000,  // auto-close after 6seconds
                            showConfirmButton: false,
                            willOpen: () => {
                                // Continuous confetti for 6 seconds
                                const duration = 6000;
                                const end = Date.now() + duration;

                                (function frame() {
                                    confetti({
                                        particleCount: 5,
                                        angle: 60,
                                        spread: 80,
                                        origin: { x: 0 },
                                        colors: ['#ff0a54','#ff477e','#ff7096','#ff85a1','#fbb1b9']
                                    });
                                    confetti({
                                        particleCount: 5,
                                        angle: 120,
                                        spread: 80,
                                        origin: { x: 1 },
                                        colors: ['#ff0a54','#ff477e','#ff7096','#ff85a1','#fbb1b9']
                                    });

                                    if (Date.now() < end) {
                                        requestAnimationFrame(frame);
                                    }
                                })();
                            }
                        }).then(() => {
                            // Redirect to front page
                            window.location.href = "order.php"; 
                        });
                    });
                }
            })
            .catch(err => console.error("Error polling order status:", err));
    }, 5000);
}


// =================== SPINNER ANIMATION ===================
const style = document.createElement('style');
style.innerHTML = `
@keyframes spin {
  0% { transform: rotate(0deg); }
  100% { transform: rotate(360deg); }
}`;
document.head.appendChild(style);
</script>


    </body>
    </html>
    <?php
    $conn->close();
    exit();
}
$conn->close();
?> 