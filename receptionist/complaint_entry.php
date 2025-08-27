<?php
// complaint_entry.php - Phase 1: Complaint Entry (Receptionist) 
include __DIR__ . '/../config/db.php';

session_start();
// if ($role !== 'receptionist') {
//     header('Location: login.php'); // Assume login exists
//     exit;
// }
$models = ['Model A', 'Model B', 'Model C']; // Hardcoded product models
$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mobile = $_POST['mobile_number'];
    $name = $_POST['name'] ?? null;
    $city = $_POST['city'] ?? null;
    $state = $_POST['state'] ?? null;
    $product_name = $_POST['product_name'];
    $description = $_POST['description'] ?? null;


    // -------- Validation --------
    if (empty($mobile)) {
        $errors['mobile_number'] = "Mobile number is required.";
    } elseif (!preg_match('/^[0-9]{10}$/', $mobile)) {
        $errors['mobile_number'] = "Mobile number must be 10 digits.";
    }

    if (empty($product_name)) {
        $errors['product_name'] = "Please select a product model.";
    }
    if ($name && strlen($name) < 3) {
        $errors['name'] = "Name must be at least 3 characters.";
    }
    if ($city && strlen($city) < 2) {
        $errors['city'] = "City name too short.";
    }

    // If no errors → save to DB
    if (empty($errors)) {

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
        $insert_complaint->execute([$customer_id, $product_name, $description, $user_id]);

        $_SESSION['success'] = "Complaint submitted successfully!";
        header("Location: complaint_entry.php");
        exit;
    }
}
?>

<?php include __DIR__ . '/../includes/header.php'; ?>
<h3 class="fw-bold mb-3">Complaint Entry</h3>
<?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <?= $_SESSION['success']; ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
    <?php unset($_SESSION['success']); ?>
<?php endif; ?>
<form method="POST" class="card p-4">
    <div class="mb-3">
        <label for="mobile_number" class="form-label">Mobile Number <span class="text-danger">*</span></label>
        <input type="text" class="form-control" id="mobile_number" name="mobile_number" required>
        <?php if (isset($errors['mobile_number'])): ?>
            <div class="text-danger">
                <?= $errors['mobile_number'] ?>
            </div>
        <?php endif; ?>
    </div>
    <div class="mb-3">
        <label for="name" class="form-label">Name</label>
        <input type="text" class="form-control" id="name" name="name">
        <?php if (isset($errors['name'])): ?>
            <div class="text-danger">
                <?= $errors['name'] ?>
            </div>
        <?php endif; ?>
    </div>
    <div class="mb-3">
        <label for="city" class="form-label">City</label>
        <input type="text" class="form-control" id="city" name="city">
        <?php if (isset($errors['city'])): ?>
            <div class="text-danger">
                <?= $errors['city'] ?>
            </div>
        <?php endif; ?>
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
        <?php if (isset($errors['product_name'])): ?>
            <div class="text-danger">
                <?= $errors['product_name'] ?>
            </div>
        <?php endif; ?>
    </div>
    <div class="mb-3">
        <label for="description" class="form-label">Remark</label>
        <textarea class="form-control" id="description" name="description"></textarea>
    </div>
    <button type="submit" class="btn btn-success btn-ui w-25"><i class="material-icons me-2">save</i><b>Complaint Submit</b></button>
</form>

<script>
    // if mobile number is valid, enable other fields
    const mobileInput = document.getElementById('mobile_number');
    const otherFields = [
        document.getElementById('name'),
        document.getElementById('city'),
        document.getElementById('state'),
        document.getElementById('product_name'),
        document.getElementById('description')
    ];

    // Disable all fields initially
    otherFields.forEach(field => field.disabled = true);

    // Enable/disable on input
    mobileInput.addEventListener('input', function() {
        if (/^[0-9]{10}$/.test(this.value.trim())) {
            otherFields.forEach(field => field.disabled = false);
        } else {
            otherFields.forEach(field => {
                field.value = ''; // optional: clear values
                field.disabled = true;
            });
        }
    });

    // Autofill on mobile change
    document.getElementById('mobile_number').addEventListener('blur', function() {
        if (this.value.length == 10) {
            fetch(`../receptionist/autofill_customer.php?mobile=${this.value}`)
                .then(response => response.json())
                .then(data => {
                    if (data) {
                        document.getElementById('name').value = data.name || '';
                        document.getElementById('city').value = data.city || '';
                        document.getElementById('state').value = data.state || '';
                    }
                });
        }
    });

    // Form validation on submit
    document.querySelector("form").addEventListener("submit", function(e) {
        const mobileInput = document.getElementById("mobile_number");
        const mobile = mobileInput.value.trim();

        // Clear old error (if exists)
        let errorDiv = mobileInput.nextElementSibling;
        if (errorDiv && errorDiv.classList.contains("js-error")) {
            errorDiv.remove();
        }

        // Validation rule → must be 10 digits
        if (!/^[0-9]{10}$/.test(mobile)) {
            e.preventDefault(); // stop form submission

            // Show error
            const error = document.createElement("div");
            error.classList.add("text-danger", "js-error");
            error.textContent = "Mobile number must be exactly 10 digits.";
            mobileInput.insertAdjacentElement("afterend", error);
        }
    });
</script>
<?php include __DIR__ . '/../includes/footer.php'; ?>