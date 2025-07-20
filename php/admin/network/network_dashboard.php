<?php
session_start();


if (!isset($_SESSION['username']) || !isset($_SESSION['admin_role'])) {
    header("Location: ../../../login/login_admin.html");
    exit();
}


if (trim($_SESSION['admin_role']) !== 'Network') {
    session_unset();
    session_destroy();
    header("Location: ../login.html");
    exit();
}

$conn = new mysqli("localhost", "root", "", "cfees_cms");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$user_name = $_SESSION['username'];

$sql = "SELECT first_name, middle_name, last_name, user_name FROM admin WHERE user_name = '$user_name' LIMIT 1";
$result = $conn->query($sql);


$admin_name = "Unknown Admin";
$admin_code = "N/A";

if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $first = isset($row['first_name']) ? $row['first_name'] : '';
    $middle = isset($row['middle_name']) ? $row['middle_name'] : '';
    $last = isset($row['last_name']) ? $row['last_name'] : '';
    $admin_name = trim("$first $middle $last");
    $admin_code = $row['user_name'];
}

$complaints = $conn->query("
  SELECT c.complaint_id, c.title, c.description, c.location, c.status, c.created_at,
         c.assigned_engineer_username,
         e.first_name, e.last_name, e.intercom
  FROM complaints c 
  JOIN employees e ON c.employee_user_name = e.user_name 
   WHERE c.type = 'Network' AND c.status IN ('Pending', 'Review Pending')
  ORDER BY c.created_at DESC
");

$engineers = $conn->query("SELECT user_name, CONCAT(first_name, ' ', last_name) AS full_name FROM engineer");
$engineer_list = [];
while ($eng = $engineers->fetch_assoc()) {
    $engineer_list[] = [
        'username' => $eng['user_name'],
        'full_name' => $eng['full_name']
    ];
}



?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Networking Dashboard</title>
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
  <link rel="stylesheet" href="../../../css/index/index.css" />
  <link rel="stylesheet" href="../../../css/admin/network_dashboard.css" />
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Tiro+Devanagari+Hindi:ital@0;1&display=swap" rel="stylesheet">

  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;600;700&display=swap" rel="stylesheet" />
</head>

<body>

  <header class="main-header">
    <div class="header-inner">
      <div class="logo-box left">
        <img src="../../../logos/logo-left.png" alt="Left Logo" />
      </div>
     <div class="header-center">
      <h1 class="hindi-bold">अग्नि, पर्यावरण तथा विस्फोटक सुरक्षा केंद्र (CFEES)</h1>
      <p class="hindi-regular">रक्षा मन्त्रालय, भारत सरकार</p>
      <h2 class="eng-bold">Centre for Fire, Explosive and Environment Safety (CFEES)</h2>
      <p class="eng-regular">Ministry of Defence, Government of India</p>
    </div>
      <div class="logo-box right">
        <img src="../../../logos/logo-right.png" alt="Right Logo" />
      </div>
    </div>
  </header>


  <div class="page-layout">

    <aside class="sidebar">
      <div class="profile-box">
        <div class="avatar-box">
        <img src="../../../logos/default_user.jpg" alt="Profile Picture" />
        </div>
      <h3><?php echo htmlspecialchars($admin_name, ENT_QUOTES, 'UTF-8'); ?></h3>
      </div>
      <nav class="sidebar-nav">
        <ul>
          <li><a href="MyProfile_network.php"><i class="fa-solid fa-user"></i> User Profile</a></li>
          <li><a href="./records.php"><i class="fa-solid fa-screwdriver-wrench"></i> Past
              Complaints</a>
          </li>
          <li><a href="./logout.php"><i class="fa fa-right-from-bracket"></i> Logout</a></li>
        </ul>
      </nav>
    </aside>


    <main class="main-content">
      <h2 class="welcome">Welcome, <?php echo htmlspecialchars($admin_name, ENT_QUOTES, 'UTF-8'); ?></h2>

      <h3 class="section-title">Complaints</h3>
      <div class="table-container">
  <table>
    <thead>
      <tr>
        <th>Complaint ID</th>
        <th>Employee Name</th>
        <th>Intercom</th>
        <th>Complaint Title</th>
        <th>Location</th>
        <th>Status</th>
        <th>Review</th>
        <th>Assigned Engineer</th>
      </tr>
    </thead>
    <tbody>
      <?php if ($complaints && $complaints->num_rows > 0): 
        while ($row = $complaints->fetch_assoc()): ?>
        <tr>
          <td>CMP<?php echo $row['complaint_id']; ?></td>
          <td><?php echo $row['first_name'] . ' ' . $row['last_name']; ?></td>
          <td><?php echo htmlspecialchars($row['intercom'], ENT_QUOTES); ?></td>
          <td><?php echo htmlspecialchars($row['title'], ENT_QUOTES); ?></td>
          <td><?php echo htmlspecialchars($row['location'], ENT_QUOTES); ?></td>
          <td><?php echo htmlspecialchars($row['status'], ENT_QUOTES); ?></td>
          <td>
             <?php if ($row['status'] === 'Review Pending'): ?>
            <button class="feedback" onclick="openFeedbackModal('<?php echo $row['complaint_id']; ?>')">Review Feedback</button>
          <?php else: ?>
            <button class="review" onclick="openReviewModal(
              '<?php echo $row['complaint_id']; ?>',
              '<?php echo htmlspecialchars($row['first_name'] . ' ' . $row['last_name'], ENT_QUOTES); ?>',
              '<?php echo $row['created_at']; ?>',
              '<?php echo htmlspecialchars($row['title'], ENT_QUOTES); ?>',
              '<?php echo htmlspecialchars($row['description'], ENT_QUOTES); ?>',
              '<?php echo htmlspecialchars($row['location'], ENT_QUOTES); ?>',
              '<?php echo htmlspecialchars($row['intercom'], ENT_QUOTES); ?>'
            )">Review</button>
          <?php endif; ?>
        </td>
       <td>
  <?php if (!empty($row['assigned_engineer_username'])): ?>
    <?php
      $assignedUsername = $row['assigned_engineer_username'];
      $displayName = $assignedUsername;

      foreach ($engineer_list as $eng) {
        if ($eng['username'] === $assignedUsername) {
          $displayName = $eng['full_name'];
          break;
        }
      }

      echo '<span>' . htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8') . '</span>';
    ?>
  <?php else: ?>
    <button class="assign" onclick="openAssignModal('<?php echo $row['complaint_id']; ?>')" data-id="<?php echo $row['complaint_id']; ?>">Assign</button>
  <?php endif; ?>
