<?php
// spare_parts_detail.php - Phase 3: Detailed View for Spare Parts
include __DIR__ . '/../config/db.php';
session_start();
// if ($_SESSION['role'] !== 'spare_parts_coordinator') {
//     header('Location: login.php');
//     exit;
// }

$id = $_GET['id'] ?? 0;
if (!$id) {
    header('Location: ../spare_parts/dashboard.php');
    exit;
}

$spare_stmt = $pdo->prepare("SELECT * FROM spare_parts_list WHERE complaint_id = ?");
$spare_stmt->execute([$id]);
$spare_parts = $spare_stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($_POST['status'] as $part_id => $status) {
        $courier = $_POST['courier_details'][$part_id] ?? null;
        $update = $pdo->prepare("UPDATE spare_parts_list SET status = ?, courier_details = ? WHERE id = ?");
        $update->execute([$status, $courier, $part_id]);
    }
    header('Location: ../spare_parts/dashboard.php');
    exit;
}
?>

<?php include __DIR__ . '/../includes/header.php'; ?>
<h2>Update Spare Parts - Complaint ID: <?= $id ?></h2>
<form method="POST" class="card p-4">
    <?php foreach ($spare_parts as $part): ?>
        <div class="mb-3">
            <h5><?= $part['part_name'] ?> (Qty: <?= $part['quantity'] ?>)</h5>
            <label>Status</label>
            <select class="form-select" name="status[<?= $part['id'] ?>]">
                <option value="Pending" <?= $part['status'] === 'Pending' ? 'selected' : '' ?>>Pending</option>
                <option value="Shipped" <?= $part['status'] === 'Shipped' ? 'selected' : '' ?>>Shipped</option>
                <option value="Received" <?= $part['status'] === 'Received' ? 'selected' : '' ?>>Received</option>
            </select>
        </div>
        <div class="mb-3">
            <label>Courier Details</label>
            <textarea class="form-control" name="courier_details[<?= $part['id'] ?>]"><?= $part['courier_details'] ?></textarea>
        </div>
    <?php endforeach; ?>
    <button type="submit" class="btn btn-primary"><i class="material-icons">save</i> Update</button>
</form>
<?php include __DIR__ . '/../includes/footer.php'; ?>