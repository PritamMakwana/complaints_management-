<?php
include __DIR__ . '/../config/db.php';
session_start();
// Restrict access to admin and coordinator roles
// if (!isset($_SESSION['role']) || !in_array($_SESSION['role'], ['admin', 'coordinator'])) {
//     header('Location: login.php');
//     exit;
// }
// ?>

<?php include __DIR__ . '/../includes/header.php'; ?>
<!-- Add Bootstrap CSS and Material Icons -->

<div class="container mt-4">
    <h2>Dashboard</h2>
    <p>Welcome, Select a report to view details.</p>

    <div class="row g-4">
        <!-- Complaint Trends Card -->
        <div class="col-md-6 col-lg-3">
            <div class="card h-100 shadow-sm" style="cursor: pointer;" onclick="window.location='complaint_trends.php'">
                <div class="card-body text-center">
                    <i class="material-icons" style="font-size: 48px; color: #36A2EB;">trending_up</i>
                    <h5 class="card-title mt-3">Complaint Trends</h5>
                    <p class="card-text">Analyze complaints by city, state, and product to identify common issues.</p>
                </div>
            </div>
        </div>

        <!-- Customer History Card -->
        <div class="col-md-6 col-lg-3">
            <div class="card h-100 shadow-sm" style="cursor: pointer;" onclick="window.location='customer_history.php'">
                <div class="card-body text-center">
                    <i class="material-icons" style="font-size: 48px; color: #FFCE56;">history</i>
                    <h5 class="card-title mt-3">Customer History</h5>
                    <p class="card-text">View detailed complaint history for individual customers.</p>
                </div>
            </div>
        </div>

        <!-- Schedule Management Card -->
        <div class="col-md-6 col-lg-3">
            <div class="card h-100 shadow-sm" style="cursor: pointer;" onclick="window.location='schedule.php'">
                <div class="card-body text-center">
                    <i class="material-icons" style="font-size: 48px; color: #FF6384;">calendar_today</i>
                    <h5 class="card-title mt-3">Schedule Management</h5>
                    <p class="card-text">View assigned tasks for service persons in a calendar view.</p>
                </div>
            </div>
        </div>

        <!-- Service Person Performance Card -->
        <div class="col-md-6 col-lg-3">
            <div class="card h-100 shadow-sm" style="cursor: pointer;" onclick="window.location='service_person_performance.php'">
                <div class="card-body text-center">
                    <i class="material-icons" style="font-size: 48px; color: #4BC0C0;">person</i>
                    <h5 class="card-title mt-3">Service Person Performance</h5>
                    <p class="card-text">Evaluate performance metrics for service persons.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>