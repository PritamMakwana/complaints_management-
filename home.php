<?php
```plaintext
complaint_management/
├── config/
│   └── db.php
├── includes/
│   ├── header.php
│   ├── footer.php
│   └── auth.php
├── css/
│   └── styles.css
├── js/
│   └── scripts.js
├── receptionist/
│   └── complaint_entry.php
├── coordinator/
│   ├── dashboard.php
│   └── complaint_details.php
├── spare_parts/
│   ├── dashboard.php
│   └── update_parts.php
├── service_person/
│   ├── dashboard.php
│   └── update_complaint.php
├── sql/
│   └── database.sql
├── index.php
└── logout.php
```

### Database Setup (sql/database.sql)
```sql
CREATE DATABASE complaint_management;
USE complaint_management;

CREATE TABLE customers (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NULL,
    mobile_number VARCHAR(15) NOT NULL UNIQUE,
    city VARCHAR(100) NULL,
    state VARCHAR(100) NULL,
    address TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('receptionist', 'coordinator', 'spare_parts_coordinator', 'admin') NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE service_persons (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    mobile_number VARCHAR(15) NOT NULL UNIQUE,
    area_of_service VARCHAR(100),
    is_available BOOLEAN DEFAULT TRUE
);

CREATE TABLE complaints (
    id INT AUTO_INCREMENT PRIMARY KEY,
    customer_id INT NULL,
    product_name VARCHAR(255) NULL,
    description TEXT NULL,
    status ENUM('New', 'Assigned to Coordinator', 'Assigned to Service Person', 'Closed') DEFAULT 'New',
    receptionist_user_id INT NOT NULL,
    coordinator_user_id INT NULL,
    spare_parts_coordinator_user_id INT NULL,
    service_person_id INT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    closed_at TIMESTAMP NULL,
    closing_remark TEXT NULL,
    FOREIGN KEY (customer_id) REFERENCES customers(id),
    FOREIGN KEY (receptionist_user_id) REFERENCES users(id),
    FOREIGN KEY (coordinator_user_id) REFERENCES users(id),
    FOREIGN KEY (spare_parts_coordinator_user_id) REFERENCES users(id),
    FOREIGN KEY (service_person_id) REFERENCES service_persons(id)
);

CREATE TABLE complaint_details (
    id INT AUTO_INCREMENT PRIMARY KEY,
    complaint_id INT NOT NULL,
    service_needed BOOLEAN DEFAULT FALSE,
    free_spare_parts_needed BOOLEAN DEFAULT FALSE,
    paid_spare_parts_needed BOOLEAN DEFAULT FALSE,
    num_of_coolers INT DEFAULT 1,
    coordinator_remark TEXT NULL,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (complaint_id) REFERENCES complaints(id)
);

CREATE TABLE spare_parts_list (
    id INT AUTO_INCREMENT PRIMARY KEY,
    complaint_id INT NOT NULL,
    part_name VARCHAR(255) NOT NULL,
    quantity INT NOT NULL,
    status ENUM('Pending', 'Shipped', 'Received') DEFAULT 'Pending',
    courier_details TEXT NULL,
    FOREIGN KEY (complaint_id) REFERENCES complaints(id)
);
```

### Database Configuration (config/db.php)
```php
<?php
try {
    $dsn = "mysql:host=localhost;dbname=complaint_management;charset=utf8mb4";
    $pdo = new PDO($dsn, 'root', '', [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]);
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>
```

### Authentication (includes/auth.php)
```php
<?php
session_start();
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
    header("Location: ../index.php");
    exit;
}
function checkRole($requiredRole) {
    if ($_SESSION['role'] !== $requiredRole && $_SESSION['role'] !== 'admin') {
        header("Location: ../index.php");
        exit;
    }
}
?>
```

