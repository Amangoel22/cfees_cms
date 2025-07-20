<?php
session_start();

if (!isset($_SESSION['user_name'])) {
    echo "unauthorized";
    exit();
}

if (!isset($_POST['complaint_id'])) {
    echo "invalid";
    exit();
}

$conn = new mysqli("localhost", "root", "", "cfees_cms");
if ($conn->connect_error) {
    echo "dberror";
    exit();
}

$complaint_id = $conn->real_escape_string($_POST['complaint_id']);


$sql = "UPDATE complaints 
        SET status = 'Active', engineer_status = 'Active' 
        WHERE complaint_id = '$complaint_id'";

if ($conn->query($sql)) {
    echo "success";
} else {
    echo "fail";
}

$conn->close();
?>
