<?php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $conn = new mysqli("localhost", "root", "", "cfees_cms");
    if ($conn->connect_error) die("DB Error");

    $id = intval($_POST['complaint_id']);
    $engineer = $conn->real_escape_string($_POST['engineer']);

  
    $query = "
        UPDATE complaints 
        SET assigned_engineer_username = '$engineer', 
            status = 'Active', 
            engineer_status = 'Pending', 
            assignment_time = NOW() 
        WHERE complaint_id = $id
    ";

    echo $conn->query($query) ? "success" : "fail";
}
?>