### Header (includes/header.php)
```php
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Complaint Management System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link href="../css/styles.css" rel="stylesheet">
</head>
<body>
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">Complaint Management</a>
            <div class="navbar-nav">
                <?php if (isset($_SESSION['role'])): ?>
                    <?php if ($_SESSION['role'] === 'receptionist'): ?>
                        <a class="nav-link" href="../receptionist/complaint_entry.php">New Complaint</a>
                    <?php elseif ($_SESSION['role'] === 'coordinator'): ?>
                        <a class="nav-link" href="../coordinator/dashboard.php">Dashboard</a>
                    <?php elseif ($_SESSION['role'] === 'spare_parts_coordinator'): ?>
                        <a class="nav-link" href="../spare_parts/dashboard.php">Spare Parts</a>
                    <?php elseif ($_SESSION['role'] === 'service_person'): ?>
                        <a class="nav-link" href="../service_person/dashboard.php">My Tasks</a>
                    <?php endif; ?>
                    <a class="nav-link" href="../logout.php">Logout</a>
                <?php endif; ?>
            </div>
        </div>
    </nav>
    <div class="container mt-4">
```

### Footer (includes/footer.php)
```php
    </div>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="../js/scripts.js"></script>
</body>
</html>
```

### CSS (css/styles.css)
```css
body {
    background-color: #f8f9fa;
}
.card {
    margin-bottom: 20px;
}
.material-icons {
    vertical-align: middle;
}
```

### JavaScript (js/scripts.js)
```javascript
document.addEventListener('DOMContentLoaded', () => {
    // Autofill customer details based on mobile number
    const mobileInput = document.getElementById('mobile_number');
    if (mobileInput) {
        mobileInput.addEventListener('change', async () => {
            const mobile = mobileInput.value;
            if (mobile.length >= 10) {
                const response = await fetch(`../receptionist/fetch_customer.php?mobile=${mobile}`);
                const data = await response.json();
                if (data.success) {
                    document.getElementById('name').value = data.customer.name || '';
                    document.getElementById('city').value = data.customer.city || '';
                    document.getElementById('state').value = data.customer.state || '';
                    document.getElementById('address').value = data.customer.address || '';
                }
            }
        });
    }

    // Dynamic spare parts form
    const addPartBtn = document.getElementById('add-part');
    if (addPartBtn) {
        addPartBtn.addEventListener('click', () => {
            const partsContainer = document.getElementById('spare-parts-container');
            const partRow = document.createElement('div');
            partRow.className = 'row mb-2';
            partRow.innerHTML = `
                <div class="col-md-6">
                    <input type="text" class="form-control" name="part_name[]" placeholder="Part Name" required>
                </div>
                <div class="col-md-4">
                    <input type="number" class="form-control" name="quantity[]" placeholder="Quantity" min="1" required>
                </div>
                <div class="col-md-2">
                    <button type="button" class="btn btn-danger remove-part"><span class="material-icons">delete</span></button>
                </div>
            `;
            partsContainer.appendChild(partRow);
            partRow.querySelector('.remove-part').addEventListener('click', () => partRow.remove());
        });
    }
});
```

### Login Page (index.php)
```php
<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header("Location: receptionist/complaint_entry.php");
    exit;
}
require_once 'config/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];
    $stmt = $pdo->prepare("SELECT * FROM users WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['role'] = $user['role'];
        header("Location: receptionist/complaint_entry.php");
        exit;
    } else {
        $error = "Invalid credentials";
    }
}
?>
<?php include 'includes/header.php'; ?>
<div class="row justify-content-center">
    <div class="col-md-4">
        <div class="card">
            <div class="card-header">Login</div>
            <div class="card-body">
                <?php if (isset($error)): ?>
                    <div class="alert alert-danger"><?php echo $error; ?></div>
                <?php endif; ?>
                <form method="POST">
                    <div class="mb-3">
                        <label for="username" class="form-label">Username</label>
                        <input type="text" class="form-control" id="username" name="username" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Login</button>
                </form>
            </div>
        </div>
    </div>
</div>
<?php include 'includes/footer.php'; ?>
```

