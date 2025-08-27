<?php
include __DIR__ . '/../config/db.php';
session_start();
// if (!in_array($_SESSION['role'], ['admin', 'coordinator'])) {
//     header('Location: login.php');
//     exit;
// }

// Handle filters
$from_date = $_POST['from_date'] ?? date('Y-m-01'); // Default to start of month
$to_date = $_POST['to_date'] ?? date('Y-m-t'); // End of month
$service_person_ids = $_POST['service_person_ids'] ?? []; // Array of selected service person IDs
$selected_date = $_GET['selected_date'] ?? ''; // Selected calendar day

// Fetch service persons for dropdown
$sp_stmt = $pdo->prepare("
    SELECT id, name
    FROM service_persons
    ORDER BY name ASC
");
$sp_stmt->execute();
$service_persons = $sp_stmt->fetchAll(PDO::FETCH_ASSOC);

// Fetch complaints for calendar and details
$where = "WHERE c.created_at BETWEEN ? AND ?";
$params = [$from_date . ' 00:00:00', $to_date . ' 23:59:59'];
if (!empty($service_person_ids)) {
    $placeholders = implode(',', array_fill(0, count($service_person_ids), '?'));
    $where .= " AND c.service_person_id IN ($placeholders)";
    $params = array_merge($params, $service_person_ids);
}
$complaints_stmt = $pdo->prepare("
    SELECT c.id, c.customer_id, c.product_name, c.status, c.created_at, c.closed_at, c.description, c.closing_remark,
           cu.name as customer_name, sp.name as service_person_name,
           DATE(c.created_at) as complaint_date,
           TIMESTAMPDIFF(HOUR, c.created_at, c.closed_at) as resolution_time_hours
    FROM complaints c
    LEFT JOIN customers cu ON c.customer_id = cu.id
    LEFT JOIN service_persons sp ON c.service_person_id = sp.id
    $where
    ORDER BY c.created_at DESC
");
$complaints_stmt->execute($params);
$complaints = $complaints_stmt->fetchAll(PDO::FETCH_ASSOC);

// Group complaints by date for calendar
$calendar_data = [];
$closed_complaints_per_day = [];
foreach ($complaints as $complaint) {
    $date = $complaint['complaint_date'];
    if (!isset($calendar_data[$date])) {
        $calendar_data[$date] = [];
        $closed_complaints_per_day[$date] = 0;
    }
    $calendar_data[$date][] = $complaint;
    if ($complaint['status'] === 'Closed') {
        $closed_complaints_per_day[$date]++;
    }
}

// Fetch complaints for selected date
$selected_complaints = [];
if ($selected_date && preg_match('/^\d{4}-\d{2}-\d{2}$/', $selected_date)) {
    $selected_where = "WHERE DATE(c.created_at) = ? AND c.created_at BETWEEN ? AND ?";
    $selected_params = [$selected_date, $from_date . ' 00:00:00', $to_date . ' 23:59:59'];
    if (!empty($service_person_ids)) {
        $placeholders = implode(',', array_fill(0, count($service_person_ids), '?'));
        $selected_where .= " AND c.service_person_id IN ($placeholders)";
        $selected_params = array_merge($selected_params, $service_person_ids);
    }
    $selected_stmt = $pdo->prepare("
        SELECT c.id, c.customer_id, c.product_name, c.status, c.created_at, c.closed_at, c.description, c.closing_remark,
               cu.name as customer_name, sp.name as service_person_name,
               TIMESTAMPDIFF(HOUR, c.created_at, c.closed_at) as resolution_time_hours
        FROM complaints c
        LEFT JOIN customers cu ON c.customer_id = cu.id
        LEFT JOIN service_persons sp ON c.service_person_id = sp.id
        $selected_where
        ORDER BY c.created_at DESC
    ");
    $selected_stmt->execute($selected_params);
    $selected_complaints = $selected_stmt->fetchAll(PDO::FETCH_ASSOC);
}

// Summary metrics
$summary = [
    'total_complaints' => count($complaints),
    'total_closed_complaints' => 0,
    'avg_resolution_time_hours' => 0,
    'unique_service_persons' => 0,
    'busiest_day' => ['date' => 'N/A', 'count' => 0]
];
$service_person_counts = [];
$busiest_day_count = 0;
$busiest_day = 'N/A';
$resolution_times = [];
foreach ($complaints as $complaint) {
    $sp_id = $complaint['service_person_id'] ?? 'N/A';
    $date = $complaint['complaint_date'];
    $service_person_counts[$sp_id] = ($service_person_counts[$sp_id] ?? 0) + 1;
    if ($complaint['status'] === 'Closed') {
        $summary['total_closed_complaints']++;
        if ($complaint['resolution_time_hours'] !== null) {
            $resolution_times[] = $complaint['resolution_time_hours'];
        }
    }
    $day_count = count($calendar_data[$date]);
    if ($day_count > $busiest_day_count) {
        $busiest_day_count = $day_count;
        $busiest_day = $date;
    }
}
$summary['unique_service_persons'] = count($service_person_counts);
$summary['busiest_day'] = ['date' => $busiest_day, 'count' => $busiest_day_count];
$summary['avg_resolution_time_hours'] = $resolution_times ? round(array_sum($resolution_times) / count($resolution_times), 2) : 0;

// Fetch complaints by service person for chart
$chart_stmt = $pdo->prepare("
    SELECT sp.name as service_person_name, COUNT(*) as complaint_count
    FROM complaints c
    LEFT JOIN service_persons sp ON c.service_person_id = sp.id
    $where
    GROUP BY c.service_person_id
    ORDER BY complaint_count DESC
    LIMIT 5
");
$chart_stmt->execute($params);
$chart_data = $chart_stmt->fetchAll(PDO::FETCH_ASSOC);
?>

<?php include __DIR__ . '/../includes/header.php'; ?>
<!-- Add DataTables and Chart.js CSS/JS -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>

<style>
.calendar-table td { text-align: center; padding: 10px; }
.calendar-table td.has-complaints { background-color: #e3f2fd; cursor: pointer; }
.calendar-table td.has-complaints:hover { background-color: #bbdefb; }
.calendar-table td.has-closed-complaints { background-color: #d4edda; }
.calendar-table td.has-closed-complaints:hover { background-color: #c3e6cb; }
</style>

<div class="container mt-4">
    <h2>Schedule Management</h2>

    <!-- Filter Form -->
    <form method="POST" class="mb-4">
        <div class="row">
            <div class="col-md-3">
                <label for="from_date">From Date</label>
                <input type="date" class="form-control" name="from_date" value="<?= htmlspecialchars($from_date) ?>">
            </div>
            <div class="col-md-3">
                <label for="to_date">To Date</label>
                <input type="date" class="form-control" name="to_date" value="<?= htmlspecialchars($to_date) ?>">
            </div>
            <div class="col-md-4">
                <label for="service_person_ids">Service Persons</label>
                <select name="service_person_ids[]" class="form-control" multiple>
                    <?php foreach ($service_persons as $sp): ?>
                        <option value="<?= $sp['id'] ?>" <?= in_array($sp['id'], $service_person_ids) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($sp['name']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="col-md-2 align-self-end">
                <button type="submit" class="btn btn-info btn-ui text-light"><i class="material-icons">filter_list</i> Filter</button>
            </div>
        </div>
    </form>

    <!-- Summary Metrics -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="card-title mb-0">Schedule Summary</h5>
        </div>
        <div class="card-body">
            <p><strong>Total Assigned Complaints:</strong> <?= $summary['total_complaints'] ?></p>
            <p><strong>Total Closed Complaints:</strong> <?= $summary['total_closed_complaints'] ?></p>
            <p><strong>Average Resolution Time (Hours):</strong> <?= $summary['avg_resolution_time_hours'] ?></p>
            <p><strong>Unique Service Persons:</strong> <?= $summary['unique_service_persons'] ?></p>
            <p><strong>Busiest Day:</strong> <?= htmlspecialchars($summary['busiest_day']['date']) ?> (<?= $summary['busiest_day']['count'] ?> complaints)</p>
        </div>
    </div>

    <!-- Calendar View -->
    <h3>Calendar View</h3>
    <?php
    $start_date = new DateTime($from_date);
    $end_date = new DateTime($to_date);
    $month = $start_date->format('Y-m');
    $days_in_month = $start_date->format('t');
    $first_day_of_month = new DateTime("$month-01");
    $first_day_weekday = (int)$first_day_of_month->format('w'); // 0 (Sun) to 6 (Sat)
    ?>
    <table class="table table-bordered calendar-table">
        <thead>
            <tr>
                <th>Sun</th><th>Mon</th><th>Tue</th><th>Wed</th><th>Thu</th><th>Fri</th><th>Sat</th>
            </tr>
        </thead>
        <tbody>
            <?php
            $day = 1;
            $current_date = clone $first_day_of_month;
            $week = [];
            for ($i = 0; $i < $first_day_weekday; $i++) {
                $week[] = ''; // Empty cells before first day
            }
            while ($day <= $days_in_month) {
                $week[] = $day;
                if (count($week) == 7 || $day == $days_in_month) {
                    echo '<tr>';
                    foreach ($week as $d) {
                        if ($d === '') {
                            echo '<td></td>';
                        } else {
                            $date_str = sprintf("%s-%02d", $month, $d);
                            $has_complaints = isset($calendar_data[$date_str]) ? 'has-complaints' : '';
                            $has_closed = isset($closed_complaints_per_day[$date_str]) && $closed_complaints_per_day[$date_str] > 0 ? 'has-closed-complaints' : '';
                            $complaint_count = isset($calendar_data[$date_str]) ? count($calendar_data[$date_str]) : 0;
                            $closed_count = $closed_complaints_per_day[$date_str] ?? 0;
                            echo "<td class='$has_complaints $has_closed' onclick=\"window.location='?selected_date=$date_str&from_date=" . htmlspecialchars($from_date) . "&to_date=" . htmlspecialchars($to_date) . "'\">$d<br><small>$complaint_count tasks ($closed_count closed)</small></td>";
                        }
                    }
                    echo '</tr>';
                    $week = [];
                    $day++;
                } else {
                    $day++;
                }
            }
            ?>
        </tbody>
    </table>

    <!-- Selected Date Complaints -->
    <?php if ($selected_date && $selected_complaints): ?>
        <h3 class="mt-4">Complaints for <?= htmlspecialchars($selected_date) ?></h3>
        <table id="complaintsTable" class="table table-datatable table-striped table-hover">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Service Person</th>
                    <th>Customer</th>
                    <th>Product</th>
                    <th>Status</th>
                    <th>Created At</th>
                    <th>Closed At</th>
                    <th>Description</th>
                    <th>Closing Remark</th>
                    <th>Resolution Time (Hours)</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($selected_complaints as $complaint): ?>
                    <tr>
                        <td><?= htmlspecialchars($complaint['id']) ?></td>
                        <td><?= htmlspecialchars($complaint['service_person_name'] ?? 'N/A') ?></td>
                        <td><?= htmlspecialchars($complaint['customer_name'] ?? 'N/A') ?></td>
                        <td><?= htmlspecialchars($complaint['product_name'] ?? 'N/A') ?></td>
                        <td>
                            <span class="badge bg-<?= $complaint['status'] === 'Closed' ? 'success' : ($complaint['status'] === 'New' ? 'warning' : 'info') ?>">
                                <?= htmlspecialchars($complaint['status']) ?>
                            </span>
                        </td>
                        <td><?= htmlspecialchars($complaint['created_at']) ?></td>
                        <td><?= htmlspecialchars($complaint['closed_at'] ?? 'N/A') ?></td>
                        <td><?= htmlspecialchars($complaint['description'] ?? 'N/A') ?></td>
                        <td><?= htmlspecialchars($complaint['closing_remark'] ?? 'N/A') ?></td>
                        <td><?= htmlspecialchars($complaint['resolution_time_hours'] ?? 'N/A') ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php elseif ($selected_date): ?>
        <p class="alert alert-info">No complaints found for <?= htmlspecialchars($selected_date) ?>.</p>
    <?php endif; ?>

    <!-- Chart -->
    <div class="row mt-5">
        <div class="col-md-6">
            <h5>Complaints by Service Person</h5>
            <canvas id="servicePersonChart" height="200"></canvas>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {

    // Initialize Chart.js for service person chart
    var ctx = document.getElementById('servicePersonChart').getContext('2d');
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: [<?php foreach ($chart_data as $row) { echo "'" . addslashes($row['service_person_name'] ?? 'N/A') . "',"; } ?>],
            datasets: [{
                label: 'Complaints by Service Person',
                data: [<?php foreach ($chart_data as $row) { echo $row['complaint_count'] . ','; } ?>],
                backgroundColor: '#36A2EB',
                borderColor: '#36A2EB',
                borderWidth: 1
            }]
        },
        options: {
            scales: {
                y: {
                    beginAtZero: true,
                    title: { display: true, text: 'Complaint Count' }
                },
                x: {
                    title: { display: true, text: 'Service Person' }
                }
            },
            plugins: {
                legend: { display: false }
            }
        }
    });
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>