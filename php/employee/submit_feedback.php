<?php
session_start();

$conn = new mysqli("localhost", "root", "", "cfees_cms");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $complaint_id = intval($_POST['complaint_id'] ?? 0);
    $rating = intval($_POST['rating'] ?? 0);
    $reason = trim($_POST['reason'] ?? '');

    if ($complaint_id > 0 && $rating >= 1 && $rating <= 5 && $reason !== '') {
   
        $check = $conn->prepare("SELECT complaint_id FROM feedback WHERE complaint_id = ?");
        $check->bind_param("i", $complaint_id);
        $check->execute();
        $check->store_result();

        if ($check->num_rows > 0) {
            echo "duplicate";
        } else {
            $stmt = $conn->prepare("INSERT INTO feedback (complaint_id, rating, reason) VALUES (?, ?, ?)");
            $stmt->bind_param("iis", $complaint_id, $rating, $reason);
            if ($stmt->execute()) {
                echo "success";
            } else {
                echo "fail";
            }
            $stmt->close();
        }
        $check->close();
    } else {
        echo "invalid";
    }
}
?>