### Receptionist: Complaint Entry (receptionist/complaint_entry.php)
```php
<?php
require_once '../config/db.php';
require_once '../includes/auth.php';
checkRole('receptionist');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $mobile = $_POST['mobile_number'];
    $name = $_POST['name'] ?? null;
    $city = $_POST['city'] ?? null;
    $state = $_POST['state'] ?? null;
    $address = $_POST['address'] ?? null;
    $product_name = $_POST['product_name'] ?? null;
    $description = $_POST['description'] ?? null;

    // Check if customer exists
    $stmt = $pdo->prepare("SELECT id FROM customers WHERE mobile_number = ?");
    $stmt->execute([$mobile]);
    $customer = $stmt->fetch();
    
    if (!$customer) {
        $stmt = $pdo->prepare("INSERT INTO customers (name, mobile_number, city, state, address) VALUES (?, ?, ?, ?, ?)");
        $stmt->execute([$name, $mobile, $city, $state, $address]);
        $customer_id = $pdo->lastInsertId();
    } else {
        $customer_id = $customer['id'];
    }

    // Insert complaint
    $stmt = $pdo->prepare("INSERT INTO complaints (customer_id, product_name, description, receptionist_user_id) VALUES (?, ?, ?, ?)");
    $stmt->execute([$customer_id, $product_name, $description, $_SESSION['user_id']]);
    $success = "Complaint registered successfully!";
}
?>
<?php include '../includes/header.php'; ?>
<div class="card">
    <div class="card-header">New Complaint</div>
    <div class="card-body">
        <?php if (isset($success)): ?>
            <div class="alert alert-success"><?php echo $success; ?></div>
        <?php endif; ?>
        <form method="POST">
            <h5>Customer Information</h5>
            <div class="mb-3">
                <label for="mobile_number" class="form-label">Mobile Number</label>
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
                <label for="address" class="form-label">Address</label>
                <textarea class="form-control" id="address" name="address"></textarea>
            </div>
            <h5>Complaint Details</h5>
            <div class="mb-3">
                <label for="product_name" class="form-label">Product</label>
                <select class="form-control" id="product_name" name="product_name">
                    <option value="Air Cooler Model 1">Air Cooler Model 1</option>
                    <option value="Air Cooler Model 2">Air Cooler Model 2</option>
                </select>
            </div>
            <div class="mb-3">
                <label for="description" class="form-label">Remark</label>
                <textarea class="form-control" id="description" name="description"></textarea>
            </div>
            <button type="submit" class="btn btn-primary">Submit</button>
        </form>
    </div>
</div>
<?php include '../includes/footer.php'; ?>
```

### Receptionist: Fetch Customer (receptionist/fetch_customer.php)
```php
<?php
require_once '../config/db.php';
header('Content-Type: application/json');

if (isset($_GET['mobile'])) {
    $mobile = $_GET['mobile'];
    $stmt = $pdo->prepare("SELECT * FROM customers WHERE mobile_number = ?");
    $stmt->execute([$mobile]);
    $customer = $stmt->fetch();
    if ($customer) {
        echo json_encode(['success' => true, 'customer' => $customer]);
    } else {
        echo json_encode(['success' => false]);
    }
} else {
    echo json_encode(['success' => false, 'error' => 'Mobile number not provided']);
}
?>
```

### Coordinator: Dashboard (coordinator/dashboard.php)
```php
<?php
require_once '../config/db.php';
require_once '../includes/auth.php';
checkRole('coordinator');

$stmt = $pdo->prepare("SELECT c.*, cu.name AS customer_name, cu.mobile_number FROM complaints c LEFT JOIN customers cu ON c.customer_id = cu.id WHERE c.status = 'New'");
$stmt->execute();
$complaints = $stmt->fetchAll();
?>
<?php include '../includes/header.php'; ?>
<div class="card">
    <div class="card-header">Coordinator Dashboard</div>
    <div class="card-body">
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Customer</th>
                    <th>Mobile</th>
                    <th>Product</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($complaints as $complaint): ?>
                    <tr>
                        <td><?php echo $complaint['id']; ?></td>
                        <td><?php echo $complaint['customer_name'] ?? 'N/A'; ?></td>
                        <td><?php echo $complaint['mobile_number'] ?? 'N/A'; ?></td>
                        <td><?php echo $complaint['product_name'] ?? 'N/A'; ?></td>
                        <td><?php echo $complaint['status']; ?></td>
                        <td>
                            <a href="complaint_details.php?id=<?php echo $complaint['id']; ?>" class="btn btn-sm btn-primary">
                                <span class="material-icons">edit</span> Update
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php include '../includes/footer.php'; ?>
```

