<?php
session_start();
if (!isset($_SESSION['user_id'])) {
  header("Location: ../../login/login_employee.html");
  exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['complaint_id'])) {
  $cid = intval($_POST['complaint_id']);

  $conn = new mysqli("localhost", "root", "", "cfees_cms");
  if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
  }

  $stmt = $conn->prepare("DELETE FROM complaints WHERE complaint_id = ?");
  $stmt->bind_param("i", $cid);
  if ($stmt->execute()) {

    header("Location: employee.php");
    exit();
  } else {
    echo "Failed to delete complaint.";
  }

  $stmt->close();
  $conn->close();
} else {
  echo "Invalid request.";
}
?>
