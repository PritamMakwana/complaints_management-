<?php
// coordinator_dashboard.php - Phase 2: Coordinator Dashboard
include  __DIR__ . '/../config/db.php';
session_start();
// if ($_SESSION['role'] !== 'coordinator') {
//     header('Location: login.php');
//     exit;
// }
$status = $_GET['status'] ?? 'New';

// $stmt = $pdo->prepare("SELECT c.id, cu.name as customer_name, c.product_name, c.status, c.created_at 
//                        FROM complaints c 
//                        LEFT JOIN customers cu ON c.customer_id = cu.id 
//                        WHERE c.status = 'New'");

$stmt = $pdo->prepare("SELECT c.id, cu.name as customer_name, c.product_name, c.status, c.created_at 
                       FROM complaints c 
                       LEFT JOIN customers cu ON c.customer_id = cu.id 
                       WHERE c.status = :status
                       ORDER BY c.created_at DESC");
$stmt->execute(['status' => $status]);
$complaints = $stmt->fetchAll();
?>

<?php include  __DIR__ . '/../includes/header.php'; ?>
<h2>Coordinator Dashboard</h2>

<!-- Status Filter -->
<form method="get" class="mb-3">
    <label for="status" class="form-label">Filter by Status:</label>
    <select name="status" id="status" class="form-select w-auto d-inline" onchange="this.form.submit()">
        <option value="New" <?= $status === 'New' ? 'selected' : '' ?>>New</option>
        <option value="Assigned to Coordinator" <?= $status === 'Assigned to Coordinator' ? 'selected' : '' ?>>Assigned to Coordinator</option>
        <option value="Assigned to Service Person" <?= $status === 'Assigned to Service Person' ? 'selected' : '' ?>>Assigned to Service Person</option>
        <option value="Closed" <?= $status === 'Closed' ? 'selected' : '' ?>>Closed</option>
    </select>
</form>

<table class="table table-datatable table-striped">
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
                <td><?= htmlspecialchars($complaint['id']) ?></td>
                <td><?= htmlspecialchars($complaint['customer_name']) ?></td>
                <td><?= htmlspecialchars($complaint['product_name']) ?></td>
                <td><?= htmlspecialchars($complaint['status']) ?></td>
                <td><?= htmlspecialchars($complaint['created_at']) ?></td>
                <td>
                    <a href="../coordinator/complaint_details.php?id=<?= $complaint['id'] ?>"
                        class="btn btn-sm btn-info text-light">
                        <i class="material-icons me-2">edit</i> Edit
                    </a>
                </td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?php include  __DIR__ . '/../includes/footer.php'; ?>