### Coordinator: Complaint Details (coordinator/complaint_details.php)
```php
<?php
require_once '../config/db.php';
require_once '../includes/auth.php';
checkRole('coordinator');

if (!isset($_GET['id'])) {
    header("Location: dashboard.php");
    exit;
}

$complaint_id = $_GET['id'];
$stmt = $pdo->prepare("SELECT c.*, cu.* FROM complaints c LEFT JOIN customers cu ON c.customer_id = cu.id WHERE c.id = ?");
$stmt->execute([$complaint_id]);
$complaint = $stmt->fetch();

$stmt = $pdo->prepare("SELECT * FROM service_persons WHERE is_available = TRUE");
$stmt->execute();
$service_persons = $stmt->fetchAll();

$stmt = $pdo->prepare("SELECT * FROM users WHERE role = 'spare_parts_coordinator'");
$stmt->execute();
$spare_parts_coordinators = $stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $service_needed = isset($_POST['service_needed']) ? 1 : 0;
    $free_spare_parts_needed = isset($_POST['free_spare_parts_needed']) ? 1 : 0;
    $paid_spare_parts_needed = isset($_POST['paid_spare_parts_needed']) ? 1 : 0;
    $num_of_coolers = $_POST['num_of_coolers'] ?? 1;
    $coordinator_remark = $_POST['coordinator_remark'] ?? null;
    $service_person_id = $_POST['service_person_id'] ?? null;
    $spare_parts_coordinator_id = $_POST['spare_parts_coordinator_id'] ?? null;
    $part_names = $_POST['part_name'] ?? [];
    $quantities = $_POST['quantity'] ?? [];

    // Update customer details
    $stmt = $pdo->prepare("UPDATE customers SET name = ?, city = ?, state = ?, address = ? WHERE id = ?");
    $stmt->execute([$_POST['name'], $_POST['city'], $_POST['state'], $_POST['address'], $complaint['customer_id']]);

    // Insert or update complaint details
    $stmt = $pdo->prepare("INSERT INTO complaint_details (complaint_id, service_needed, free_spare_parts_needed, paid_spare_parts_needed, num_of_coolers, coordinator_remark) VALUES (?, ?, ?, ?, ?, ?) ON DUPLICATE KEY UPDATE service_needed = ?, free_spare_parts_needed = ?, paid_spare_parts_needed = ?, num_of_coolers = ?, coordinator_remark = ?");
    $stmt->execute([$complaint_id, $service_needed, $free_spare_parts_needed, $paid_spare_parts_needed, $num_of_coolers, $coordinator_remark, $service_needed, $free_spare_parts_needed, $paid_spare_parts_needed, $num_of_coolers, $coordinator_remark]);

    // Update complaint status and assignments
    $status = $service_person_id ? 'Assigned to Service Person' : 'Assigned to Coordinator';
    $stmt = $pdo->prepare("UPDATE complaints SET coordinator_user_id = ?, spare_parts_coordinator_user_id = ?, service_person_id = ?, status = ? WHERE id = ?");
    $stmt->execute([$_SESSION['user_id'], $spare_parts_coordinator_id, $service_person_id, $status, $complaint_id]);

    // Insert spare parts
    if (!empty($part_names)) {
        $stmt = $pdo->prepare("INSERT INTO spare_parts_list (complaint_id, part_name, quantity) VALUES (?, ?, ?)");
        for ($i = 0; $i < count($part_names); $i++) {
            if (!empty($part_names[$i]) && !empty($quantities[$i])) {
                $stmt->execute([$complaint_id, $part_names[$i], $quantities[$i]]);
            }
        }
    }

    header("Location: dashboard.php");
    exit;
}
?>
<?php include '../includes/header.php'; ?>
<div class="card">
    <div class="card-header">Complaint #<?php echo $complaint['id']; ?> Details</div>
    <div class="card-body">
        <form method="POST">
            <h5>Customer Information</h5>
            <div class="mb-3">
                <label for="name" class="form-label">Name</label>
                <input type="text" class="form-control" id="name" name="name" value="<?php echo $complaint['name'] ?? ''; ?>">
            </div>
            <div class="mb-3">
                <label for="mobile_number" class="form-label">Mobile Number</label>
                <input type="text" class="form-control" id="mobile_number" name="mobile_number" value="<?php echo $complaint['mobile_number'] ?? ''; ?>" disabled>
            </div>
            <div class="mb-3">
                <label for="city" class="form-label">City</label>
                <input type="text" class="form-control" id="city" name="city" value="<?php echo $complaint['city'] ?? ''; ?>">
            </div>
            <div class="mb-3">
                <label for="state" class="form-label">State</label>
                <input type="text" class="form-control" id="state" name="state" value="<?php echo $complaint['state'] ?? ''; ?>">
            </div>
            <div class="mb-3">
                <label for="address" class="form-label">Address</label>
                <textarea class="form-control" id="address" name="address"><?php echo $complaint['address'] ?? ''; ?></textarea>
            </div>
            <h5>Complaint Details</h5>
            <div class="mb-3">
                <label for="product_name" class="form-label">Product</label>
                <input type="text" class="form-control" id="product_name" value="<?php echo $complaint['product_name'] ?? ''; ?>" disabled>
            </div>
            <div class="mb-3">
                <label for="description" class="form-label">Remark</label>
                <textarea class="form-control" id="description" disabled><?php echo $complaint['description'] ?? ''; ?></textarea>
            </div>
            <h5>Coordinator Details</h5>
            <div class="mb-3">
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" id="service_needed" name="service_needed">
                    <label class="form-check-label" for="service_needed">Service Needed</label>
                </div>
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" id="free_spare_parts_needed" name="free_spare_parts_needed">
                    <label class="form-check-label" for="free_spare_parts_needed">Free Spare Parts Needed</label>
                </div>
                <div class="form-check">
                    <input type="checkbox" class="form-check-input" id="paid_spare_parts_needed" name="paid_spare_parts_needed">
                    <label class="form-check-label" for="paid_spare_parts_needed">Paid Spare Parts Needed</label>
                </div>
            </div>
            <div class="mb-3">
                <label for="num_of_coolers" class="form-label">Number of Coolers</label>
                <input type="number" class="form-control" id="num_of_coolers" name="num_of_coolers" min="1" value="1">
            </div>
            <div class="mb-3">
                <label for="coordinator_remark" class="form-label">Coordinator Remark</label>
                <textarea class="form-control" id="coordinator_remark" name="coordinator_remark"></textarea>
            </div>
            <h5>Assignments</h5>
            <div class="mb-3">
                <label for="service_person_id" class="form-label">Assign to Service Person</label>
                <select class="form-control" id="service_person_id" name="service_person_id">
                    <option value="">Select Service Person</option>
                    <?php foreach ($service_persons as $sp): ?>
                        <option value="<?php echo $sp['id']; ?>"><?php echo $sp['name']; ?> (<?php echo $sp['area_of_service']; ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3">
                <label for="spare_parts_coordinator_id" class="form-label">Assign to Spare Parts Coordinator</label>
                <select class="form-control" id="spare_parts_coordinator_id" name="spare_parts_coordinator_id">
                    <option value="">Select Coordinator</option>
                    <?php foreach ($spare_parts_coordinators as $spc): ?>
                        <option value="<?php echo $spc['id']; ?>"><?php echo $spc['username']; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <h5>Spare Parts</h5>
            <div id="spare-parts-container"></div>
            <button type="button" id="add-part" class="btn btn-secondary mb-3"><span class="material-icons">add</span> Add Spare Part</button>
            <button type="submit" class="btn btn-primary">Save</button>
        </form>
    </div>
</div>
<?php include '../includes/footer.php'; ?>
```

