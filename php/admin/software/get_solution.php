<?php
$conn = new mysqli("localhost", "root", "", "cfees_cms");
if ($conn->connect_error) die("DB Error");

$id = intval($_POST['complaint_id']);
$sql = "SELECT solution FROM complaints WHERE complaint_id = $id LIMIT 1";
$result = $conn->query($sql);
if ($result && $row = $result->fetch_assoc()) {
    echo json_encode(['solution' => $row['solution']]);
} else {
    echo json_encode(['solution' => '']);
}
?>
