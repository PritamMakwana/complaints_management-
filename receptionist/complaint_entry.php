<?php
// complaint_entry.php - Phase 1: Complaint Entry (Receptionist) 
include '/config/db.php';

session_start();
if ($_SESSION['role'] !== 'receptionist') {
    header('Location: login.php'); // Assume login exists
    exit;
}

$models = ['Model A', 'Model B', 'Model C']; // Hardcoded product models

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mobile = $_POST['mobile_number'];
    $name = $_POST['name'] ?? null;
    $city = $_POST['city'] ?? null;
    $state = $_POST['state'] ?? null;
    $product_name = $_POST['product_name'];
    $description = $_POST['description'] ?? null;

    // Check if customer exists
    $stmt = $pdo->prepare("SELECT id FROM customers WHERE mobile_number = ?");
    $stmt->execute([$mobile]);
    $customer = $stmt->fetch();

    if ($customer) {
        $customer_id = $customer['id'];
        // Update customer if new details provided
        if ($name || $city || $state) {
            $update_stmt = $pdo->prepare("UPDATE customers SET name = COALESCE(?, name), city = COALESCE(?, city), state = COALESCE(?, state) WHERE id = ?");
            $update_stmt->execute([$name, $city, $state, $customer_id]);
        }
    } else {
        $insert_stmt = $pdo->prepare("INSERT INTO customers (name, mobile_number, city, state) VALUES (?, ?, ?, ?)");
        $insert_stmt->execute([$name, $mobile, $city, $state]);
        $customer_id = $pdo->lastInsertId();
    }

    // Insert complaint
    $insert_complaint = $pdo->prepare("INSERT INTO complaints (customer_id, product_name, description, receptionist_user_id) VALUES (?, ?, ?, ?)");
    $insert_complaint->execute([$customer_id, $product_name, $description, $_SESSION['user_id']]);

    header('Location: success.php'); // Or dashboard
    exit;
}
?>

<?php include '/includes/header.php'; ?>
<h2>Complaint Entry</h2>
<form method="POST" class="card p-4">
    <div class="mb-3">
        <label for="mobile_number" class="form-label">Mobile Number <span class="text-danger">*</span></label>
        <input type="text" class="form-control" id="mobile_number" name="mobile_number" required>
    </div>
    <div class="mb-3">
        <label for="name" class="form-label">Name</label>
        <input type="text" class="form-control" id="name" name="name">
    </div>
    <div class="mb-3">
        <label for="city" class="form-label">City</label>
        <input type="text" class="form-control" id="city" name="city">
    </div>
    <div class="mb-3">
        <label for="state" class="form-label">State</label>
        <input type="text" class="form-control" id="state" name="state">
    </div>
    <div class="mb-3">
        <label for="product_name" class="form-label">Product Model <span class="text-danger">*</span></label>
        <select class="form-select" id="product_name" name="product_name" required>
            <?php foreach ($models as $model): ?>
                <option value="<?= $model ?>"><?= $model ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="mb-3">
        <label for="description" class="form-label">Remark</label>
        <textarea class="form-control" id="description" name="description"></textarea>
    </div>
    <button type="submit" class="btn btn-primary"><i class="material-icons">save</i> Submit</button>
</form>

<script>
    // Autofill on mobile change
    document.getElementById('mobile_number').addEventListener('blur', function() {
        fetch(`autofill_customer.php?mobile=${this.value}`)
            .then(response => response.json())
            .then(data => {
                if (data) {
                    document.getElementById('name').value = data.name || '';
                    document.getElementById('city').value = data.city || '';
                    document.getElementById('state').value = data.state || '';
                }
            });
    });
</script>
<?php include '/includes/footer.php'; ?>