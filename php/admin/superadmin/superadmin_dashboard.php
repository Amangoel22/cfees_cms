<?php
session_start();
$conn = new mysqli("localhost", "root", "", "cfees_cms");

if (!isset($_SESSION['username'])) {
    header("Location: ../../../login/login_admin.html");
    exit();
}

$user_name = $_SESSION['username'];
$user_result = $conn->query("SELECT first_name, middle_name, last_name FROM admin WHERE user_name = '$user_name' LIMIT 1");

if ($user_result && $user_result->num_rows > 0) {
    $user_data = $user_result->fetch_assoc();
    $fullName = trim($user_data['first_name'] . ' ' . $user_data['middle_name'] . ' ' . $user_data['last_name']);
} else {
    $fullName = "Super Admin";
}

$sort = $_GET['sort'] ?? 'newest';
$type = $_GET['type'] ?? 'all';
$search = $_GET['search'] ?? '';
$from = $_GET['from'] ?? '';
$to = $_GET['to'] ?? '';

$query = "SELECT 
  c.complaint_id,
  c.title,
  c.description,
  c.location,
  c.intercom,
  c.type,
  c.status,
  c.created_at AS registered_time,
  c.resolution_time AS resolved_time,
  c.solution AS engineer_feedback,

  e.first_name AS emp_first_name,
  e.last_name AS emp_last_name,

  eng.first_name AS eng_first_name,
  eng.last_name AS eng_last_name,

  f.rating,
  f.reason,
 c.status AS admin_feedback

FROM complaints c

JOIN employees e ON c.employee_user_name = e.user_name
LEFT JOIN engineer eng ON c.assigned_engineer_username = eng.user_name
LEFT JOIN feedback f ON c.complaint_id = f.complaint_id
WHERE 1 = 1
";


if ($type !== 'all') {
  $query .= " AND c.type = '" . $conn->real_escape_string($type) . "'";
}
if (!empty($from)) {
  $query .= " AND DATE(c.created_at) >= '" . $conn->real_escape_string($from) . "'";
}
if (!empty($to)) {
  $query .= " AND DATE(c.created_at) <= '" . $conn->real_escape_string($to) . "'";
}
if (!empty($search)) {
  $s = $conn->real_escape_string($search);
  $query .= " AND (c.title LIKE '%$s%' OR c.description LIKE '%$s%' OR c.location LIKE '%$s%' OR e.first_name LIKE '%$s%' OR e.last_name LIKE '%$s%')";
}
$query .= ($sort === 'oldest') ? " ORDER BY c.created_at ASC" : " ORDER BY c.created_at DESC";

