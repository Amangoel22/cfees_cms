<?php
session_start();

if (!isset($_SESSION['user_name'])) {
    header("Location: ../../login/login_engineer.html");
    exit();
}

$engineer_username = $_SESSION['user_name'];
$engineer_name = $_SESSION['engineer_name'];

$conn = new mysqli("localhost", "root", "", "cfees_cms");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$filter_type = $_GET['type'] ?? '';
$search_term = $_GET['search'] ?? '';
$date_from = $_GET['from'] ?? '';
$date_to = $_GET['to'] ?? '';
$sort = $_GET['sort'] ?? 'newest';

$sql = "SELECT c.*, e.first_name AS emp_first_name, e.last_name AS emp_last_name, e.intercom 
        FROM complaints c 
        JOIN employees e ON c.employee_user_name = e.user_name 
        WHERE c.assigned_engineer_username = ? AND c.status IN ('Review Pending', 'Resolved')";

$params = [$engineer_username];
$types = "s";

if (!empty($filter_type)) {
    $sql .= " AND c.type = ?";
    $params[] = $filter_type;
    $types .= "s";
}

if (!empty($search_term)) {
    $sql .= " AND (c.title LIKE ? OR c.description LIKE ? OR c.location LIKE ?)";
    $params[] = "%$search_term%";
    $params[] = "%$search_term%";
    $params[] = "%$search_term%";
    $types .= "sss";
}

if (!empty($date_from)) {
    $sql .= " AND DATE(c.created_at) >= ?";
    $params[] = $date_from;
    $types .= "s";
}
if (!empty($date_to)) {
    $sql .= " AND DATE(c.created_at) <= ?";
    $params[] = $date_to;
    $types .= "s";
}

$order = $sort === 'oldest' ? 'ASC' : 'DESC';
$sql .= " ORDER BY c.created_at $order";

$stmt = $conn->prepare($sql);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();
$complaints = $result->fetch_all(MYSQLI_ASSOC);
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Complaint Records - DRDO CFEES</title>
  <link rel="stylesheet" href="../../css/engineer/records.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;600;700&display=swap" rel="stylesheet">
</head>
<body>
  <!-- Header -->
  <header class="main-header">
    <div class="header-inner">
      <div class="logo-box left">
        <img src="../../logos/logo-left.png" alt="Left Logo">
      </div>
      <div class="header-center">
        <h1 class="hindi-bold">अग्नि, पर्यावरण तथा विस्फोटक सुरक्षा केंद्र (CFEES)</h1>
        <p class="hindi-regular">रक्षा मन्त्रालय, भारत सरकार</p>
        <h2 class="eng-bold">Centre for Fire, Explosive and Environment Safety (CFEES)</h2>
        <p class="eng-regular">Ministry of Defence, Government of India</p>
      </div>
      <div class="logo-box right">
        <img src="../../logos/logo-right.png" alt="Right Logo">
      </div>
    </div>
  </header>

  <!-- Dashboard Layout -->
  <div class="dashboard">
     <main class="main-content">
      <main class="main-content">
      <a href="dashboard.php" class="back-top-btn">
  <i class="fa fa-arrow-left"></i> Back to Dashboard
</a>
      <div class="complaint-records">
        <h2>Complaint Records</h2>
        <form method="GET" class="filters">
          <label for="sort">Sort by:</label>
          <select name="sort" id="sort">
            <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Newest to Oldest</option>
            <option value="oldest" <?= $sort === 'oldest' ? 'selected' : '' ?>>Oldest to Newest</option>
          </select>

          <label for="from">From:</label>
          <input type="date" name="from" value="<?= htmlspecialchars($date_from) ?>">

          <label for="to">To:</label>
          <input type="date" name="to" value="<?= htmlspecialchars($date_to) ?>">

          <input type="text" name="search" placeholder="Search..." value="<?= htmlspecialchars($search_term) ?>">
          <button type="submit">Apply Filters</button>
        </form>

        <table>
          <thead>
            <tr>
              <th>Complaint ID</th>
              <th>Title</th>
              <th>Type</th>
              <th>Location</th>
              <th>Employee</th>
              <th>Registered</th>
              <th>Intercom</th>
              <th>Status</th>
              <th>Action</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($complaints as $c): ?>
              <tr>
                <td><?= $c['complaint_id'] ?></td>
                <td><?= htmlspecialchars($c['title']) ?></td>
                <td><?= htmlspecialchars($c['type']) ?></td>
                <td><?= htmlspecialchars($c['location']) ?></td>
                <td><?= htmlspecialchars(($c['emp_first_name'] ?? '') . ' ' . ($c['emp_last_name'] ?? '')) ?></td>
                <td><?= $c['created_at'] ?></td>
                <td><?= htmlspecialchars($c['intercom'] ?? '-') ?></td>
              <td><span class="status <?= strtolower(str_replace(' ', '', $c['status'])) ?>"><?= htmlspecialchars($c['status']) ?></span></td>
                <td><button class="view-btn" onclick='showDetails(<?= json_encode($c) ?>)'>View</button></td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    </main>
  </div>

  <div class="modal" id="detailsModal">
    <div class="modal-content">
      <span class="close-btn" onclick="document.getElementById('detailsModal').style.display='none'">&times;</span>
      <div id="modalContent"></div>
    </div>
  </div>

  <footer class="main-footer">
    <p>&copy; 2025, DRDO, Ministry of Defence, Government of India</p>
  </footer>

  <script>
    function showDetails(data) {
      let html = `
        <h3>Complaint Details</h3>
        <p><strong>ID:</strong> ${data.complaint_id}</p>
        <p><strong>Title:</strong> ${data.title}</p>
        <p><strong>Description:</strong> ${data.description}</p>
        <p><strong>Location:</strong> ${data.location}</p>
        <p><strong>Type:</strong> ${data.type}</p>
        <p><strong>Intercom:</strong> ${data.intercom ?? '-'}</p>
        <p><strong>Status:</strong> ${data.status}</p>
        <p><strong>Employee:</strong> ${(data.emp_first_name ?? '') + ' ' + (data.emp_last_name ?? '')}</p>
        <p><strong>Registered At:</strong> ${data.created_at}</p>
        <p><strong>Engineer Feedback:</strong> ${data.solution ?? '-'}</p>
      `;
      document.getElementById('modalContent').innerHTML = html;
      document.getElementById('detailsModal').style.display = 'flex';
    }
  </script>
</body>
</html>