### Spare Parts Coordinator: Dashboard (spare_parts/dashboard.php)
```php
<?php
require_once '../config/db.php';
require_once '../includes/auth.php';
checkRole('spare_parts_coordinator');

$stmt = $pdo->prepare("SELECT c.*, cu.name AS customer_name, cu.mobile_number, spl.part_name, spl.quantity, spl.status, spl.courier_details 
                       FROM complaints c 
                       LEFT JOIN customers cu ON c.customer_id = cu.id 
                       LEFT JOIN spare_parts_list spl ON c.id = spl.complaint_id 
                       WHERE c.spare_parts_coordinator_user_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$complaints = $stmt->fetchAll();
?>
<?php include '../includes/header.php'; ?>
<div class="card">
    <div class="card-header">Spare Parts Dashboard</div>
    <div class="card-body">
        <table class="table">
            <thead>
                <tr>
                    <th>Complaint ID</th>
                    <th>Customer</th>
                    <th>Part Name</th>
                    <th>Quantity</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($complaints as $complaint): ?>
                    <tr>
                        <td><?php echo $complaint['id']; ?></td>
                        <td><?php echo $complaint['customer_name'] ?? 'N/A'; ?></td>
                        <td><?php echo $complaint['part_name'] ?? 'N/A'; ?></td>
                        <td><?php echo $complaint['quantity'] ?? 'N/A'; ?></td>
                        <td><?php echo $complaint['status'] ?? 'N/A'; ?></td>
                        <td>
                            <a href="update_parts.php?id=<?php echo $complaint['id']; ?>" class="btn btn-sm btn-primary">
                                <span class="material-icons">edit</span> Update
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php include '../includes/footer.php'; ?>
```

