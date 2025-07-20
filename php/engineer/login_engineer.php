<?php
session_start();


$conn = new mysqli("localhost", "root", "", "cfees_cms");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}


$username = isset($_POST['username']) ? trim($_POST['username']) : '';
$password = isset($_POST['password']) ? trim($_POST['password']) : '';

if ($username === '' || $password === '') {
    echo "<script>alert('Please enter both username and password.'); window.location.href='../../login/login_engineer.html';</script>";
    exit();
}


$sql = "SELECT * FROM engineer WHERE user_name = '$username' AND password = '$password' LIMIT 1";
$result = $conn->query($sql);

if ($result && $result->num_rows === 1) {
    $row = $result->fetch_assoc();
    $_SESSION['user_name'] = $row['user_name'];
    $_SESSION['engineer_name'] = trim($row['first_name'] . ' ' . $row['last_name']);

    header("Location: dashboard.php");
    exit();
} else {
    echo "<script>alert('Invalid credentials. Please try again.'); window.location.href='../../login/login_engineer.html';</script>";
    exit();
}
?>
