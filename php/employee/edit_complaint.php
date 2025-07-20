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

  $stmt = $conn->prepare("SELECT title, description, type FROM complaints WHERE complaint_id = ?");
  $stmt->bind_param("i", $cid);
  $stmt->execute();
  $stmt->bind_result($title, $desc, $type);
  if ($stmt->fetch()) {

    echo "<script>
      sessionStorage.setItem('editMode', 'true');
      sessionStorage.setItem('editComplaintId', '$cid');
      sessionStorage.setItem('editTitle', '".addslashes($title)."');
      sessionStorage.setItem('editDesc', '".addslashes($desc)."');
      sessionStorage.setItem('editType', '$type');
      window.location.href = 'employee.php';
    </script>";
  } else {
    echo "Complaint not found.";
  }

  $stmt->close();
  $conn->close();
} else {
  echo "Invalid request.";
}
?>