### Spare Parts Coordinator: Update Parts (spare_parts/update_parts.php)
```php
<?php
require_once '../config/db.php';
require_once '../includes/auth.php';
checkRole('spare_parts_coordinator');

if (!isset($_GET['id'])) {
    header("Location: dashboard.php");
    exit;
}

$complaint_id = $_GET['id'];
$stmt = $pdo->prepare("SELECT spl.*, c.customer_id, cu.name AS customer_name 
                       FROM spare_parts_list spl 
                       LEFT JOIN complaints c ON spl.complaint_id = c.id 
                       LEFT JOIN customers cu ON c.customer_id = cu.id 
                       WHERE spl.complaint_id = ?");
$stmt->execute([$complaint_id]);
$parts = $stmt->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    foreach ($_POST['part_id'] as $index => $part_id) {
        $status = $_POST['status'][$index];
        $courier_details = $_POST['courier_details'][$index];
        $stmt = $pdo->prepare("UPDATE spare_parts_list SET status = ?, courier_details = ? WHERE id = ?");
        $stmt->execute([$status, $courier_details, $part_id]);
    }
    header("Location: dashboard.php");
    exit;
}
?>
<?php include '../includes/header.php'; ?>
<div class="card">
    <div class="card-header">Update Spare Parts for Complaint #<?php echo $complaint_id; ?></div>
    <div class="card-body">
        <form method="POST">
            <?php foreach ($parts as $part): ?>
                <div class="row mb-3">
                    <div class="col-md-3">
                        <label class="form-label">Part Name</label>
                        <input type="text" class="form-control" value="<?php echo $part['part_name']; ?>" disabled>
                        <input type="hidden" name="part_id[]" value="<?php echo $part['id']; ?>">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Quantity</label>
                        <input type="number" class="form-control" value="<?php echo $part['quantity']; ?>" disabled>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Status</label>
                        <select class="form-control" name="status[]">
                            <option value="Pending" <?php echo $part['status'] === 'Pending' ? 'selected' : ''; ?>>Pending</option>
                            <option value="Shipped" <?php echo $part['status'] === 'Shipped' ? 'selected' : ''; ?>>Shipped</option>
                            <option value="Received" <?php echo $part['status'] === 'Received' ? 'selected' : ''; ?>>Received</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Courier Details</label>
                        <input type="text" class="form-control" name="courier_details[]" value="<?php echo $part['courier_details'] ?? ''; ?>">
                    </div>
                </div>
            <?php endforeach; ?>
            <button type="submit" class="btn btn-primary">Update</button>
        </form>
    </div>
</div>
<?php include '../includes/footer.php'; ?>
```

