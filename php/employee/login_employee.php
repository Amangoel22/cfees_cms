<?php
session_start();


$conn = new mysqli("localhost", "root", "", "cfees_cms");


if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}


$username = $_POST['username'];
$password = $_POST['password'];


$sql = "SELECT * FROM employees WHERE user_name = '$username' AND password = '$password' LIMIT 1";
$result = $conn->query($sql);


if ($result && $result->num_rows == 1) {
    $row = $result->fetch_assoc();


    $_SESSION['user_id'] = $row['id'];
    $_SESSION['user_name'] = $row['user_name'];
    $_SESSION['role'] = $row['role'];
    $_SESSION['full_name'] = trim($row['first_name'] . ' ' . $row['middle_name'] . ' ' . $row['last_name']);

 
    $_SESSION['desig_id'] = $row['desig_id'];         
    $_SESSION['intercom'] = $row['intercom']; 


    header("Location: dashboard.php");
    exit();
} else {
    echo "<script>alert('Invalid username or password!'); window.history.back();</script>";
}
?>
