<?php
// spare_parts_dashboard.php - Phase 3: Spare Parts Management
include '/config/db.php';
session_start();
if ($_SESSION['role'] !== 'spare_parts_coordinator') {
    header('Location: login.php');
    exit;
}

$stmt = $pdo->prepare("SELECT c.id, cu.name as customer_name, c.product_name 
                       FROM complaints c 
                       LEFT JOIN customers cu ON c.customer_id = cu.id 
                       WHERE c.spare_parts_coordinator_user_id = ? AND (cd.free_spare_parts_needed = 1 OR cd.paid_spare_parts_needed = 1)");
$stmt->execute([$_SESSION['user_id']]);
$complaints = $stmt->fetchAll();
?>

<?php include '/includes/header.php'; ?>
<h2>Spare Parts Dashboard</h2>
<table class="table table-striped">
    <thead>
        <tr>
            <th>ID</th>
            <th>Customer</th>
            <th>Product</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($complaints as $complaint): ?>
            <tr>
                <td><?= $complaint['id'] ?></td>
                <td><?= $complaint['customer_name'] ?></td>
                <td><?= $complaint['product_name'] ?></td>
                <td><a href="/spare_parts/spare_parts_detail.php?id=<?= $complaint['id'] ?>" class="btn btn-sm btn-info"><i class="material-icons">edit</i> Update</a></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?php include '/includes/footer.php'; ?>