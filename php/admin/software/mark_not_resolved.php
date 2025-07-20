<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn = new mysqli("localhost", "root", "", "cfees_cms");
    if ($conn->connect_error) die("DB Error");

    $id = intval($_POST['complaint_id']);
    $sql = "UPDATE complaints SET status = 'Pending', assigned_engineer_username = NULL, solution = NULL WHERE complaint_id = $id";
    if ($conn->query($sql)) {
        echo "success";
    } else {
        echo "fail";
    }
}
?>
