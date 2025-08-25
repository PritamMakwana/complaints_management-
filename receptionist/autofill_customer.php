<?php
// autofill_customer.php - Helper for autofill
include __DIR__ . '/../config/db.php';
$mobile = $_GET['mobile'] ?? '';
if ($mobile) {
    $stmt = $pdo->prepare("SELECT name, city, state FROM customers WHERE mobile_number = ?");
    $stmt->execute([$mobile]);
    $customer = $stmt->fetch(PDO::FETCH_ASSOC);
    echo json_encode($customer ?: []);
}
?>