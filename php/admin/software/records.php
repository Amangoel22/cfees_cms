 <?php
  session_start();
  if (!isset($_SESSION['username']) || $_SESSION['admin_role'] !== 'Software') {
    header("Location: ../../../login/login_admin.html");
    exit();
  }
  
  $conn = new mysqli("localhost", "root", "", "cfees_cms");
  if ($conn->connect_error) die("Connection failed");
$filters = [
  'sort' => $_GET['sort'] ?? 'desc',
  'from' => $_GET['from'] ?? '',
  'to' => $_GET['to'] ?? '',
  'search' => $_GET['search'] ?? ''
];

$query = "
  SELECT c.*, CONCAT(e.first_name, ' ', e.last_name) AS employee_name,
         c.solution, f.rating, f.reason,
         CONCAT(eng.first_name, ' ', eng.last_name) AS engineer_name
  FROM complaints c
  JOIN employees e ON c.employee_user_name = e.user_name
  LEFT JOIN engineer eng ON eng.user_name = c.assigned_engineer_username
  LEFT JOIN feedback f ON c.complaint_id = f.complaint_id
  WHERE c.type = 'Software'
";

if (!empty($filters['from']) && !empty($filters['to'])) {
  $query .= " AND DATE(c.created_at) BETWEEN '" . $conn->real_escape_string($filters['from']) . "' AND '" . $conn->real_escape_string($filters['to']) . "'";
}

if (!empty($filters['search'])) {
  $search = $conn->real_escape_string($filters['search']);
  $query .= " AND (
      c.title LIKE '%$search%' OR
      c.description LIKE '%$search%' OR
      CONCAT(e.first_name, ' ', e.last_name) LIKE '%$search%' OR
      c.location LIKE '%$search%'
  )";
}

$query .= " ORDER BY c.created_at " . ($filters['sort'] === 'asc' ? 'ASC' : 'DESC');

$result = $conn->query($query);


?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Complaint Records - DRDO CFEES</title>
  <link rel="stylesheet" href="../../../css/admin/records.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;600;700&display=swap" rel="stylesheet">
</head>
<body>

  <header class="main-header">
    <div class="header-inner">
      <div class="logo-box left">
        <img src="../../../logos/logo-left.png" alt="Left Logo">
      </div>
      <div class="header-center">
        <h1 class="hindi-bold">अग्नि, पर्यावरण तथा विस्फोटक सुरक्षा केंद्र (CFEES)</h1>
        <p class="hindi-regular">रक्षा मन्त्रालय, भारत सरकार</p>
        <h2 class="eng-bold">Centre for Fire, Explosive and Environment Safety (CFEES)</h2>
        <p class="eng-regular">Ministry of Defence, Government of India</p>
      </div>
      <div class="logo-box right">
        <img src="../../../logos/logo-right.png" alt="Right Logo">
      </div>
    </div>
  </header>


  <div class="dashboard">
    <main class="main-content">
         <a href="software_dashboard.php" class="back-top-btn">
  <i class="fa fa-arrow-left"></i> Back to Dashboard
</a>
      <section class="complaint-records">
        <section class="filters">
        <form method="get">
          <label>Sort by:
            <select name="sort">
              <option value="desc" <?= $filters['sort'] === 'desc' ? 'selected' : '' ?>>Newest First</option>
              <option value="asc" <?= $filters['sort'] === 'asc' ? 'selected' : '' ?>>Oldest First</option>
            </select>
          </label>
          <label>From: <input type="date" name="from" value="<?= htmlspecialchars($filters['from']) ?>"></label>
          <label>To: <input type="date" name="to" value="<?= htmlspecialchars($filters['to']) ?>"></label>
          <label>By Title: <input type="text" name="search" placeholder="Search..." value="<?= htmlspecialchars($filters['search']) ?>"></label>
          <button type="submit">Apply Filters</button>
        </form>
      </section>
        <h2>All Complaints</h2>

        <table>
          <thead>
            <tr>
              <th>Complaint ID</th>
              <th>Title</th>
              <th>Date</th>
              <th>Status</th>
              <th>Type</th>
              <th>Location</th>
              <th>Resolved Time</th>
              <th>Details</th>
            </tr>
          </thead>
          <tbody>
            <?php if ($result && $result->num_rows > 0): ?>
              <?php while ($row = $result->fetch_assoc()): ?>
                <tr>
                  <td>CMP<?= $row['complaint_id'] ?></td>
                  <td><?= htmlspecialchars($row['title']) ?></td>
                  <td><?= date('Y-m-d H:i A', strtotime($row['created_at'])) ?></td>
                  <td><span class="status <?= strtolower(str_replace(' ', '', $row['status'])) ?>"><?= htmlspecialchars($row['status']) ?></span></td>
                  <td><?= htmlspecialchars($row['type']) ?></td>
                  <td><?= htmlspecialchars($row['location']) ?></td>
                  <td><?= $row['resolution_time'] ? date('Y-m-d H:i A', strtotime($row['resolution_time'])) : '—' ?></td>
                  <td>
                    <button class="view-btn" onclick='viewDetails(<?= json_encode($row) ?>)'>View</button>
                  </td>
                </tr>
              <?php endwhile; ?>
            <?php else: ?>
              <tr><td colspan="8">No complaints found.</td></tr>
            <?php endif; ?>
          </tbody>
        </table>
      </section>
    </main>
  </div>

  <div class="modal" id="viewModal" style="display:none">
    <div class="modal-content">
      <span class="close-btn" onclick="document.getElementById('viewModal').style.display='none'">&times;</span>
      <h2>Complaint Details</h2>
      <div id="modalContent"></div>
    </div>
  </div>

  <footer class="main-footer">
    <p>Copyright © 2025, DRDO, Ministry of Defence, Government of India</p>
  </footer>

  <script>
 function viewDetails(data) {
  let html = `<p><strong>ID:</strong> CMP${data.complaint_id}</p>` +
             `<p><strong>Title:</strong> ${data.title}</p>` +
             `<p><strong>Description:</strong> ${data.description}</p>` +
             `<p><strong>Location:</strong> ${data.location}</p>` +
             `<p><strong>Status:</strong> ${data.status}</p>`;

  if (data.status === 'Resolved') {
    html += `<p><strong>Assigned Engineer:</strong> ${data.engineer_name || 'N/A'}</p>` +
            `<p><strong>Engineer Feedback:</strong> ${data.solution || 'No feedback provided'}</p>` +
            `<p><strong>Employee Rating:</strong> ${data.rating || 'N/A'}</p>`;

    if (data.reason) {
      html += `<p><strong>Reason:</strong> ${data.reason}</p>`;
    }
  }

  document.getElementById('modalContent').innerHTML = html;
  document.getElementById('viewModal').style.display = 'block';
}

  </script>
</body>
</html>
