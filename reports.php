<?php
// reports.php - Additional Features: Reporting
// Accessible by admin or coordinators
include '/config/db.php';
session_start();
if (!in_array($_SESSION['role'], ['admin', 'coordinator'])) {
    header('Location: login.php');
    exit;
}

// Example: Complaint History by Customer
$customer_id = $_GET['customer_id'] ?? 0;
if ($customer_id) {
    $stmt = $pdo->prepare("SELECT * FROM complaints WHERE customer_id = ?");
    $stmt->execute([$customer_id]);
    $history = $stmt->fetchAll();
    // Display in table
}

// Service Person Performance
$performance_stmt = $pdo->query("SELECT sp.name, COUNT(c.id) as closed_count 
                                 FROM service_persons sp 
                                 LEFT JOIN complaints c ON sp.id = c.service_person_id AND c.status = 'Closed' 
                                 GROUP BY sp.id");
$performance = $performance_stmt->fetchAll();

// Complaint Trends by City
$trends_stmt = $pdo->query("SELECT cu.city, COUNT(c.id) as count 
                            FROM complaints c 
                            LEFT JOIN customers cu ON c.customer_id = cu.id 
                            GROUP BY cu.city");
$trends = $trends_stmt->fetchAll();

// Similar for state, product
?>

<?php include '/includes/header.php'; ?>
<h2>Reports</h2>
<!-- Add forms to select customer for history, time frames, etc. -->
<h3>Service Person Performance</h3>
<table class="table">
    <thead>
        <tr>
            <th>Name</th>
            <th>Closed Complaints</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($performance as $perf): ?>
            <tr>
                <td><?= $perf['name'] ?></td>
                <td><?= $perf['closed_count'] ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>

<h3>Complaint Trends by City</h3>
<table class="table">
    <thead>
        <tr>
            <th>City</th>
            <th>Count</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($trends as $trend): ?>
            <tr>
                <td><?= $trend['city'] ?></td>
                <td><?= $trend['count'] ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?php include '/includes/footer.php'; ?>