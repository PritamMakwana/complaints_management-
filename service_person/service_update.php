<?php
// service_update.php - Phase 4: Update for Service Person
include __DIR__ . '/../config/db.php';
session_start();
// if ($_SESSION['role'] !== 'service_person') {
//     header('Location: login.php');
//     exit;
// }
$id = $_GET['id'] ?? 0;
if (!$id) {
    header('Location: ../service_person/dashboard.php');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $closing_remark = $_POST['closing_remark'];
        $reason = $_POST['reason'];

        $update = $pdo->prepare("UPDATE complaints SET status = 'Closed', closed_at = NOW(), closing_remark = ? WHERE id = ?");
        $update->execute(["$reason: $closing_remark", $id]); // Combine reason and remark

        // Update service person availability if needed
        $_SESSION['success'] = "Complaint #$id closed successfully!";
    } catch (Exception $e) {
        $_SESSION['error'] = "Failed to close complaint: " . $e->getMessage();
    }

    header('Location: ../service_person/dashboard.php');
    exit;
}
?>

<?php include __DIR__ . '/../includes/header.php'; ?>
<h2>Close Complaint - ID: <?= $id ?></h2>
<form method="POST" class="card p-4">
    <div class="mb-3">
        <label for="reason">Reason for Closure</label>
        <select class="form-select" id="reason" name="reason" required>
            <option value="Service Fulfilled">Service Fulfilled</option>
            <option value="Warranty Expired">Warranty Expired</option>
            <option value="Customer Unreachable">Customer Unreachable</option>
            <option value="Note">Note</option>
        </select>
    </div>
    <div class="mb-3">
        <label for="closing_remark">Closing Remark</label>
        <textarea class="form-control" id="closing_remark" name="closing_remark" required></textarea>
    </div>
    <button type="submit" class="btn btn-success btn-ui w-25"><i class="material-icons me-2">check</i><b>Close</b></button>
</form>
<?php include __DIR__ . '/../includes/footer.php'; ?>