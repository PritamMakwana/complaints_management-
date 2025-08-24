<?php
// complaint_detail.php - Phase 2: Detailed View for Coordinator

include '/config/db.php';
session_start();
if ($_SESSION['role'] !== 'coordinator') {
    header('Location: login.php');
    exit;
}

$id = $_GET['id'] ?? 0;
if (!$id) {
    header('Location: /coordinator/coordinator_dashboard.php');
    exit;
}

// Fetch complaint details
$complaint_stmt = $pdo->prepare("SELECT c.*, cu.name as customer_name, cu.mobile_number, cu.city, cu.state, cu.address, 
                                 cd.service_needed, cd.free_spare_parts_needed, cd.paid_spare_parts_needed, cd.num_of_coolers, cd.coordinator_remark 
                                 FROM complaints c 
                                 LEFT JOIN customers cu ON c.customer_id = cu.id 
                                 LEFT JOIN complaint_details cd ON c.id = cd.complaint_id 
                                 WHERE c.id = ?");
$complaint_stmt->execute([$id]);
$complaint = $complaint_stmt->fetch();

if (!$complaint) {
    header('Location: /coordinator/coordinator_dashboard.php');
    exit;
}

// Fetch service persons
$sp_stmt = $pdo->query("SELECT id, name FROM service_persons WHERE is_available = TRUE");
$service_persons = $sp_stmt->fetchAll();

// Fetch spare parts coordinators (users with role)
$spc_stmt = $pdo->query("SELECT id, name FROM users WHERE role = 'spare_parts_coordinator'");
$sp_coords = $spc_stmt->fetchAll();

// Fetch existing spare parts
$spare_stmt = $pdo->prepare("SELECT * FROM spare_parts_list WHERE complaint_id = ?");
$spare_stmt->execute([$id]);
$spare_parts = $spare_stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Update customer if needed
    $name = $_POST['name'] ?? $complaint['customer_name'];
    $city = $_POST['city'] ?? $complaint['city'];
    $state = $_POST['state'] ?? $complaint['state'];
    $address = $_POST['address'] ?? $complaint['address'];
    $update_cust = $pdo->prepare("UPDATE customers SET name = ?, city = ?, state = ?, address = ? WHERE id = ?");
    $update_cust->execute([$name, $city, $state, $address, $complaint['customer_id']]);

    // Update complaint details
    $service_needed = isset($_POST['service_needed']) ? 1 : 0;
    $free_sp = isset($_POST['free_spare_parts_needed']) ? 1 : 0;
    $paid_sp = isset($_POST['paid_spare_parts_needed']) ? 1 : 0;
    $num_coolers = $_POST['num_of_coolers'] ?? 1;
    $remark = $_POST['coordinator_remark'] ?? null;

    if ($complaint['complaint_id']) { // complaint_details id is null if not exists
        $update_cd = $pdo->prepare("UPDATE complaint_details SET service_needed = ?, free_spare_parts_needed = ?, paid_spare_parts_needed = ?, num_of_coolers = ?, coordinator_remark = ? WHERE complaint_id = ?");
        $update_cd->execute([$service_needed, $free_sp, $paid_sp, $num_coolers, $remark, $id]);
    } else {
        $insert_cd = $pdo->prepare("INSERT INTO complaint_details (complaint_id, service_needed, free_spare_parts_needed, paid_spare_parts_needed, num_of_coolers, coordinator_remark) VALUES (?, ?, ?, ?, ?, ?)");
        $insert_cd->execute([$id, $service_needed, $free_sp, $paid_sp, $num_coolers, $remark]);
    }

    // Assignments
    $service_person_id = $_POST['service_person_id'] ?? null;
    $sp_coord_id = $_POST['spare_parts_coordinator_user_id'] ?? null;
    $status = 'Assigned to Coordinator'; // Default
    if ($service_person_id) $status = 'Assigned to Service Person';
    $update_comp = $pdo->prepare("UPDATE complaints SET coordinator_user_id = ?, service_person_id = ?, spare_parts_coordinator_user_id = ?, status = ? WHERE id = ?");
    $update_comp->execute([$_SESSION['user_id'], $service_person_id, $sp_coord_id, $status, $id]);

    // Spare parts
    $delete_sp = $pdo->prepare("DELETE FROM spare_parts_list WHERE complaint_id = ?");
    $delete_sp->execute([$id]);
    if (isset($_POST['part_name'])) {
        $insert_sp = $pdo->prepare("INSERT INTO spare_parts_list (complaint_id, part_name, quantity) VALUES (?, ?, ?)");
        foreach ($_POST['part_name'] as $key => $part) {
            if ($part) {
                $qty = $_POST['quantity'][$key] ?? 1;
                $insert_sp->execute([$id, $part, $qty]);
            }
        }
    }

    header('Location: /coordinator/coordinator_dashboard.php');
    exit;
}
?>

