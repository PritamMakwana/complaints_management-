<?php
include __DIR__ . '/config/db.php';
session_start();
// if (!in_array($_SESSION['role'], ['admin', 'coordinator'])) {
//     header('Location: login.php');
//     exit;
// }

// Handle filters
$from_date = $_POST['from_date'] ?? date('Y-m-01'); // Default to start of month
$to_date = $_POST['to_date'] ?? date('Y-m-t'); // End of month

// Fetch complaint trends
$trends = [
    'by_city' => [],
    'by_state' => [],
    'by_product' => []
];
$summary = [
    'total_complaints' => 0,
    'unique_cities' => 0,
    'unique_states' => 0,
    'unique_products' => 0,
    'top_city' => ['name' => 'N/A', 'count' => 0],
    'top_state' => ['name' => 'N/A', 'count' => 0],
    'top_product' => ['name' => 'N/A', 'count' => 0]
];

// Fetch complaints grouped by city
$city_stmt = $pdo->prepare("
    SELECT cu.city, COUNT(*) as complaint_count
    FROM complaints c
    LEFT JOIN customers cu ON c.customer_id = cu.id
    WHERE c.created_at BETWEEN ? AND ?
    GROUP BY cu.city
    ORDER BY complaint_count DESC
    LIMIT 5
");
$city_stmt->execute([$from_date . ' 00:00:00', $to_date . ' 23:59:59']);
$trends['by_city'] = $city_stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch complaints grouped by state
$state_stmt = $pdo->prepare("
    SELECT cu.state, COUNT(*) as complaint_count
    FROM complaints c
    LEFT JOIN customers cu ON c.customer_id = cu.id
    WHERE c.created_at BETWEEN ? AND ?
    GROUP BY cu.state
    ORDER BY complaint_count DESC
    LIMIT 5
");
$state_stmt->execute([$from_date . ' 00:00:00', $to_date . ' 23:59:59']);
$trends['by_state'] = $state_stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch complaints grouped by product
$product_stmt = $pdo->prepare("
    SELECT c.product_name, COUNT(*) as complaint_count
    FROM complaints c
    WHERE c.created_at BETWEEN ? AND ?
    GROUP BY c.product_name
    ORDER BY complaint_count DESC
    LIMIT 5
");
$product_stmt->execute([$from_date . ' 00:00:00', $to_date . ' 23:59:59']);
$trends['by_product'] = $product_stmt->fetchAll(PDO::FETCH_ASSOC);

// Summary metrics
$total_stmt = $pdo->prepare("
    SELECT 
        COUNT(*) as total_complaints,
        COUNT(DISTINCT cu.city) as unique_cities,
        COUNT(DISTINCT cu.state) as unique_states,
        COUNT(DISTINCT c.product_name) as unique_products
    FROM complaints c
    LEFT JOIN customers cu ON c.customer_id = cu.id
    WHERE c.created_at BETWEEN ? AND ?
");
$total_stmt->execute([$from_date . ' 00:00:00', $to_date . ' 23:59:59']);
$summary_data = $total_stmt->fetch(PDO::FETCH_ASSOC);
$summary['total_complaints'] = $summary_data['total_complaints'];
$summary['unique_cities'] = $summary_data['unique_cities'];
$summary['unique_states'] = $summary_data['unique_states'];
$summary['unique_products'] = $summary_data['unique_products'];

// Top entities
if (!empty($trends['by_city'])) {
    $summary['top_city'] = ['name' => $trends['by_city'][0]['city'] ?? 'N/A', 'count' => $trends['by_city'][0]['complaint_count']];
}
if (!empty($trends['by_state'])) {
    $summary['top_state'] = ['name' => $trends['by_state'][0]['state'] ?? 'N/A', 'count' => $trends['by_state'][0]['complaint_count']];
}
if (!empty($trends['by_product'])) {
    $summary['top_product'] = ['name' => $trends['by_product'][0]['product_name'] ?? 'N/A', 'count' => $trends['by_product'][0]['complaint_count']];
}
// search
$search = $_GET['search'] ?? '';
$where = "WHERE c.created_at BETWEEN ? AND ?";
$params = [$from_date . ' 00:00:00', $to_date . ' 23:59:59'];
if ($search) {
    $where .= " AND (c.status LIKE ? OR cu.name LIKE ? OR c.product_name LIKE ? OR c.closing_remark LIKE ? OR c.description LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
    $params[] = "%$search%";
}
$stmt = $pdo->prepare("
    SELECT c.id, cu.name as customer_name, c.product_name, c.status, c.created_at, c.closed_at
    ,c.closing_remark,c.description,TIMESTAMPDIFF(HOUR, c.created_at, c.closed_at) as resolution_time_hours
    FROM complaints c
    LEFT JOIN customers cu ON c.customer_id = cu.id
    $where
    ORDER BY c.created_at DESC
");
$stmt->execute($params);
$complaintsSearch = $stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php include __DIR__ . '/includes/header.php'; ?>
<!-- Add DataTables and Chart.js CSS/JS -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>

<div class="container mt-4">
    <h2>Identify Common Complaints</h2>

    <form method="GET" class="mb-3">
        <div class="input-group">
            <span class="input-group-text"><i class="material-icons">search</i></span>
            <input type="text" id="search" name="search" class="form-control" placeholder="Search by status, customer, or product..." value="<?= htmlspecialchars($search) ?>">
            <button type="submit" class="btn btn-primary"><i class="material-icons">search</i> Search</button>
            <a href="complaint_trends.php" class="btn btn-secondary"><i class="material-icons">refresh</i> Reset</a>
        </div>
    </form>


    <table class="table table-datatable table-striped">
        <thead>
            <tr>
                <th>ID</th>
                <th>Customer</th>
                <th>Product</th>
                <th>Status</th>
                <th>Created At</th>
                <th>Closed At</th>
                <th>Description</th>
                <th>Closing Remark</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($complaintsSearch as $complaint): ?>
                <tr>
                    <td><?= htmlspecialchars($complaint['id']) ?></td>
                    <td><?= htmlspecialchars($complaint['customer_name']) ?></td>
                    <td><?= htmlspecialchars($complaint['product_name']) ?></td>
                    <td><?= htmlspecialchars($complaint['status']) ?></td>
                    <td><?= htmlspecialchars(date("d-m-Y h:i A", strtotime($complaint['created_at']))) ?></td>
                    <td><?= htmlspecialchars(date("d-m-Y h:i A", strtotime($complaint['closed_at']))) ?></td>
                    <td><?= htmlspecialchars($complaint['description']) ?></td>
                    <td><?= htmlspecialchars($complaint['closing_remark']) ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <h2>Complaint Trends</h2>
    <!-- Date Filter Form -->
    <form method="POST" class="mb-4">
        <div class="row">
            <div class="col-md-4">
                <label for="from_date">From Date</label>
                <input type="date" class="form-control" name="from_date" value="<?= htmlspecialchars($from_date) ?>">
            </div>
            <div class="col-md-4">
                <label for="to_date">To Date</label>
                <input type="date" class="form-control" name="to_date" value="<?= htmlspecialchars($to_date) ?>">
            </div>
        </div>
    </form>

    <!-- Summary Metrics -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="card-title mb-0">Complaint Trends Summary</h5>
        </div>
        <div class="card-body">
            <p><strong>Total Complaints:</strong> <?= $summary['total_complaints'] ?></p>
            <p><strong>Unique Cities:</strong> <?= $summary['unique_cities'] ?></p>
            <p><strong>Unique States:</strong> <?= $summary['unique_states'] ?></p>
            <p><strong>Unique Products:</strong> <?= $summary['unique_products'] ?></p>
            <p><strong>Top City:</strong> <?= htmlspecialchars($summary['top_city']['name']) ?> (<?= $summary['top_city']['count'] ?> complaints)</p>
            <p><strong>Top State:</strong> <?= htmlspecialchars($summary['top_state']['name']) ?> (<?= $summary['top_state']['count'] ?> complaints)</p>
            <p><strong>Top Product:</strong> <?= htmlspecialchars($summary['top_product']['name']) ?> (<?= $summary['top_product']['count'] ?> complaints)</p>
        </div>
    </div>

    <!-- Complaint Trends Table -->
    <h3>Complaint Breakdown</h3>
    <table id="trendsTable" class="table table-datatable table-striped table-hover">
        <thead>
            <tr>
                <th>Category</th>
                <th>Value</th>
                <th>Complaint Count</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($trends['by_city'] as $row): ?>
                <tr>
                    <td>City</td>
                    <td><?= htmlspecialchars($row['city'] ?? 'N/A') ?></td>
                    <td><?= $row['complaint_count'] ?></td>
                </tr>
            <?php endforeach; ?>
            <?php foreach ($trends['by_state'] as $row): ?>
                <tr>
                    <td>State</td>
                    <td><?= htmlspecialchars($row['state'] ?? 'N/A') ?></td>
                    <td><?= $row['complaint_count'] ?></td>
                </tr>
            <?php endforeach; ?>
            <?php foreach ($trends['by_product'] as $row): ?>
                <tr>
                    <td>Product</td>
                    <td><?= htmlspecialchars($row['product_name'] ?? 'N/A') ?></td>
                    <td><?= $row['complaint_count'] ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <!-- Charts -->
    <div class="row mt-5">
        <div class="col-md-4">
            <h5>Complaints by City</h5>
            <canvas id="cityChart" height="200"></canvas>
        </div>
        <div class="col-md-4">
            <h5>Complaints by State</h5>
            <canvas id="stateChart" height="200"></canvas>
        </div>
        <div class="col-md-4">
            <h5>Complaints by Product</h5>
            <canvas id="productChart" height="200"></canvas>
        </div>
    </div>
</div>

<script>
    $(document).ready(function() {
        // Initialize Chart.js for city chart
        var cityCtx = document.getElementById('cityChart').getContext('2d');
        new Chart(cityCtx, {
            type: 'bar',
            data: {
                labels: [<?php foreach ($trends['by_city'] as $row) {
                                echo "'" . addslashes($row['city'] ?? 'N/A') . "',";
                            } ?>],
                datasets: [{
                    label: 'Complaints by City',
                    data: [<?php foreach ($trends['by_city'] as $row) {
                                echo $row['complaint_count'] . ',';
                            } ?>],
                    backgroundColor: '#36A2EB',
                    borderColor: '#36A2EB',
                    borderWidth: 1
                }]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Complaint Count'
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'City'
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });

        // Initialize Chart.js for state chart
        var stateCtx = document.getElementById('stateChart').getContext('2d');
        new Chart(stateCtx, {
            type: 'bar',
            data: {
                labels: [<?php foreach ($trends['by_state'] as $row) {
                                echo "'" . addslashes($row['state'] ?? 'N/A') . "',";
                            } ?>],
                datasets: [{
                    label: 'Complaints by State',
                    data: [<?php foreach ($trends['by_state'] as $row) {
                                echo $row['complaint_count'] . ',';
                            } ?>],
                    backgroundColor: '#FFCE56',
                    borderColor: '#FFCE56',
                    borderWidth: 1
                }]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Complaint Count'
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'State'
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });

        // Initialize Chart.js for product chart
        var productCtx = document.getElementById('productChart').getContext('2d');
        new Chart(productCtx, {
            type: 'bar',
            data: {
                labels: [<?php foreach ($trends['by_product'] as $row) {
                                echo "'" . addslashes($row['product_name'] ?? 'N/A') . "',";
                            } ?>],
                datasets: [{
                    label: 'Complaints by Product',
                    data: [<?php foreach ($trends['by_product'] as $row) {
                                echo $row['complaint_count'] . ',';
                            } ?>],
                    backgroundColor: '#FF6384',
                    borderColor: '#FF6384',
                    borderWidth: 1
                }]
            },
            options: {
                scales: {
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Complaint Count'
                        }
                    },
                    x: {
                        title: {
                            display: true,
                            text: 'Product'
                        }
                    }
                },
                plugins: {
                    legend: {
                        display: false
                    }
                }
            }
        });
    });
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>