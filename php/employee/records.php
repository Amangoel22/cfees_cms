<?php
session_start();

if (!isset($_SESSION['user_name'])) {
  header("Location: ../../login/login_employee.html");
  exit();
}

$conn = new mysqli("localhost", "root", "", "cfees_cms");
if ($conn->connect_error) die("Connection failed: " . $conn->connect_error);

$user_name = $_SESSION['user_name'];

function getEngineerName($conn, $username) {
  if (!$username) return "-";
  $stmt = $conn->prepare("SELECT first_name, last_name FROM engineer WHERE user_name = ?");
  $stmt->bind_param("s", $username);
  $stmt->execute();
  $stmt->bind_result($fname, $lname);
  $stmt->fetch();
  $stmt->close();
  return $fname . ' ' . $lname;
}

function getFeedback($conn, $cid) {
  $stmt = $conn->prepare("SELECT rating, reason FROM feedback WHERE complaint_id = ?");
  $stmt->bind_param("i", $cid);
  $stmt->execute();
  $stmt->bind_result($rating, $reason);
  $stmt->fetch();
  $stmt->close();
  return [$rating, $reason];
}

$sql = "SELECT * FROM complaints WHERE employee_user_name = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("s", $user_name);
$stmt->execute();
$result = $stmt->get_result();
$complaints = [];

while ($row = $result->fetch_assoc()) {
  $cid = $row['complaint_id'];
  $row['engineer'] = getEngineerName($conn, $row['assigned_engineer_username']);
  if ($row['status'] === 'Resolved') {
    list($rating, $reason) = getFeedback($conn, $cid);
    $row['rating'] = $rating;
    $row['reason'] = $reason;
  }
  $complaints[] = $row;
}
$stmt->close();

header('Content-Type: text/html');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Complaint Records - DRDO CFEES</title>
  <link rel="stylesheet" href="../../css/employee/records.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;600;700&display=swap" rel="stylesheet">
  <script>
    let complaints = <?php echo json_encode($complaints); ?>;
  </script>
  <style>
    .status {
      padding: 4px 10px;
      border-radius: 6px;
      font-weight: bold;
      display: inline-block;
    }
    .status.pending {
      color: #d0ab19ff;
    }
    .status.active {
      color: #0277bd;
    }
    .status.resolved {
      color: #388e3c;
    }
    .view-btn {
      background: #0059b3;
      color: #fff;
      padding: 6px 12px;
      border: none;
      border-radius: 6px;
      cursor: pointer;
      font-size: 13px;
      transition: background 0.3s;
    }
    .view-btn:hover {
      background: #004080;
    }
  </style>

</head>
<body>
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


<!-- Filters -->
  <div class="dashboard">
    <main class="main-content">
        <a href="dashboard.php" class="back-top-btn">
  <i class="fa fa-arrow-left"></i> Back to Dashboard
</a>
      <section class="filters">
          <label>Sort by:
        <select id="sortOrder">
          <option value="desc">Newest to Oldest</option>
          <option value="asc">Oldest to Newest</option>
        </select>
          </label>
        <label>Complaint Type: 
          <select id="typeFilter">
          <option value="">Select Type</option>
          <option value="Software">Software</option>
          <option value="Hardware">IT Hardware</option>
          <option value="Network">Network</option>
        </select>
  </label>
        <label>From: <input type="date" id="fromDate"></label>
        <label>To: <input type="date" id="toDate"></label>
        <label>By Title: <input type="text" id="searchText" placeholder="Search..."></label>
        <button onclick="applyFilters()">Apply Filters</button>
      </section>
      
      <section class="complaint-records">
        <h2>Your Complaints</h2>
        <table id="complaintTable">
          <thead>
            <tr><tH>S.No.</th><th>Complaint ID</th><th>Title</th><th>Date</th><th>Complaint Type</th><th>Location</th><th>Status</th><th>Resolved Date and Time</th><th>Details</th></tr>
          </thead>
          <tbody></tbody>
        </table>
      </section>
    </main>
  </div>


  <div class="modal" id="detailsModal">
    <div class="modal-content">
      <span class="close-btn" onclick="document.getElementById('detailsModal').style.display='none'">&times;</span>
      <div id="detailsContent"></div>
    </div>
  </div>
  
  <!-- Footer -->
 <footer class="main-footer">
    <p>Copyright © 2025, DRDO, Ministry of Defence, Government of India</p>
  </footer>

  <script>
    const tbody = document.querySelector('#complaintTable tbody');
    function formatDate(dateStr) {
      const d = new Date(dateStr);
      return d.toLocaleString('en-GB');
    }
   function populateTable(data) {
  tbody.innerHTML = "";
  if (!data.length) {
    tbody.innerHTML = '<tr><td colspan="9" style="text-align:center">No complaints found</td></tr>';
    return;
  }

  data.forEach((c, index) => {
    const tr = document.createElement('tr');
    const statusClass = c.status.toLowerCase();
    tr.innerHTML = `
      <td>${index + 1}</td>
      <td>CMP${c.complaint_id}</td>
      <td>${c.title}</td>
      <td>${formatDate(c.created_at)}</td>
      <td>${c.type}</td>
      <td>${c.location}</td>
      <td><span class="status ${statusClass}">${c.status}</span></td>
      <td>${c.status === 'Resolved' ? formatDate(c.updated_at) : '-'}</td>
      <td><button class="view-btn" onclick='showDetails(${JSON.stringify(c)})'>View</button></td>
    `;
    tbody.appendChild(tr);
  });
}

    function showDetails(c) {
      let html = `<h3>Complaint Details</h3>
        <p><strong>Complaint ID:</strong> CMP${c.complaint_id}</p>
        <p><strong>Title:</strong> ${c.title}</p>
        <p><strong>Description:</strong> ${c.description}</p>
        <p><strong>Type:</strong> ${c.type}</p>
        <p><strong>Location:</strong> ${c.location}</p>
        <p><strong>Status:</strong> ${c.status}</p>
        <p><strong>Assigned Engineer:</strong> ${c.engineer}</p>`;
      if (c.status === 'Resolved') {
        html += `
        <p><strong>Engineer Feedback:</strong> ${c.solution || '-'}</p>
        <p><strong>Admin Feedback:</strong> ${c.status || '-'}</p>
        <p><strong>Rating:</strong> ${c.rating || '-'}<i class="fa-solid fa-star"></i></p>
        <p><strong>Reason:</strong> ${c.reason || '-'}</p>`;
      }
      document.getElementById('detailsContent').innerHTML = html;
      document.getElementById('detailsModal').style.display = 'flex';
    }
    function applyFilters() {
      const type = document.getElementById('typeFilter').value;
      const from = new Date(document.getElementById('fromDate').value);
      const to = new Date(document.getElementById('toDate').value);
      const text = document.getElementById('searchText').value.toLowerCase();
      const order = document.getElementById('sortOrder').value;

      let filtered = complaints.filter(c => {
        if (type && c.type !== type) return false;
        const created = new Date(c.created_at);
        if (!isNaN(from.getTime()) && created < from) return false;
        if (!isNaN(to.getTime()) && created > to) return false;
        if (text && !(c.title + c.description + c.type + c.location).toLowerCase().includes(text)) return false;
        return true;
      });

      filtered.sort((a, b) => {
        const dateA = new Date(a.created_at);
        const dateB = new Date(b.created_at);
        return order === 'desc' ? dateB - dateA : dateA - dateB;
      });

      populateTable(filtered);
    }
    window.onload = () => populateTable(complaints);
  </script>
</body>
</html>