<?php include '/includes/header.php'; ?>
<h2>Complaint Detail - ID: <?= $id ?></h2>
<form method="POST" class="card p-4">
    <!-- Display receptionist details -->
    <h4>Customer Details</h4>
    <div class="mb-3">
        <label>Mobile Number</label>
        <input type="text" class="form-control" value="<?= $complaint['mobile_number'] ?>" readonly>
    </div>
    <div class="mb-3">
        <label>Name</label>
        <input type="text" class="form-control" name="name" value="<?= $complaint['customer_name'] ?>">
    </div>
    <div class="mb-3">
        <label>City</label>
        <input type="text" class="form-control" name="city" value="<?= $complaint['city'] ?>">
    </div>
    <div class="mb-3">
        <label>State</label>
        <input type="text" class="form-control" name="state" value="<?= $complaint['state'] ?>">
    </div>
    <div class="mb-3">
        <label>Address</label>
        <textarea class="form-control" name="address"><?= $complaint['address'] ?></textarea>
    </div>
    <div class="mb-3">
        <label>Product</label>
        <input type="text" class="form-control" value="<?= $complaint['product_name'] ?>" readonly>
    </div>
    <div class="mb-3">
        <label>Description</label>
        <textarea class="form-control" readonly><?= $complaint['description'] ?></textarea>
    </div>

    <!-- Coordinator fields -->
    <h4>Coordinator Updates</h4>
    <div class="form-check mb-3">
        <input type="checkbox" class="form-check-input" id="service_needed" name="service_needed" <?= $complaint['service_needed'] ? 'checked' : '' ?>>
        <label class="form-check-label" for="service_needed">Service Needed</label>
    </div>
    <div class="form-check mb-3">
        <input type="checkbox" class="form-check-input" id="free_spare_parts_needed" name="free_spare_parts_needed" <?= $complaint['free_spare_parts_needed'] ? 'checked' : '' ?>>
        <label class="form-check-label" for="free_spare_parts_needed">Free Spare Parts Needed</label>
    </div>
    <div class="form-check mb-3">
        <input type="checkbox" class="form-check-input" id="paid_spare_parts_needed" name="paid_spare_parts_needed" <?= $complaint['paid_spare_parts_needed'] ? 'checked' : '' ?>>
        <label class="form-check-label" for="paid_spare_parts_needed">Paid Spare Parts Needed</label>
    </div>
    <div class="mb-3">
        <label for="num_of_coolers">Number of Coolers</label>
        <input type="number" class="form-control" id="num_of_coolers" name="num_of_coolers" value="<?= $complaint['num_of_coolers'] ?? 1 ?>" min="1">
    </div>
    <div class="mb-3">
        <label for="coordinator_remark">Remark</label>
        <textarea class="form-control" id="coordinator_remark" name="coordinator_remark"><?= $complaint['coordinator_remark'] ?></textarea>
    </div>

    <!-- Assignment -->
    <h4>Assignments</h4>
    <div class="mb-3">
        <label for="service_person_id">Assign to Service Person</label>
        <select class="form-select" id="service_person_id" name="service_person_id">
            <option value="">Select</option>
            <?php foreach ($service_persons as $sp): ?>
                <option value="<?= $sp['id'] ?>" <?= $complaint['service_person_id'] == $sp['id'] ? 'selected' : '' ?>><?= $sp['name'] ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="mb-3">
        <label for="spare_parts_coordinator_user_id">Assign to Spare Parts Coordinator</label>
        <select class="form-select" id="spare_parts_coordinator_user_id" name="spare_parts_coordinator_user_id">
            <option value="">Select</option>
            <?php foreach ($sp_coords as $spc): ?>
                <option value="<?= $spc['id'] ?>" <?= $complaint['spare_parts_coordinator_user_id'] == $spc['id'] ? 'selected' : '' ?>><?= $spc['name'] ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <!-- Spare Parts Dynamic Form -->
    <h4>Spare Parts List</h4>
    <div id="spare-parts-container">
        <?php foreach ($spare_parts as $index => $part): ?>
            <div class="row mb-2">
                <div class="col-md-6">
                    <input type="text" class="form-control" name="part_name[]" value="<?= $part['part_name'] ?>" placeholder="Part Name">
                </div>
                <div class="col-md-4">
                    <input type="number" class="form-control" name="quantity[]" value="<?= $part['quantity'] ?>" placeholder="Quantity" min="1">
                </div>
                <div class="col-md-2">
                    <button type="button" class="btn btn-danger remove-part"><i class="material-icons">delete</i></button>
                </div>
            </div>
        <?php endforeach; ?>
    </div>
    <button type="button" id="add-part" class="btn btn-secondary mb-3"><i class="material-icons">add</i> Add Part</button>

    <button type="submit" class="btn btn-primary"><i class="material-icons">save</i> Save</button>
</form>

<script>
    document.getElementById('add-part').addEventListener('click', function() {
        const container = document.getElementById('spare-parts-container');
        const row = document.createElement('div');
        row.classList.add('row', 'mb-2');
        row.innerHTML = `
            <div class="col-md-6">
                <input type="text" class="form-control" name="part_name[]" placeholder="Part Name">
            </div>
            <div class="col-md-4">
                <input type="number" class="form-control" name="quantity[]" placeholder="Quantity" min="1">
            </div>
            <div class="col-md-2">
                <button type="button" class="btn btn-danger remove-part"><i class="material-icons">delete</i></button>
            </div>
        `;
        container.appendChild(row);
    });

    document.addEventListener('click', function(e) {
        if (e.target.classList.contains('remove-part')) {
            e.target.closest('.row').remove();
        }
    });
</script>
<?php include '/includes/footer.php'; ?>