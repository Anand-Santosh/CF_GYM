<?php
require_once '../../includes/auth.php';
require_once '../../includes/header.php';

if ($_SESSION['role'] !== 'admin') {
    header("Location: ../../index.php");
    exit();
}

// Handle status update
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['update_status'])) {
    $order_id = $_POST['order_id'];
    $new_status = $_POST['status'];
    
    $stmt = $conn->prepare("UPDATE supplement_orders SET status = ? WHERE order_id = ?");
    $stmt->execute([$new_status, $order_id]);
    
    $_SESSION['success'] = "Order status updated successfully!";
    header("Location: orders.php");
    exit();
}

// Get all supplement orders
$orders = $conn->query("
    SELECT o.*, s.name as supplement_name, m.full_name as member_name
    FROM supplement_orders o
    JOIN supplements s ON o.supplement_id = s.supplement_id
    JOIN members m ON o.member_id = m.member_id
    ORDER BY o.pickup_date DESC
")->fetchAll();
?>

<h2>Supplement Orders</h2>

<?php if(isset($_SESSION['success'])): ?>
    <div class="alert alert-success"><?php echo $_SESSION['success']; unset($_SESSION['success']); ?></div>
<?php endif; ?>

<div class="table-responsive">
    <table class="table table-striped">
        <thead>
            <tr>
                <th>Order ID</th>
                <th>Member</th>
                <th>Supplement</th>
                <th>Quantity</th>
                <th>Pickup Date</th>
                <th>Status</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($orders as $order): ?>
            <tr>
                <td><?= $order['order_id'] ?></td>
                <td><?= $order['member_name'] ?></td>
                <td><?= $order['supplement_name'] ?></td>
                <td><?= $order['quantity'] ?></td>
                <td><?= date('M j, Y', strtotime($order['pickup_date'])) ?></td>
                <td>
                    <span class="badge bg-<?= 
                        $order['status'] == 'pending' ? 'warning' : 
                        ($order['status'] == 'ready' ? 'success' : 'secondary') 
                    ?>">
                        <?= ucfirst($order['status']) ?>
                    </span>
                </td>
                <td>
                    <form method="POST" class="d-inline">
                        <input type="hidden" name="order_id" value="<?= $order['order_id'] ?>">
                        <select name="status" class="form-select form-select-sm d-inline" style="width: auto;">
                            <option value="pending" <?= $order['status'] == 'pending' ? 'selected' : '' ?>>Pending</option>
                            <option value="ready" <?= $order['status'] == 'ready' ? 'selected' : '' ?>>Ready</option>
                            <option value="collected" <?= $order['status'] == 'collected' ? 'selected' : '' ?>>Collected</option>
                        </select>
                        <button type="submit" name="update_status" class="btn btn-sm btn-primary">Update</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>

<?php
require_once '../../includes/footer.php';
?>