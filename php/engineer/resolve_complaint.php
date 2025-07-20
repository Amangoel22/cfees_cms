<?php
session_start();
if (!isset($_SESSION['user_name'])) {
    echo 'unauthorized';
    exit();
}

$conn = new mysqli("localhost", "root", "", "cfees_cms");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}


$complaint_id = isset($_POST['complaint_id']) ? intval($_POST['complaint_id']) : 0;
$resolution = trim($_POST['resolution'] ?? '');
$message = trim($_POST['message'] ?? '');

if ($complaint_id <= 0 || ($resolution !== 'resolved' && $resolution !== 'not_resolved')) {
    echo 'invalid';
    exit();
}

$engineer_status = ($resolution === 'resolved') ? 'Resolved' : 'Not Resolved';
$status = 'Review Pending';

$sql = "UPDATE complaints 
        SET engineer_status = ?, solution = ?, status = ?, resolution_time = NOW()
        WHERE complaint_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("sssi", $engineer_status, $message, $status, $complaint_id);

echo $stmt->execute() ? "success" : "error";
?>
