<?php
// schedule.php - Additional Features: Schedule Management
// Simple calendar view, using Bootstrap for layout. For real calendar, could use JS library, but keep simple as table.
include '/config/db.php';
session_start();
if ($_SESSION['role'] !== 'coordinator') {
    header('Location: login.php');
    exit;
}

// Fetch assignments per service person
$stmt = $pdo->query("SELECT sp.name, COUNT(c.id) as assigned 
                     FROM service_persons sp 
                     LEFT JOIN complaints c ON sp.id = c.service_person_id AND c.status = 'Assigned to Service Person' 
                     GROUP BY sp.id");
$schedules = $stmt->fetchAll();
?>

<?php include '/includes/header.php'; ?>
<h2>Schedule Management</h2>
<table class="table">
    <thead><tr><th>Service Person</th><th>Assigned Complaints</th></tr></thead>
    <tbody>
        <?php foreach ($schedules as $sched): ?>
            <tr><td><?= $sched['name'] ?></td><td><?= $sched['assigned'] ?></td></tr>
        <?php endforeach; ?>
    </tbody>
</table>
<!-- Could add more advanced calendar with JS if needed -->
<?php include '/includes/footer.php'; ?>