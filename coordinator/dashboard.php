
<?php
// coordinator_dashboard.php - Phase 2: Coordinator Dashboard
include '/config/db.php';
session_start();
if ($_SESSION['role'] !== 'coordinator') {
    header('Location: login.php');
    exit;
}

$stmt = $pdo->prepare("SELECT c.id, cu.name as customer_name, c.product_name, c.status, c.created_at 
                       FROM complaints c 
                       LEFT JOIN customers cu ON c.customer_id = cu.id 
                       WHERE c.status = 'New'");
$stmt->execute();
$complaints = $stmt->fetchAll();
?>

<?php include '/includes/header.php'; ?>
<h2>Coordinator Dashboard</h2>
<table class="table table-striped">
    <thead>
        <tr>
            <th>ID</th>
            <th>Customer</th>
            <th>Product</th>
            <th>Status</th>
            <th>Created At</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($complaints as $complaint): ?>
            <tr>
                <td><?= $complaint['id'] ?></td>
                <td><?= $complaint['customer_name'] ?></td>
                <td><?= $complaint['product_name'] ?></td>
                <td><?= $complaint['status'] ?></td>
                <td><?= $complaint['created_at'] ?></td>
                <td><a href="/coordinator/complaint_details.php $complaint['id'] ?>" class="btn btn-sm btn-info"><i class="material-icons">edit</i> Edit</a></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?php include '/includes/footer.php'; ?>