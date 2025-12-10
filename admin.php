<?php
session_start();

// DB connection
$host = "localhost";
$user = "root";
$pass = "";
$db = "cafe_db";
$conn = new mysqli($host, $user, $pass, $db);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// --- ADMIN LOGIN ---
if (isset($_POST['admin_login'])) {
    $username = $_POST['username'];
    $password = $_POST['password'];

    if ($username == "muglife" && $password == "1234") {
        $_SESSION['admin_logged_in'] = true;
    } else {
        $error = "Invalid credentials!";
    }
}

// --- LOGOUT ---
if (isset($_GET['logout'])) {
    session_destroy();
    header("Location: admin.php");
    exit;
}
// Delete reviews older than 30 days every time admin.php loads
$conn->query("DELETE FROM reviews WHERE created_at < NOW() - INTERVAL 30 DAY");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}


// If not logged in → show login form
if (!isset($_SESSION['admin_logged_in'])) {
    ?>
    <!DOCTYPE html>
    <html>
    <head>
        <title>Admin Login</title>
        <style>
            body { font-family: Arial, sans-serif; background: #f4f4f4; }
            .login-box { width: 400px; margin: 250px auto; background: white; padding: 20px; border-radius: 10px; box-shadow: 0 0 10px #a8a5a5ff; height: 250px; }
            input { width: 95%; padding: 10px; margin: 5px 0; }
            button { background: #a8520bff; color: white; border: none; padding: 15px; width: 100%;margin-top: 25px;}
        </style>
    </head>
    <body>
        <div class="login-box">
            <center><h2>Admin Login</h2></center>
            <?php if (isset($error)) echo "<p style='color:red;'>$error</p>"; ?>
            <form method="POST">
                <input type="text" name="username" placeholder="Username" required>
                <input type="password" name="password" placeholder="Password" required>
                <button type="submit" name="admin_login">Login</button>
            </form>
        </div>
    </body>
    </html>
    <?php
    exit;
}

// --- SHOW DASHBOARD ---
$page = isset($_GET['page']) ? $_GET['page'] : "orders";

?>
<!DOCTYPE html>
<html>
<head>
    <title>Admin Dashboard</title>
    <style>
        body { font-family: Arial, sans-serif; background: #f7f7f7ff; }
        .navbar { background: #333; padding: 10px; }
        .navbar a { color: white; margin: 0 10px; text-decoration: none; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #af2d2dff; padding: 8px; text-align: center; vertical-align: top; }
        th { background: #a8520bff; color: white; }
        button { padding: 5px 10px; }
        ul { list-style: none; padding: 0; margin: 0; }
        .stats-tabs {
    margin-top: 20px;
    margin-bottom: 10px;
}

.tab-btn {
    padding: 10px 20px;
    margin-right: 5px;
    border: none;
    background: #e7893cff;
    color: white;
    cursor: pointer;
    border-radius: 5px;
}

.tab-btn.active {
    background: #a8520bff;
}

    </style>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <div class="navbar">
        <a href="admin.php?page=orders">Orders</a>
        <a href="admin.php?page=menu">Manage Menu</a>
        <a href="admin.php?page=stats">Stats</a>
        <a href="admin.php?page=comments">Reviews</a>
        <a href="admin.php?logout=1">Logout</a>
    </div>

    <div class="content">
        <?php
// --- ORDERS PAGE ---
if ($page == "orders") {

    // Define GST rate
    define('GST_RATE', 0.05); // 5% GST

    echo "<h2>Customer Orders</h2>";

    // Fetch all orders, descending by ID
    $result = $conn->query("SELECT * FROM orders ORDER BY id DESC");

    echo "<table border='1' cellpadding='8' cellspacing='0' style='border-collapse:collapse; width:100%;'>
            <tr style='background:#f0f0f0;'>
                <th>ID</th>
                <th>Customer Name</th>
                <th>Table Number</th>
                <th>Order Type</th>
                <th>GST (%)</th>
                <th>Total (incl. GST)</th>
                <th>Status</th>
                <th>Payment Proof</th>
                <th>Items</th>
                <th>Action</th>
            </tr>";

    while ($row = $result->fetch_assoc()) {

        // Payment proof
        $proof_html = $row['payment_proof'] 
            ? "<a href='{$row['payment_proof']}' target='_blank'><img src='{$row['payment_proof']}' width='80'></a>" 
            : "Not uploaded";

        // Items + Customizations
        $items_html = "<ul>";
        $subtotal = 0; // before GST

        $items_result = $conn->query("
            SELECT 
                oi.id AS order_item_id, 
                oi.quantity, 
                COALESCE(oi.item_name, m.name) AS item_name,
                COALESCE(m.price, oi.price) AS item_price,
                m.category_id
            FROM order_items oi
            LEFT JOIN menu m ON oi.menu_id = m.id
            WHERE oi.order_id = {$row['id']} AND oi.quantity > 0
        ");

        while ($item = $items_result->fetch_assoc()) {

            // Bloom Platter fixed
            if($item['category_id'] == 5){ 
                $items_html .= "<li>The Bloom Platter × {$item['quantity']}</li>";
                $subtotal += 349 * $item['quantity'];
                continue; // skip customizations
            }

            // Normal items with customizations
            $cust_result = $conn->query("
                SELECT oic.customization_name, oic.extra_price
                FROM order_item_customizations oic
                WHERE oic.order_item_id = {$item['order_item_id']}
            ");

            $custs = [];
            $extra_total = 0;
            if ($cust_result && $cust_result->num_rows > 0) {
                while ($cust = $cust_result->fetch_assoc()) {
                    $custs[] = $cust['customization_name'];
                    $extra_total += $cust['extra_price'];
                }
            }

            $cust_text = !empty($custs) ? " (" . implode(", ", $custs) . ")" : "";

            // subtotal = (item price + customization extras) * quantity
            $subtotal += ($item['item_price'] + $extra_total) * $item['quantity'];

            $items_html .= "<li>{$item['item_name']} × {$item['quantity']}{$cust_text}</li>";
        }

        $items_html .= "</ul>";

        // GST Calculation
        $gst_amount = $subtotal * GST_RATE;
        $total_with_gst = $subtotal + $gst_amount;
        $total_display = "₹" . number_format($total_with_gst,2);

        // Display the order row
        echo "<tr>
                <td>{$row['id']}</td>
                <td>{$row['customer_name']}</td>
                <td>{$row['table_number']}</td>
                <td>{$row['order_type']}</td>
                <td>{$row['gst_percent']}</td>
                <td>{$total_display}</td>
                <td>{$row['status']}</td>
                <td>{$proof_html}</td>
                <td>{$items_html}</td>
                <td>
                    <form method='POST' action='' style='display:inline; margin-left:5px;'>
                        <input type='hidden' name='order_id' value='{$row['id']}'>
                        <button type='submit' name='set_ready'>Set Ready</button>
                    </form>
                </td>
              </tr>";
    }

    echo "</table>";
}


 // --- MENU PAGE ---
if ($page == "menu") {
    echo "<h2>Manage Menu</h2>";
    $result = $conn->query("
        SELECT m.id, m.name, m.price, c.name AS category_name
        FROM menu m
        LEFT JOIN categories c ON m.category_id = c.id
        ORDER BY c.name, m.name
    ") or die("Query Failed: " . $conn->error);

    echo "<table>
            <tr>
                <th>Item</th>
                <th>Price</th>
                <th>Customizations</th>
            </tr>";

    $last_category = ""; // track last category

    while ($row = $result->fetch_assoc()) {
        $menu_id = $row['id'];

        $cust_result = $conn->query("
            SELECT name, extra_price FROM menu_customizations 
            WHERE menu_id = $menu_id
        ");

        $custs = [];
        if ($cust_result && $cust_result->num_rows > 0) {
            while ($cust = $cust_result->fetch_assoc()) {
                $extra_price = is_numeric($cust['extra_price']) ? " (+₹{$cust['extra_price']})" : "";
                $custs[] = $cust['name'] . $extra_price;
            }
        }

        $cust_text = !empty($custs) ? implode(", ", $custs) : "None";

        // Show category as a centered row ONCE
        if ($last_category != $row['category_name']) {
            echo "<tr style='background:#f2f2f2; text-align:center; font-weight:bold;'>
                    <td colspan='3'>{$row['category_name']}</td>
                  </tr>";
            $last_category = $row['category_name'];
        }

        // ✅ If price > 0 → show normally
        if ($row['price'] > 0) {
            echo "<tr>
                    <td>{$row['name']}</td>
                    <td>₹{$row['price']}</td>
                    <td>{$cust_text}</td>
                  </tr>";
        } else {
            // ✅ If price = 0 → just show the item name, no price/customization
            echo "<tr>
                    <td>{$row['name']}</td>
                    <td></td>
                    <td></td>
                  </tr>";
        }
    }

    echo "</table>";
}



// --- Handle Deletions ---
if (isset($_POST['delete_review'])) {
    $review_id = (int)$_POST['review_id'];
    $conn->query("DELETE FROM reviews WHERE id = $review_id");
    header("Location: admin.php?page=comments");
    exit;
}

// --- COMMENTS PAGE ---
if ($page == "comments") {
    echo '
    <style>
        table { width:90%; margin:20px auto; border-collapse:collapse; text-align:center; }
        th, td { border:1px solid #e2b580ff; padding:10px; }
        th { background:#bb7b1bff; }
        button.delete-btn { padding:5px 10px; border:none; background:red; color:white; border-radius:5px; cursor:pointer; }
    </style>

    <h2 style="text-align:center; margin-top:20px;">⭐ Customer Reviews</h2>';

    $reviews = $conn->query("SELECT * FROM reviews ORDER BY created_at DESC");
    if ($reviews->num_rows > 0) {
        echo "<table>
                <tr><th>ID</th><th>Rating</th><th>Comment</th><th>Submitted At</th><th>Action</th></tr>";
        while ($r = $reviews->fetch_assoc()) {
            $stars = str_repeat('★', $r['rating']) . str_repeat('☆', 5 - $r['rating']);
            echo "<tr>
                    <td>{$r['id']}</td>
                    <td style='color:#ff9800; font-size:18px;'>$stars</td>
                    <td>{$r['comment']}</td>
                    <td>{$r['created_at']}</td>
                    <td>
                        <form method='POST' onsubmit=\"return confirm('Delete this review?');\">
                            <input type='hidden' name='review_id' value='{$r['id']}'>
                            <button type='submit' name='delete_review' class='delete-btn'>Delete</button>
                        </form>
                    </td>
                </tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='text-align:center;'>No reviews yet.</p>";
    }
}


        // --- STATS PAGE ---
        if ($page == "stats") {
            $stats = $conn->query("
                SELECT DATE(created_at) as order_date,
                       COUNT(*) as total_orders,
                       SUM(total) as revenue
                FROM orders
                GROUP BY DATE(created_at)
                ORDER BY order_date DESC
                LIMIT 7
            ");
$monthly_stats = $conn->query("
    SELECT DATE_FORMAT(created_at, '%Y-%m') AS month,
           COUNT(*) AS total_orders,
           SUM(total) AS revenue
    FROM orders
    GROUP BY DATE_FORMAT(created_at, '%Y-%m')
    ORDER BY month DESC
    LIMIT 12
");

$months = [];
$monthly_orders = [];
$monthly_revenue = [];

while ($row = $monthly_stats->fetch_assoc()) {
    $months[] = $row['month'];
    $monthly_orders[] = $row['total_orders'];
    $monthly_revenue[] = $row['revenue'];
}

            $dates = [];
            $orders = [];
            $revenue = [];

            while ($row = $stats->fetch_assoc()) {
                $dates[] = $row['order_date'];
                $orders[] = $row['total_orders'];
                $revenue[] = $row['revenue'];
            }
            ?>

           <div class="stats-tabs">
    <button class="tab-btn active" onclick="showChart('daily')">Daily</button>
    <button class="tab-btn" onclick="showChart('monthly')">Monthly</button>
</div>

<div id="dailyChartContainer">
    <h3>Daily Orders & Revenue</h3>
    <canvas id="ordersChart" width="600" height="300"></canvas>
</div>

<div id="monthlyChartContainer" style="display:none;">
    <h3>Monthly Orders & Revenue</h3>
    <canvas id="monthlyChart" width="600" height="300"></canvas>
</div>
            <script>
                const ctx = document.getElementById('ordersChart').getContext('2d');
                new Chart(ctx, {
                    type: 'bar',
                    data: {
                        labels: <?php echo json_encode(array_reverse($dates)); ?>,
                        datasets: [
                            {
                                label: 'Orders',
                                data: <?php echo json_encode(array_reverse($orders)); ?>,
                                backgroundColor: 'rgba(170, 105, 20, 0.6)',
                                borderColor: 'rgba(247, 180, 93, 0.81)',
                                borderWidth: 1,
                                yAxisID: 'y'
                            },
                            {
                                label: 'Revenue (₹)',
                                data: <?php echo json_encode(array_reverse($revenue)); ?>,
                                backgroundColor: 'rgba(255, 99, 132, 0.6)',
                                borderColor: 'rgba(255, 99, 132, 1)',
                                borderWidth: 1,
                                type: 'line',
                                yAxisID: 'y1'
                            }
                        ]
                    },
                    options: {
                        responsive: true,
                        scales: {
                            y: {
                                type: 'linear',
                                position: 'left',
                                beginAtZero: true,
                                title: { display: true, text: 'Orders' }
                            },
                            y1: {
                                type: 'linear',
                                position: 'right',
                                beginAtZero: true,
                                grid: { drawOnChartArea: false },
                                title: { display: true, text: 'Revenue (₹)' }
                            }
                        }
                    }
                });
const ctxMonthly = document.getElementById('monthlyChart').getContext('2d');
new Chart(ctxMonthly, {
    type: 'bar',
    data: {
        labels: <?php echo json_encode(array_reverse($months)); ?>,
        datasets: [
            {
                label: 'Orders',
                data: <?php echo json_encode(array_reverse($monthly_orders)); ?>,
                backgroundColor: 'rgba(170, 105, 20, 0.6)',
                borderColor: 'rgba(247, 180, 93, 0.81)',
                borderWidth: 1,
                yAxisID: 'y'
            },
            {
                label: 'Revenue (₹)',
                data: <?php echo json_encode(array_reverse($monthly_revenue)); ?>,
                backgroundColor: 'rgba(255, 99, 132, 0.6)',
                borderColor: 'rgba(255, 99, 132, 1)',
                borderWidth: 1,
                type: 'line',
                yAxisID: 'y1'
            }
        ]
    },
    options: {
        responsive: true,
        scales: {
            y: {
                type: 'linear',
                position: 'left',
                beginAtZero: true,
                title: { display: true, text: 'Orders' }
            },
            y1: {
                type: 'linear',
                position: 'right',
                beginAtZero: true,
                grid: { drawOnChartArea: false },
                title: { display: true, text: 'Revenue (₹)' }
            }
        }
    }
});
function showChart(type) {
    // Hide both containers
    document.getElementById('dailyChartContainer').style.display = 'none';
    document.getElementById('monthlyChartContainer').style.display = 'none';

    // Remove active class from all buttons
    document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));

    if(type === 'daily') {
        document.getElementById('dailyChartContainer').style.display = 'block';
        document.querySelector('.tab-btn[onclick="showChart(\'daily\')"]').classList.add('active');
    } else if(type === 'monthly') {
        document.getElementById('monthlyChartContainer').style.display = 'block';
        document.querySelector('.tab-btn[onclick="showChart(\'monthly\')"]').classList.add('active');
    }
}
         </script>
            <?php
        }
// --- UPDATE STATUS ---

if (isset($_POST['set_ready'])) {
    $order_id = (int)$_POST['order_id'];
    $conn->query("UPDATE orders SET status='Ready' WHERE id=$order_id");
    header("Location: admin.php?page=orders");
    exit;
}
        ?>
        
    </div>
</body>
</html>
