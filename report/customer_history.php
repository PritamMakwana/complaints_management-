<?php
include __DIR__ . '/../config/db.php';
session_start();
// if (!in_array($_SESSION['role'], ['admin', 'coordinator'])) {
//     header('Location: login.php');
//     exit;
// }

// Handle customer search
$search_query = $_POST['search_query'] ?? $_GET['search_query'] ?? '';
$customer_id = $_GET['customer_id'] ?? '';
$complaint_id = $_GET['complaint_id'] ?? '';

// Fetch customers
$where = '';
$params = [];
if ($search_query) {
    $where = "WHERE name LIKE ? OR mobile_number LIKE ?";
    $params = ["%$search_query%", "%$search_query%"];
}
$customers_stmt = $pdo->prepare("
    SELECT id, name, mobile_number, city, state
    FROM customers
    $where
    ORDER BY name ASC
");
$customers_stmt->execute($params);
$customers = $customers_stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch complaints for selected customer
$history = [];
if ($customer_id) {
    $hist_stmt = $pdo->prepare("
        SELECT id, product_name, description, status, created_at, closed_at
        FROM complaints
        WHERE customer_id = ?
        ORDER BY created_at DESC
    ");
    $hist_stmt->execute([(int)$customer_id]);
    $history = $hist_stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Fetch complaint details if selected
$complaint_details = null;
if ($complaint_id && $customer_id) {
    // $detail_stmt = $pdo->prepare("
    //     SELECT c.*, cd.service_needed, cd.free_spare_parts_needed, cd.paid_spare_parts_needed, cd.num_of_coolers, cd.coordinator_remark,
    //            cu.name as customer_name, cu.mobile_number, cu.city, cu.state, cu.address,
    //            u1.name as receptionist_name, u2.name as coordinator_name, u3.name as spare_parts_coordinator_name, sp.name as service_person_name
    //     FROM complaints c
    //     LEFT JOIN complaint_details cd ON c.id = cd.complaint_id
    //     LEFT JOIN customers cu ON c.customer_id = cu.id
    //     LEFT JOIN users u1 ON c.receptionist_user_id = u1.id
    //     LEFT JOIN users u2 ON c.coordinator_user_id = u2.id
    //     LEFT JOIN users u3 ON c.spare_parts_coordinator_user_id = u3.id
    //     LEFT JOIN service_persons sp ON c.service_person_id = sp.id
    //     WHERE c.id = ? AND c.customer_id = ?
    // ");

    $detail_stmt = $pdo->prepare("
        SELECT c.*, cd.service_needed, cd.free_spare_parts_needed, cd.paid_spare_parts_needed, cd.num_of_coolers, cd.coordinator_remark,
               cu.name as customer_name, cu.mobile_number, cu.city, cu.state, cu.address,sp.name as service_person_name
        FROM complaints c
        LEFT JOIN complaint_details cd ON c.id = cd.complaint_id
        LEFT JOIN customers cu ON c.customer_id = cu.id
        LEFT JOIN service_persons sp ON c.service_person_id = sp.id
        WHERE c.id = ? AND c.customer_id = ?
    ");
    $detail_stmt->execute([(int)$complaint_id, (int)$customer_id]);
    $complaint_details = $detail_stmt->fetch(PDO::FETCH_ASSOC);

    // Fetch spare parts
    $spare_stmt = $pdo->prepare("SELECT * FROM spare_parts_list WHERE complaint_id = ?");
    $spare_stmt->execute([(int)$complaint_id]);
    $spare_parts = $spare_stmt->fetchAll(PDO::FETCH_ASSOC);
}
?>

<?php include __DIR__ . '/../includes/header.php'; ?>

<div class="container mt-4">
    <h2>Complaint History by Customer</h2>

    <!-- Customer Search Form -->
    <form method="POST" class="mb-4">
        <div class="input-group">
            <span class="input-group-text"><i class="material-icons">search</i></span>
            <input type="text" class="form-control" name="search_query" placeholder="Search by Name or Mobile Number" value="<?= htmlspecialchars($search_query) ?>">
            <button type="submit" class="btn btn-info btn-ui text-light"><i class="material-icons">search</i> Search</button>
            <a href="customer_history.php" class="btn btn-light btn-ui text-info"><i class="material-icons">refresh</i> Reset</a>
        </div>
    </form>

    <!-- Customer List -->
    <h3>Customers</h3>
    <?php if ($customers): ?>
        <table id="customerTable" class="table table-datatable table-striped table-hover">
            <thead>
                <tr>
                    <th>Id</th>
                    <th>Name</th>
                    <th>Mobile Number</th>
                    <th>City</th>
                    <th>State</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($customers as $customer): ?>
                    <tr>
                        <td><?= htmlspecialchars($customer['id'] ?? 'N/A') ?></td>
                        <td><?= htmlspecialchars($customer['name'] ?? 'N/A') ?></td>
                        <td><?= htmlspecialchars($customer['mobile_number']) ?></td>
                        <td><?= htmlspecialchars($customer['city'] ?? 'N/A') ?></td>
                        <td><?= htmlspecialchars($customer['state'] ?? 'N/A') ?></td>
                        <td>
                            <a href="?customer_id=<?= $customer['id'] ?>" class="btn btn-sm btn-info view-complaints" title="View Complaints">
                                <i class="material-icons text-light">history</i>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p class="alert alert-info">No customers found.</p>
    <?php endif; ?>

    <!-- Complaint History -->
    <?php if ($customer_id && $history): ?>
        <h3 class="mt-5">Complaint History for Customer ID: <?= $customer_id ?></h3>
        <table id="complaintTable" class="table table-datatable table-striped table-hover">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Product</th>
                    <th>Description</th>
                    <th>Status</th>
                    <th>Created At</th>
                    <th>Closed At</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($history as $complaint): ?>
                    <tr>
                        <td><?= $complaint['id'] ?></td>
                        <td><?= htmlspecialchars($complaint['product_name'] ?? 'N/A') ?></td>
                        <td><?= htmlspecialchars($complaint['description'] ?? 'N/A') ?></td>
                        <td>
                            <span class="badge bg-<?= $complaint['status'] === 'Closed' ? 'success' : ($complaint['status'] === 'New' ? 'warning' : 'info') ?>">
                                <?= $complaint['status'] ?>
                            </span>
                        </td>
                        <td><?= $complaint['created_at'] ?></td>
                        <td><?= $complaint['closed_at'] ?? 'N/A' ?></td>
                        <td>
                            <a href="?customer_id=<?= $customer_id ?>&complaint_id=<?= $complaint['id'] ?>" class="btn btn-sm btn-info view-details" title="View Details">
                                <i class="material-icons text-light">visibility</i>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php elseif ($customer_id): ?>
        <p class="alert alert-info">No complaints found for this customer.</p>
    <?php endif; ?>

    <!-- Complaint Details -->
    <?php if ($complaint_details): ?>
        <h3 class="mt-5">Complaint Details - ID: <?= $complaint_id ?></h3>
        <div class="card">
            <div class="card-header">
                <h5 class="card-title mb-0">Complaint Information</h5>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-6">
                        <p><strong>Customer Name:</strong> <?= htmlspecialchars($complaint_details['customer_name'] ?? 'N/A') ?></p>
                        <p><strong>Mobile:</strong> <?= htmlspecialchars($complaint_details['mobile_number'] ?? 'N/A') ?></p>
                        <p><strong>City:</strong> <?= htmlspecialchars($complaint_details['city'] ?? 'N/A') ?></p>
                        <p><strong>State:</strong> <?= htmlspecialchars($complaint_details['state'] ?? 'N/A') ?></p>
                        <p><strong>Address:</strong> <?= htmlspecialchars($complaint_details['address'] ?? 'N/A') ?></p>
                    </div>
                    <div class="col-md-6">
                        <p><strong>Product:</strong> <?= htmlspecialchars($complaint_details['product_name'] ?? 'N/A') ?></p>
                        <p><strong>Description:</strong> <?= htmlspecialchars($complaint_details['description'] ?? 'N/A') ?></p>
                        <p><strong>Status:</strong>
                            <span class="badge bg-<?= $complaint_details['status'] === 'Closed' ? 'success' : ($complaint_details['status'] === 'New' ? 'warning' : 'info') ?>">
                                <?= $complaint_details['status'] ?>
                            </span>
                        </p>
                        <p><strong>Created At:</strong> <?= $complaint_details['created_at'] ?></p>
                        <p><strong>Closed At:</strong> <?= $complaint_details['closed_at'] ?? 'N/A' ?></p>
                        <p><strong>Closing Remark:</strong> <?= htmlspecialchars($complaint_details['closing_remark'] ?? 'N/A') ?></p>
                    </div>
                </div>
                <h5 class="mt-3">Assigned Personnel</h5>
                <p><strong>Receptionist:</strong> <?= htmlspecialchars($complaint_details['receptionist_name'] ?? 'N/A') ?></p>
                <p><strong>Coordinator:</strong> <?= htmlspecialchars($complaint_details['coordinator_name'] ?? 'N/A') ?></p>
                <p><strong>Spare Parts Coordinator:</strong> <?= htmlspecialchars($complaint_details['spare_parts_coordinator_name'] ?? 'N/A') ?></p>
                <p><strong>Service Person:</strong> <?= htmlspecialchars($complaint_details['service_person_name'] ?? 'N/A') ?></p>
                <h5 class="mt-3">Complaint Details</h5>
                <p><strong>Service Needed:</strong> <?= $complaint_details['service_needed'] ? 'Yes' : 'No' ?></p>
                <p><strong>Free Spare Parts Needed:</strong> <?= $complaint_details['free_spare_parts_needed'] ? 'Yes' : 'No' ?></p>
                <p><strong>Paid Spare Parts Needed:</strong> <?= $complaint_details['paid_spare_parts_needed'] ? 'Yes' : 'No' ?></p>
                <p><strong>Number of Coolers:</strong> <?= $complaint_details['num_of_coolers'] ?? 'N/A' ?></p>
                <p><strong>Coordinator Remark:</strong> <?= htmlspecialchars($complaint_details['coordinator_remark'] ?? 'N/A') ?></p>
                <?php if ($spare_parts): ?>
                    <h5 class="mt-3">Spare Parts</h5>
                    <table class="table table-datatable table-bordered">
                        <thead>
                            <tr>
                                <th>Part Name</th>
                                <th>Quantity</th>
                                <th>Status</th>
                                <th>Courier Details</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($spare_parts as $part): ?>
                                <tr>
                                    <td><?= htmlspecialchars($part['part_name']) ?></td>
                                    <td><?= $part['quantity'] ?></td>
                                    <td>
                                        <span class="badge bg-<?= $part['status'] === 'Received' ? 'success' : ($part['status'] === 'Pending' ? 'warning' : 'info') ?>">
                                            <?= $part['status'] ?>
                                        </span>
                                    </td>
                                    <td><?= htmlspecialchars($part['courier_details'] ?? 'N/A') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>