<?php
include __DIR__ . '/../config/db.php';
session_start();
// if (!in_array($_SESSION['role'], ['admin', 'coordinator'])) {
//     header('Location: login.php');
//     exit;
// }

// Handle search and filters
$search_query = $_POST['search_query'] ?? $_GET['search_query'] ?? '';
$service_person_id = $_GET['service_person_id'] ?? '';
$from_date = $_POST['from_date'] ?? date('Y-m-01'); // Default to start of month
$to_date = $_POST['to_date'] ?? date('Y-m-t'); // End of month
$complaint_id = $_GET['complaint_id'] ?? '';

// Fetch service persons
$where = '';
$params = [];
if ($search_query) {
    $where = "WHERE name LIKE ? OR mobile_number LIKE ?";
    $params = ["%$search_query%", "%$search_query%"];
}
$sp_stmt = $pdo->prepare("
    SELECT id, name, mobile_number, area_of_service
    FROM service_persons
    $where
    ORDER BY name ASC
");
$sp_stmt->execute($params);
$service_persons = $sp_stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch performance data for selected service person
$closed_complaints = [];
$summary = [
    'total_closed' => 0,
    'avg_resolution_time_hours' => 0,
    'avg_resolution_time_days' => 0,
    'min_resolution_time_hours' => null,
    'max_resolution_time_hours' => null,
    'time_buckets' => [
        '0_to_1_day' => 0,
        '1_to_2_days' => 0,
        '2_to_3_days' => 0,
        '3_to_4_days' => 0,
        '4_to_5_days' => 0
    ]
];
if ($service_person_id) {
    $perf_stmt = $pdo->prepare("
        SELECT c.id, cu.name as customer_name, c.product_name, c.description, c.created_at, c.closed_at, c.closing_remark,
               TIMESTAMPDIFF(HOUR, c.created_at, c.closed_at) as resolution_time_hours,
               TIMESTAMPDIFF(DAY, c.created_at, c.closed_at) as resolution_time_days
        FROM complaints c
        LEFT JOIN customers cu ON c.customer_id = cu.id
        WHERE c.service_person_id = ? AND c.status = 'Closed' AND c.closed_at BETWEEN ? AND ?
        ORDER BY c.closed_at DESC
    ");
    $perf_stmt->execute([(int)$service_person_id, $from_date . ' 00:00:00', $to_date . ' 23:59:59']);
    $closed_complaints = $perf_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Summary metrics
    if ($closed_complaints) {
        $summary['total_closed'] = count($closed_complaints);
        $resolution_times = array_column($closed_complaints, 'resolution_time_hours');
        $summary['avg_resolution_time_hours'] = round(array_sum($resolution_times) / $summary['total_closed'], 2);
        $summary['avg_resolution_time_days'] = round($summary['avg_resolution_time_hours'] / 24, 2);
        $summary['min_resolution_time_hours'] = min($resolution_times);
        $summary['max_resolution_time_hours'] = max($resolution_times);

        // Categorize into time buckets (0-1, 1-2, 2-3, 3-4, 4-5 days)
        foreach ($closed_complaints as $complaint) {
            $days = $complaint['resolution_time_days'];
            if ($days < 1) {
                $summary['time_buckets']['0_to_1_day']++;
            } elseif ($days < 2) {
                $summary['time_buckets']['1_to_2_days']++;
            } elseif ($days < 3) {
                $summary['time_buckets']['2_to_3_days']++;
            } elseif ($days < 4) {
                $summary['time_buckets']['3_to_4_days']++;
            } elseif ($days < 5) {
                $summary['time_buckets']['4_to_5_days']++;
            }
        }
    }
}

// Fetch complaint details if selected
$complaint_details = null;
if ($complaint_id && $service_person_id) {
    $detail_stmt = $pdo->prepare("
        SELECT c.*, cd.service_needed, cd.free_spare_parts_needed, cd.paid_spare_parts_needed, cd.num_of_coolers, cd.coordinator_remark,
               cu.name as customer_name, cu.mobile_number, cu.city, cu.state, cu.address,
               sp.name as service_person_name
        FROM complaints c
        LEFT JOIN complaint_details cd ON c.id = cd.complaint_id
        LEFT JOIN customers cu ON c.customer_id = cu.id
        LEFT JOIN service_persons sp ON c.service_person_id = sp.id
        WHERE c.id = ? AND c.service_person_id = ?
    ");
    $detail_stmt->execute([(int)$complaint_id, (int)$service_person_id]);
    $complaint_details = $detail_stmt->fetch(PDO::FETCH_ASSOC);

    // Fetch spare parts
    $spare_stmt = $pdo->prepare("SELECT * FROM spare_parts_list WHERE complaint_id = ?");
    $spare_stmt->execute([(int)$complaint_id]);
    $spare_parts = $spare_stmt->fetchAll(PDO::FETCH_ASSOC);
}

?>

<?php include __DIR__ . '/../includes/header.php'; ?>
<!-- Add DataTables and Chart.js CSS/JS -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>

<div class="container mt-4">
    <h2>Service Person Performance Reports</h2>

    <!-- Service Person Search Form -->
    <form method="POST" class="mb-4">
        <div class="input-group">
            <span class="input-group-text"><i class="material-icons">search</i></span>
            <input type="text" class="form-control" name="search_query" placeholder="Search by Name or Mobile Number" value="<?= htmlspecialchars($search_query) ?>">
            <button type="submit" class="btn btn-info btn-ui text-light"><i class="material-icons">search</i> Search</button>
            <a href="service_person_performance.php" class="btn btn-light btn-ui text-info"><i class="material-icons">refresh</i> Reset</a>
        </div>
    </form>

    <!-- Service Persons List -->
    <h3>Service Persons</h3>
    <?php if ($service_persons): ?>
        <table id="servicePersonTable" class="table table-datatable table-striped table-hover">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Mobile Number</th>
                    <th>Area of Service</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($service_persons as $sp): ?>
                    <tr>
                        <td><?= htmlspecialchars($sp['name']) ?></td>
                        <td><?= htmlspecialchars($sp['mobile_number']) ?></td>
                        <td><?= htmlspecialchars($sp['area_of_service'] ?? 'N/A') ?></td>
                        <td>
                            <a href="?service_person_id=<?= $sp['id'] ?>" class="btn btn-sm btn-info view-performance text-light" title="View Performance">
                                <i class="material-icons">assessment</i>
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <p class="alert alert-info">No service persons found.</p>
    <?php endif; ?>

    <!-- Performance Report -->
    <?php if ($service_person_id): ?>
        <h3 class="mt-5">Performance for Service Person ID: <?= $service_person_id ?></h3>

        <!-- Date Filter Form -->
        <form method="POST" class="mb-4">
            <div class="row">
                <div class="col-md-4">
                    <label for="from_date">From Date</label>
                    <input type="date" class="form-control" name="from_date" value="<?= $from_date ?>">
                </div>
                <div class="col-md-4">
                    <label for="to_date">To Date</label>
                    <input type="date" class="form-control" name="to_date" value="<?= $to_date ?>">
                </div>
                <div class="col-md-4 align-self-end">
                    <button type="submit" class="btn btn-info btn-ui text-light"><i class="material-icons">filter_list</i> Filter</button>
                </div>
            </div>
        </form>

        <!-- Summary Metrics -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="card-title mb-0">Performance Summary</h5>
            </div>
            <div class="card-body">
                <p><strong>Total Closed Complaints:</strong> <?= $summary['total_closed'] ?></p>
                <p><strong>Average Resolution Time (Hours):</strong> <?= $summary['avg_resolution_time_hours'] ?></p>
                <p><strong>Average Resolution Time (Days):</strong> <?= $summary['avg_resolution_time_days'] ?></p>
                <p><strong>Minimum Resolution Time (Hours):</strong> <?= $summary['min_resolution_time_hours'] ?? 'N/A' ?></p>
                <p><strong>Maximum Resolution Time (Hours):</strong> <?= $summary['max_resolution_time_hours'] ?? 'N/A' ?></p>
                <h6>Resolution Time Distribution</h6>
                <p><strong>0-1 Day:</strong> <?= $summary['time_buckets']['0_to_1_day'] ?> complaints</p>
                <p><strong>1-2 Days:</strong> <?= $summary['time_buckets']['1_to_2_days'] ?> complaints</p>
                <p><strong>2-3 Days:</strong> <?= $summary['time_buckets']['2_to_3_days'] ?> complaints</p>
                <p><strong>3-4 Days:</strong> <?= $summary['time_buckets']['3_to_4_days'] ?> complaints</p>
                <p><strong>4-5 Days:</strong> <?= $summary['time_buckets']['4_to_5_days'] ?> complaints</p>
                <canvas id="resolutionTimeChart" height="100"></canvas>
            </div>
        </div>

        <!-- Closed Complaints List -->
        <?php if ($closed_complaints): ?>
            <table id="performanceTable" class="table table-datatable table-striped table-hover">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Customer</th>
                        <th>Product</th>
                        <th>Description</th>
                        <th>Created At</th>
                        <th>Closed At</th>
                        <th>Resolution Time (Hours)</th>
                        <th>Resolution Time (Days)</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($closed_complaints as $complaint): ?>
                        <tr>
                            <td><?= $complaint['id'] ?></td>
                            <td><?= htmlspecialchars($complaint['customer_name'] ?? 'N/A') ?></td>
                            <td><?= htmlspecialchars($complaint['product_name'] ?? 'N/A') ?></td>
                            <td><?= htmlspecialchars($complaint['description'] ?? 'N/A') ?></td>
                            <td><?= $complaint['created_at'] ?></td>
                            <td><?= $complaint['closed_at'] ?></td>
                            <td><?= $complaint['resolution_time_hours'] ?? 'N/A' ?></td>
                            <td><?= $complaint['resolution_time_days'] ?? 'N/A' ?></td>
                            <td>
                                <a href="?service_person_id=<?= $service_person_id ?>&complaint_id=<?= $complaint['id'] ?>" class="btn btn-sm btn-info view-details text-light" title="View Details">
                                    <i class="material-icons">visibility</i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php else: ?>
            <p class="alert alert-info">No closed complaints found in the selected time frame.</p>
        <?php endif; ?>
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

<script>
    $(document).ready(function() {
        // Initialize Chart.js for resolution time distribution
        <?php if ($service_person_id && $closed_complaints): ?>
            var ctx = document.getElementById('resolutionTimeChart').getContext('2d');
            var chart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: ['0-1 Day', '1-2 Days', '2-3 Days', '3-4 Days', '4-5 Days'],
                    datasets: [{
                        label: 'Number of Complaints',
                        data: [
                            <?= $summary['time_buckets']['0_to_1_day'] ?>,
                            <?= $summary['time_buckets']['1_to_2_days'] ?>,
                            <?= $summary['time_buckets']['2_to_3_days'] ?>,
                            <?= $summary['time_buckets']['3_to_4_days'] ?>,
                            <?= $summary['time_buckets']['4_to_5_days'] ?>
                        ],
                        backgroundColor: ['#36A2EB', '#FFCE56', '#FF6384', '#4BC0C0', '#9966FF'],
                        borderColor: ['#36A2EB', '#FFCE56', '#FF6384', '#4BC0C0', '#9966FF'],
                        borderWidth: 1
                    }]
                },
                options: {
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Number of Complaints'
                            }
                        },
                        x: {
                            title: {
                                display: true,
                                text: 'Resolution Time'
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
        <?php endif; ?>
    });
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>