$result = $conn->query($query);
$complaints = [];
while ($row = $result->fetch_assoc()) {
  $complaints[] = $row;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Admin Dashboard</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <link rel="stylesheet" href="../../../css/index/index.css" />
  <link rel="stylesheet" href="../../../css/admin/superadmin_dashboard.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />

</head>
<body>
  <header class="main-header">
    <div class="header-inner">
      <div class="logo-box left"><img src="../../../logos/logo-left.png" alt="Left Logo">
      </div>
      <div class="header-center">
      <h1 class="hindi-bold">अग्नि, पर्यावरण तथा विस्फोटक सुरक्षा केंद्र (CFEES)</h1>
      <p class="hindi-regular">रक्षा मन्त्रालय, भारत सरकार</p>
      <h2 class="eng-bold">Centre for Fire, Explosive and Environment Safety (CFEES)</h2>
      <p class="eng-regular">Ministry of Defence, Government of India</p>
    </div>
      <div class="logo-box right"><img src="../../../logos/logo-right.png" alt="Right Logo">
    </div>
  </header>

  <div class="page-layout">
    <aside class="sidebar">
      <div class="profile-box">
        <div class="avatar-box">
        <img src="../../../logos/default_user.jpg" alt="Profile Picture" />
        </div>
        <h3><?= htmlspecialchars($fullName); ?></h3>
      </div>
      <nav class="sidebar-nav">
        <ul>
          <li><a href="MyProfile_superadmin.php"><i class="fa-solid fa-user"></i> User Profile</a></li>
          <li><a href="./logout.php"><i class="fa-solid fa-right-from-bracket"></i> Logout</a></li>
        </ul>
      </nav>
    </aside>

    <main class="main-content">
      <h2 class="welcome">Welcome, <?= htmlspecialchars($fullName); ?></h2>
      <h3 class="section-title">Complaints</h3>

      <form class="filter-form" method="GET">
        <div>
          <label for="sort">Sort By:
          <select name="sort" id="sort">
            <option value="newest" <?= $sort === 'newest' ? 'selected' : '' ?>>Newest to Oldest</option>
            <option value="oldest" <?= $sort === 'oldest' ? 'selected' : '' ?>>Oldest to Newest</option>
          </select></label>
        </div>
        <div>
          <label for="type">Type:
          <select name="type" id="type">
            <option value="Software" <?= $type === 'Software' ? 'selected' : '' ?>>Software</option>
            <option value="Network" <?= $type === 'Network' ? 'selected' : '' ?>>Network</option>
            <option value="Hardware" <?= $type === 'Hardware' ? 'selected' : '' ?>>Hardware</option>
          </select></label>
        </div>
        <div>
          <label for="from">From:
          <input type="date" name="from" id="from" value="<?= htmlspecialchars($from); ?>">
        </div></label>
        <div>
          <label for="to">To:
          <input type="date" name="to" id="to" value="<?= htmlspecialchars($to); ?>">
        </div></label>
        <div>
          <label for="search">Search:
          <input type="text" name="search" id="search" value="<?= htmlspecialchars($search); ?>" placeholder="Search complaints...">
        </div></label>
        <div>
          <button type="submit">Apply Filters</button>
        </div>
      </form>

 
      <div class="table-container">
        <table>
          <thead>
            <tr>
              <th>Complaint ID</th>
              <th>Title</th>
              <th>Employee</th>
              <th>Location</th>
              <th>Type</th>
              <th>Status</th>
              <th>Registered</th>
              <th>Resolved</th>
              <th>Rating</th>
              <th>Review</th>
            </tr>
          </thead>
          <tbody>
            <?php
            if (count($complaints) === 0) {
              echo "<tr><td colspan='10'>No complaints found.</td></tr>";
            } else {
              foreach ($complaints as $c) {
                $statusClass = strtolower(str_replace(' ', '-', $c['status']));
               $empName = ($c['emp_first_name'] ?? '') . ' ' . ($c['emp_last_name'] ?? '');

                $resolvedTime = $c['resolved_time'] ?? '-';
                $rating = $c['rating'] ?? '-';

                echo "<tr>
  <td>CMP" . htmlspecialchars($c['complaint_id']) . "</td>
  <td>" . htmlspecialchars($c['title']) . "</td>
  <td>" . htmlspecialchars($c['emp_first_name'] . ' ' . $c['emp_last_name']) . "</td>
  <td>" . htmlspecialchars($c['location']) . "</td>
  <td>" . htmlspecialchars($c['type']) . "</td>
  <td><span class='status $statusClass'>" . htmlspecialchars($c['status']) . "</span></td>
  <td>" . htmlspecialchars($c['registered_time']) . "</td>
  <td>" . htmlspecialchars($resolvedTime) . "</td>
  <td>" . htmlspecialchars($rating) . "</td>
  <td><button class='view-btn' onclick='showDetails(" . json_encode($c) . ")'>View</button></td>
</tr>";
              }
            }
            ?>
          </tbody>
        </table>
      </div>
    </main>
  </div>

  <div id="viewModal" class="modal">
  <div class="modal-content">
    <span class="close" onclick="closeModal()">&times;</span>
    <h2>Complaint Details</h2>
    <div id="complaintDetails"></div>
  </div>
</div>

<footer class="main-footer">
  <p>Copyright © 2025, DRDO, Ministry of Defence, Government of India</p>
</footer>

<script>
  function showDetails(data) {
    const modal = document.getElementById("viewModal");
    const content = document.getElementById("complaintDetails");

    content.innerHTML = `
      <p><strong>Complaint ID:</strong> CMP${data.complaint_id}</p>
      <p><strong>Title:</strong> ${data.title}</p>
      <p><strong>Description:</strong> ${data.description}</p>
      <p><strong>Location:</strong> ${data.location}</p>
      <p><strong>Intercom:</strong> ${data.intercom}</p>
      <p><strong>Type:</strong> ${data.type}</p>
      <p><strong>Status:</strong> ${data.status}</p>
      <p><strong>Employee Name:</strong> ${data.emp_first_name} ${data.emp_last_name}</p>
      <p><strong>Registered Time:</strong> ${data.registered_time}</p>
      <p><strong>Resolved Time:</strong> ${data.resolved_time ?? '-'}</p>
      <p><strong>Assigned Engineer:</strong> ${data.eng_first_name ? data.eng_first_name + ' ' + data.eng_last_name : '-'}</p>
      <p><strong>Engineer Feedback:</strong> ${data.engineer_feedback ?? '-'}</p>
      <p><strong>Admin Feedback:</strong> ${data.admin_feedback ?? '-'}</p>
      <p><strong>Rating:</strong> ${data.rating ?? '-'}</p>
      <p><strong>Reason:</strong> ${data.reason ?? '-'}</p>
    `;

    modal.style.display = "flex";
  }

  function closeModal() {
    document.getElementById("viewModal").style.display = "none";
  }

  window.onclick = function(event) {
    const modal = document.getElementById("viewModal");
    if (event.target === modal) {
      closeModal();
    }
  };
</script>

</body>
</html>