</td>

      </tr>
    <?php endwhile; ?>
  <?php else: ?>
    <tr><td colspan="8">No complaints found.</td></tr>
  <?php endif; ?>
</tbody>

  </table>
</div>

    </main>
  </div>

  <footer class="main-footer">
    <p>Copyright © 2025, DRDO, Ministry of Defence, Government of India</p>
  </footer>


  <div class="modal" id="reviewModal">
    <div class="modal-content">
      <span class="close-btn" onclick="closeReviewModal()">&times;</span>
      <h2>Complaint Details</h2>
      <p><strong>Complaint ID:</strong> <span id="r_id"></span></p>
      <p><strong>Employee Name:</strong> <span id="r_name"></span></p>
      <p><strong>Registered At:</strong> <span id="r_time"></span></p>
      <p><strong>Title:</strong> <span id="r_title"></span></p>
      <p><strong>Description:</strong> <span id="r_desc"></span></p>
      <p><strong>Location:</strong> <span id="r_loc"></span></p>
      <p><strong>Intercom:</strong> <span id="r_intercom"></span></p>
     
<div id="engineerAssignSection">
  <p id="assignMessage">Assign the Engineer</p>
  <p><strong>Assigned Engineer:</strong> <span id="r_engineer"></span></p>
  <button id="changeEngineerBtn" onclick="openAssignModal(currentComplaintId)">Change Engineer</button>
</div>
    </div>
  </div>

 
<div class="modal" id="feedbackReviewModal">
  <div class="modal-content">
    <span class="close-btn" onclick="closeFeedbackModal()">&times;</span>
    <h2>Engineer Feedback</h2>
    <p><strong>Complaint ID:</strong> <span id="f_id"></span></p>
    <p><strong>Engineer Feedback:</strong> <span id="f_solution"></span></p>
    <button class="resolved" onclick="acceptResolution()">Resolved</button>
    <button class="not-resolved" onclick="markNotResolved()">Not Resolved</button>
  </div>
</div>

  
<script>
function openReviewModal(id, name, time, title, desc, location, intercom) {
  document.getElementById('r_id').innerText = 'CMP' + id;
  document.getElementById('r_name').innerText = name;
  document.getElementById('r_time').innerText = time;
  document.getElementById('r_title').innerText = title;
  document.getElementById('r_desc').innerText = desc;
  document.getElementById('r_loc').innerText = location;
  document.getElementById('r_intercom').innerText = intercom;
  document.getElementById('reviewModal').style.display = 'block';
}

function closeReviewModal() {
  document.getElementById('reviewModal').style.display = 'none';
}

function openAssignModal(id) {
  // document.getElementById('assignComplaintId').innerText = 'CMP' + id;
  document.getElementById('assignModal').style.display = 'block';
}

function closeAssignModal() {
  document.getElementById('assignModal').style.display = 'none';
}
</script>
<script>
let allEngineers = <?php echo json_encode($engineer_list); ?>;
let currentComplaintId = null;
let assignedEngineerMap = {};

<?php

if ($complaints && $complaints->num_rows > 0) {
    $complaints->data_seek(0);
    echo "assignedEngineerMap = {";
    while ($row = $complaints->fetch_assoc()) {
        echo "'{$row['complaint_id']}': '" . ($row['assigned_engineer_username'] ?? '') . "',";
    }
    echo "};";
}
?>

