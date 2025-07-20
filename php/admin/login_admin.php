<?php
session_start();

$conn = new mysqli("localhost", "root", "", "cfees_cms");


if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}


$username = $_POST['username'];
$password = $_POST['password'];


$sql = "SELECT * FROM admin WHERE user_name = '$username' AND password = '$password' LIMIT 1";
$result = $conn->query($sql);


if ($result && $result->num_rows === 1) {
    $row = $result->fetch_assoc();


    $_SESSION['admin_id'] = $row['id'];
    $_SESSION['admin_name'] = trim($row['first_name'] . ' ' . $row['middle_name'] . ' ' . $row['last_name']);
    $_SESSION['admin_role'] = $row['role'];

    $_SESSION['intercom'] = $row['intercom'];
    $_SESSION['username'] = $row['user_name'];


switch (trim($row['role'])) {
    case 'Super Admin':
        header("Location: superadmin/superadmin_dashboard.php");
        break;
    case 'IT Hardware':
        header("Location: IThardware/IThardware_dashboard.php");
        break;
    case 'Network':
        header("Location: network/network_dashboard.php");
        break;
    case 'Software':
        header("Location: software/software_dashboard.php");
    
        break;
    default:
        echo "<script>alert('Unknown admin role: " . addslashes($row['role']) . "'); window.history.back();</script>";
        exit();
}
} else {
    echo "<script>alert('Invalid username or password!'); window.history.back();</script>";
    exit();
}
?>
