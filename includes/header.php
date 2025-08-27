<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Complaint Management Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- DataTables CSS -->
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.7/css/dataTables.bootstrap5.min.css">
    <link href="https://fonts.googleapis.com/icon?family=Material+Icons" rel="stylesheet">
    <link href="../assets/styles.css" rel="stylesheet">
</head>

<body>

    <!-- loading page -->
    <div id="LoadSpinnerAll" class="custom-loader-overlay">
        <span class="loader"></span>
    </div>
    <!-- loading page page -->

    <nav class="navbar navbar-expand-lg navbar-light bg-light">
        <div class="container-fluid">
            <a class="navbar-brand" href="#">Complaint Portal</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav">
                    <li>
                        <a class="nav-link" href="../receptionist/complaint_entry.php">New Complaint</a>
                    </li>
                    <li>
                        <a class="nav-link" href="../coordinator/dashboard.php">Coordination Dashboard</a>
                    </li>
                    <li>
                        <a class="nav-link" href="../spare_parts/dashboard.php">Spare Parts</a>
                    </li>
                    <li>
                        <a class="nav-link" href="../service_person/dashboard.php">Service Person</a>
                    </li>
                    <li>
                        <a class="nav-link" href="../report/dashboard.php">Report</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    <div class="container mt-4">