function openReviewModal(id, name, time, title, desc, location, intercom) {
  currentComplaintId = id;
  document.getElementById('r_id').innerText = 'CMP' + id;
  document.getElementById('r_name').innerText = name;
  document.getElementById('r_time').innerText = time;
  document.getElementById('r_title').innerText = title;
  document.getElementById('r_desc').innerText = desc;
  document.getElementById('r_loc').innerText = location;
  document.getElementById('r_intercom').innerText = intercom;

  const engineer = assignedEngineerMap[id];
  if (!engineer || engineer === "") {
    document.getElementById('assignMessage').style.display = 'block';
    document.getElementById('r_engineer').style.display = 'none';
    document.getElementById('changeEngineerBtn').style.display = 'none';
  } else {
    document.getElementById('assignMessage').style.display = 'none';
    document.getElementById('r_engineer').style.display = 'inline';
    document.getElementById('r_engineer').innerText = engineer;
    document.getElementById('changeEngineerBtn').style.display = 'inline';
  }

  document.getElementById('reviewModal').style.display = 'block';
}

function openAssignModal(id) {
  currentComplaintId = id;
  document.getElementById('assignComplaintIdText').innerText = 'CMP' + id;
  document.getElementById('engineerInput').value = '';
  filterEngineers();
  document.getElementById('assignModal').style.display = 'block';
}

function closeAssignModal() {
  document.getElementById('assignModal').style.display = 'none';
}

function filterEngineers() {
  const input = document.getElementById('engineerInput').value.toLowerCase();
  const dropdown = document.getElementById('engineerDropdown');
  dropdown.innerHTML = '';
  const matches = allEngineers.filter(e => e.full_name.toLowerCase().includes(input));
  matches.forEach(e => {
    const div = document.createElement('div');
    div.innerText = e.full_name;
    div.onclick = () => {
      document.getElementById('engineerInput').value = e.full_name;
      document.getElementById('engineerInput').setAttribute('data-username', e.username); // for backend
      dropdown.innerHTML = '';
    };
    dropdown.appendChild(div);
  });
}


function assignEngineer() {
 const name = document.getElementById('engineerInput').getAttribute('data-username');
const displayName = document.getElementById('engineerInput').value;

  if (!name) return alert("Please enter or select an engineer");

  const xhr = new XMLHttpRequest();
  xhr.open("POST", "assign_engineer.php", true);
  xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
  xhr.onload = function() {
    if (xhr.status === 200 && xhr.responseText === "success") {
      assignedEngineerMap[currentComplaintId] = displayName;

    
      document.querySelectorAll(`button[data-id="${currentComplaintId}"]`).forEach(btn => {
  const row = btn.closest('tr');

 
  btn.outerHTML = `<span>${displayName}</span>`;


  const statusCell = row.querySelector('td:nth-child(6)');
  statusCell.innerText = 'Active';
});

closeAssignModal();
    } else {
      alert("Failed to assign engineer.");
    }
  };
  xhr.send("complaint_id=" + currentComplaintId + "&engineer=" + encodeURIComponent(name));

}

function openFeedbackModal(complaintId) {
  fetch("get_solution.php", {
    method: "POST",
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: "complaint_id=" + complaintId
  })
  .then(res => res.json())
  .then(data => {
    document.getElementById('f_id').innerText = 'CMP' + complaintId;
    document.getElementById('f_solution').innerText = data.solution || "No feedback provided";
    document.getElementById('feedbackReviewModal').style.display = 'block';
    currentComplaintId = complaintId;
  });
}

function closeFeedbackModal() {
  document.getElementById('feedbackReviewModal').style.display = 'none';
}

function acceptResolution() {
  fetch("accept_resolution.php", {
    method: "POST",
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: "complaint_id=" + currentComplaintId
  }).then(res => res.text()).then(data => {
    if (data === 'success') {
      alert("Resolution accepted.");
      location.reload();
    } else {
      alert("Failed to accept.");
    }
  });
}

function reassignComplaint() {
  closeFeedbackModal();
  openAssignModal(currentComplaintId);
}
function markNotResolved() {
  fetch("mark_not_resolved.php", {
    method: "POST",
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: "complaint_id=" + currentComplaintId
  }).then(res => res.text()).then(data => {
    if (data === 'success') {
      alert("Complaint marked as Pending again.");
  
    } else {
      alert("Failed to mark as not resolved.");
    }
  });
}

function submitAfterReview() {
  fetch("submit_review_resolution.php", {
    method: "POST",
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: "complaint_id=" + currentComplaintId
  }).then(res => res.text()).then(data => {
    if (data === 'success') {
      alert("Complaint removed after review.");
      closeFeedbackModal();
      location.reload();
    } else {
      alert("Failed to finalize complaint.");
    }
  });
}

</script>

<div class="modal" id="assignModal">
  <div class="modal-content">
    <span class="close-btn" onclick="closeAssignModal()">&times;</span>
    <h2>Assign Engineer</h2>
    <p>Complaint ID: <span id="assignComplaintIdText"></span></p>

   <input type="text" id="engineerInput" onclick="filterEngineers()" oninput="filterEngineers()" placeholder="Type engineer name..." autocomplete="off"/>

    <div id="engineerDropdown" class="dropdown-list"></div>

    <button onclick="assignEngineer()">Assign Engineer</button>
  </div>
</div>


</body>
</html>
