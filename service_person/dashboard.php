<?php
// service_person_dashboard.php - Phase 4: Service Person Dashboard
// Note: Assuming service persons login via users table, but linked via service_persons. For simplicity, assume $_SESSION['service_person_id'] set on login.
include __DIR__ . '/../config/db.php';
session_start();
// Assume service person has role 'service_person' and service_person_id in session
// if ($_SESSION['role'] !== 'service_person') {
//     header('Location: login.php');
//     exit;
// }

$_SESSION['service_person_id'] = 1; // For testing, assume service person ID 1 logged in

$service_person_id = $_SESSION['service_person_id']; // Set on login

$stmt = $pdo->prepare("SELECT c.id, cu.name as customer_name, c.product_name, c.status, cd.coordinator_remark 
                       FROM complaints c 
                       LEFT JOIN customers cu ON c.customer_id = cu.id 
                       LEFT JOIN complaint_details cd ON c.id = cd.complaint_id 
                       WHERE c.service_person_id = ? AND c.status != 'Closed'");
$stmt->execute([$service_person_id]);
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

<h2>Service Person Dashboard</h2>
<table class="table table-datatable table-striped">
    <thead>
        <tr>
            <th>ID</th>
            <th>Customer</th>
            <th>Product</th>
            <th>Status</th>
            <th>Remark</th>
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
                <td><?= $complaint['coordinator_remark'] ?></td>
                <td><a href="../service_person/service_update.php?id=<?= $complaint['id'] ?>" class="btn btn-sm btn-info btn-ui text-light w-50"><i class="material-icons me-1">edit</i><b>Update</b></a></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?php include __DIR__ . '/../includes/footer.php'; ?>