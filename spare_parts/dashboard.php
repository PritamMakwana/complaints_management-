<?php
// spare_parts_dashboard.php - Phase 3: Spare Parts Management
include  __DIR__ . '/../config/db.php';
session_start();
$_SESSION['user_id'] = 1;
// if ($_SESSION['role'] !== 'spare_parts_coordinator') {
//     header('Location: login.php');
//     exit;
// }

$stmt = $pdo->prepare("SELECT c.id, cu.name as customer_name, c.product_name 
                       FROM complaints c 
                       LEFT JOIN customers cu ON c.customer_id = cu.id 
                       LEFT JOIN complaint_details cd ON c.id  = cd.complaint_id
                       WHERE c.spare_parts_coordinator_user_id = ? AND (cd.free_spare_parts_needed = 1 OR cd.paid_spare_parts_needed = 1)");

$stmt->execute([$_SESSION['user_id']]);
$complaints = $stmt->fetchAll();
?>

<?php include __DIR__ . '/../includes/header.php'; ?>

<?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?= $_SESSION['success']; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php unset($_SESSION['success']); ?>
<?php endif; ?>

<?php if (isset($_SESSION['error'])): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <?= $_SESSION['error']; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php unset($_SESSION['error']); ?>
<?php endif; ?>


<h2>Spare Parts Dashboard</h2>
<table class="table table-datatable table-striped">
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
                <td><a href="../spare_parts/spare_parts_detail.php?id=<?= $complaint['id'] ?>" class="btn btn-sm btn-info btn-ui w-25 text-light"><i class="material-icons me-2">edit</i><b>Update</b></a></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?php include __DIR__ . '/../includes/footer.php'; ?>