### Service Person: Dashboard (service_person/dashboard.php)
```php
<?php
require_once '../config/db.php';
require_once '../includes/auth.php';
checkRole('service_person');

$stmt = $pdo->prepare("SELECT c.*, cu.name AS customer_name, cu.mobile_number, cd.coordinator_remark 
                       FROM complaints c 
                       LEFT JOIN customers cu ON c.customer_id = cu.id 
                       LEFT JOIN complaint_details cd ON c.id = cd.complaint_id 
                       WHERE c.service_person_id = ?");
$stmt->execute([$_SESSION['user_id']]);
$complaints = $stmt->fetchAll();
?>
<?php include '../includes/header.php'; ?>
<div class="card">
    <div class="card-header">Service Person Dashboard</div>
    <div class="card-body">
        <table class="table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Customer</th>
                    <th>Mobile</th>
                    <th>Product</th>
                    <th>Status</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($complaints as $complaint): ?>
                    <tr>
                        <td><?php echo $complaint['id']; ?></td>
                        <td><?php echo $complaint['customer_name'] ?? 'N/A'; ?></td>
                        <td><?php echo $complaint['mobile_number'] ?? 'N/A'; ?></td>
                        <td><?php echo $complaint['product_name'] ?? 'N/A'; ?></td>
                        <td><?php echo $complaint['status']; ?></td>
                        <td>
                            <a href="update_complaint.php?id=<?php echo $complaint['id']; ?>" class="btn btn-sm btn-primary">
                                <span class="material-icons">edit</span> Update
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php include '../includes/footer.php'; ?>
```

### Service Person: Update Complaint (service_person/update_complaint.php)
```php
<?php
require_once '../config/db.php';
require_once '../includes/auth.php';
checkRole('service_person');

if (!isset($_GET['id'])) {
    header("Location: dashboard.php");
    exit;
}

$complaint_id = $_GET['id'];
$stmt = $pdo->prepare("SELECT c.*, cu.*, cd.coordinator_remark 
                       FROM complaints c 
                       LEFT JOIN customers cu ON c.customer_id = cu.id 
                       LEFT JOIN complaint_details cd ON c.id = cd.complaint_id 
                       WHERE c.id = ?");
$stmt->execute([$complaint_id]);
$complaint = $stmt->fetch();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $closing_remark = $_POST['closing_remark'];
    $reason_for_closure = $_POST['reason_for_closure'];
    $stmt = $pdo->prepare("UPDATE complaints SET status = 'Closed', closing_remark = ?, closed_at = NOW() WHERE id = ?");
    $stmt->execute([$closing_remark . " (Reason: $reason_for_closure)", $complaint_id]);
    header("Location: dashboard.php");
    exit;
}
?>
<?php include '../includes/header.php'; ?>
<div class="card">
    <div class="card-header">Update Complaint #<?php echo $complaint['id']; ?></div>
    <div class="card-body">
        <h5>Customer Information</h5>
        <p><strong>Name:</strong> <?php echo $complaint['name'] ?? 'N/A'; ?></p>
        <p><strong>Mobile:</strong> <?php echo $complaint['mobile_number'] ?? 'N/A'; ?></p>
        <p><strong>Address:</strong> <?php echo $complaint['address'] ?? 'N/A'; ?></p>
        <h5>Complaint Details</h5>
        <p><strong>Product:</strong> <?php echo $complaint['product_name'] ?? 'N/A'; ?></p>
        <p><strong>Description:</strong> <?php echo $complaint['description'] ?? 'N/A'; ?></p>
        <p><strong>Coordinator Remark:</strong> <?php echo $complaint['coordinator_remark'] ?? 'N/A'; ?></p>
        <form method="POST">
            <div class="mb-3">
                <label for="closing_remark" class="form-label">Closing Remark</label>
                <textarea class="form-control" id="closing_remark" name="closing_remark" required></textarea>
            </div>
            <div class="mb-3">
                <label for="reason_for_closure" class="form-label">Reason for Closure</label>
                <select class="form-control" id="reason_for_closure" name="reason_for_closure" required>
                    <option value="Service Fulfilled">Service Fulfilled</option>
                    <option value="Warranty Expired">Warranty Expired</option>
                    <option value="Customer Unreachable">Customer Unreachable</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary">Close Complaint</button>
        </form>
    </div>
</div>
<?php include '../includes/footer.php'; ?>
```

### Logout (logout.php)
```php
<?php
session_start();
session_destroy();
header("Location: index.php");
exit;
?>
```