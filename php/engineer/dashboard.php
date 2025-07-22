<?php
session_start();

if (!isset($_SESSION['user_name'])) {
    header("Location: ../../login/login_engineer.html");
    exit();
}

$conn = new mysqli("localhost", "root", "", "cfees_cms");
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

$user_name = $_SESSION['user_name'];
$sql = "SELECT first_name, middle_name, last_name FROM engineer WHERE user_name = '$user_name' LIMIT 1";
$result = $conn->query($sql);

$engineerName = '';
if ($result && $result->num_rows === 1) {
    $row = $result->fetch_assoc();
    $engineerName = trim(($row['first_name'] ?? '') . ' ' . ($row['middle_name'] ?? '') . ' ' . ($row['last_name'] ?? ''));
}

$complaints = $conn->query("
  SELECT c.complaint_id, c.title, c.description, c.location, 
         c.status,
         COALESCE(NULLIF(TRIM(c.engineer_status), ''), 'Pending') AS engineer_status,
         COALESCE(c.intercom, e.intercom) AS intercom,
         c.solution,
         e.first_name, e.middle_name, e.last_name
  FROM complaints c
  LEFT JOIN employees e ON c.employee_user_name = e.user_name
  WHERE c.assigned_engineer_username = '$user_name'
  ORDER BY c.created_at DESC
");

?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Engineer Dashboard</title>
  <link rel="stylesheet" href="../../css/engineer/dashboard.css" />
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css" />
  <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;600;700&display=swap" rel="stylesheet" />
  <link href="https://fonts.googleapis.com/css2?family=Tiro+Devanagari+Hindi:ital@0;1&display=swap" rel="stylesheet">
  <style>
    .status.pending { color: orange; font-weight: bold; }
    .status.active { color: blue; font-weight: bold; }
    .status.resolved { color: green; font-weight: bold; }
    .status.not-resolved { color: red; font-weight: bold; }
    .resolved-indicator,
    .not-resolved-indicator {
    background-color: green;
    color: white;
    border: none;
    padding: 9px 10px;
    border-radius: 8px;
    font-weight: bold;
    cursor: not-allowed;
    font-size: 15px;
}
.not-resolved-indicator{
  background-color:rgb(164, 21, 21);
}
  </style>
</head>
<body>
  <header class="main-header">
    <div class="header-inner">
      <div class="logo-box left">
        <img src="../../logos/logo-left.png" alt="Left Logo" />
      </div>
      <div class="header-center">
      <h1 class="hindi-bold">अग्नि, पर्यावरण तथा विस्फोटक सुरक्षा केंद्र (CFEES)</h1>
      <p class="hindi-regular">रक्षा मन्त्रालय, भारत सरकार</p>
      <h2 class="eng-bold">Centre for Fire, Explosive and Environment Safety (CFEES)</h2>
      <p class="eng-regular">Ministry of Defence, Government of India</p>
    </div>
      <div class="logo-box right">
        <img src="../../logos/logo-right.png" alt="Right Logo" />
      </div>
    </div>
  </header>

  <div class="dashboard">
    <aside class="sidebar">
      <div class="profile-box">
        <div class="avatar-box">
        <img src="../../logos/default_user.jpg" alt="Profile Picture" />
        </div>
        <h3><?php echo htmlspecialchars($engineerName ?? ''); ?></h3>
      </div>
      <nav class="sidebar-nav">
        <ul>
          <li><a href="MyProfile.php"><i class="fas fa-user"></i> My Profile</a></li>
          <li><a href="records.php"><i class="fa-solid fa-screwdriver-wrench"></i> Complaints Record</a></li>
           <li><a href="logout.php"><i class="fa fa-right-from-bracket"></i> Logout</a></li>
        </ul>
      </nav>
    </aside>

    <main class="main-content">
      <div class="welcome-box">
        <h1 class="left-align">Welcome, <?php echo htmlspecialchars($engineerName ?? ''); ?></h1>
        <h2 class="center-align">Complaints</h2>
      </div>

      <div class="table-container">
        <table class="complaint-table">
          <thead>
            <tr>
              <th>S.No.</th>
              <th>Complaint ID</th>
              <th>Employee Name</th>
              <th>Intercom</th>
              <th>Location</th>
              <th>Complaint Title</th>
              <th>Status</th>
              <th>View</th>
              <th>Resolve</th>
            </tr>
          </thead>
          <tbody>
          <?php if ($complaints && $complaints->num_rows > 0):
          $index = 1;
            while ($row = $complaints->fetch_assoc()): ?>
            <tr>
              <td><?php echo $index++;?></td>
              <td>CMP<?php echo $row['complaint_id']; ?></td>
              <td><?php echo htmlspecialchars(trim(($row['first_name'] ?? '') . ' ' . ($row['middle_name'] ?? '') . ' ' . ($row['last_name'] ?? ''))); ?></td>  
              <td><?php echo htmlspecialchars($row['intercom'] !== null ? $row['intercom'] : 'N/A'); ?></td>
              <td><?php echo htmlspecialchars($row['location'] ?? ''); ?></td>
              <td><?php echo htmlspecialchars($row['title'] ?? ''); ?></td>
              <td><span class="status <?php echo strtolower(str_replace(' ', '-', $row['engineer_status'] ?? '')); ?>"><?php echo htmlspecialchars($row['engineer_status'] ?? ''); ?></span></td>
              <td>
                <button class="review-btn"
                  data-id="<?php echo $row['complaint_id']; ?>"
                  data-title="<?php echo htmlspecialchars($row['title'] ?? ''); ?>"
                  data-desc="<?php echo htmlspecialchars($row['description'] ?? ''); ?>"
                  data-intercom="<?php echo htmlspecialchars($row['intercom'] ?? ''); ?>"
                  data-location="<?php echo htmlspecialchars($row['location'] ?? ''); ?>"
                  data-status="<?php echo htmlspecialchars($row['engineer_status'] ?? '', ENT_QUOTES); ?>"

                >View</button>
              </td>
              <td>
  <?php if ($row['engineer_status'] === 'Resolved'): ?>
    <button class="resolved-indicator" disabled>Resolved</button>
  <?php elseif ($row['engineer_status'] === 'Not Resolved'): ?>
    <button class="not-resolved-indicator" disabled>Not Resolved</button>
  <?php else: ?>
    <button class="resolve-btn" data-id="<?php echo $row['complaint_id']; ?>">Resolve</button>
  <?php endif; ?>
</td>
            </tr>
          <?php endwhile; else: ?>
            <tr><td colspan="7">No complaints assigned.</td></tr>
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
      <h3>Complaint Review</h3>
      <p><strong>Complaint ID:</strong> <span id="r_id"></span></p>
      <p><strong>Title:</strong> <span id="r_title"></span></p>
      <p><strong>Description:</strong> <span id="r_desc"></span></p>
      <p><strong>Intercom:</strong> <span id="r_intercom"></span></p>
      <p><strong>Location:</strong> <span id="r_location"></span></p>
      <button id="markReviewBtn" onclick="markReviewed()">Mark as Viewed</button>
    </div>
  </div>


  <div class="modal" id="resolveModal">
    <div class="modal-content">
      <span class="close-btn" onclick="closeResolveModal()">&times;</span>
      <h3>Resolve Complaint</h3>
      <p><strong>Complaint ID:</strong> <span id="res_id"></span></p>
   <button class="resolve-resolved" onclick="showFeedbackBox('resolved')">Resolved</button>
<button class="resolve-not-resolved" onclick="showFeedbackBox('not_resolved')">Not Resolved</button>

      <div id="feedbackSection" style="display:none;">
      <textarea id="f_reason" placeholder="Enter Remarks" rows="3" ></textarea>
      <button id="submit-feedback" onclick="submitResolution()">Submit</button>
      </div>
    </div>
  </div>

<script>
let currentComplaintId = '';
let resolutionType = '';

function closeReviewModal() {
  document.getElementById('reviewModal').style.display = 'none';
}

function closeResolveModal() {
  document.getElementById('resolveModal').style.display = 'none';
  document.getElementById('feedbackSection').style.display = 'none';
  document.getElementById('feedbackText').value = '';


document.querySelector('.resolve-resolved').style.display = 'inline-block';
document.querySelector('.resolve-not-resolved').style.display = 'inline-block';

}


function showFeedbackBox(type) {
  resolutionType = type;
  document.getElementById('feedbackSection').style.display = 'block';

  const resolvedBtn = document.querySelector('#resolveModal button.resolve-resolved');
  const notResolvedBtn = document.querySelector('#resolveModal button.resolve-not-resolved');


  if (type === 'resolved') {
    resolvedBtn.style.display = 'inline-block';
    notResolvedBtn.style.display = 'none';
  } else {
    resolvedBtn.style.display = 'none';
    notResolvedBtn.style.display = 'inline-block';
  }
}


document.querySelectorAll('.review-btn').forEach(btn => {
  btn.addEventListener('click', function() {
    currentComplaintId = btn.dataset.id;
    document.getElementById('r_id').textContent = 'CMP' + btn.dataset.id;
    document.getElementById('r_title').textContent = btn.dataset.title;
    document.getElementById('r_desc').textContent = btn.dataset.desc;
    document.getElementById('r_intercom').textContent = btn.dataset.intercom;
    document.getElementById('r_location').textContent = btn.dataset.location;

    
    const status = btn.dataset.status?.trim().toLowerCase();

    const reviewBtn = document.getElementById('markReviewBtn');
   if (status === 'pending' || status === '') {
  reviewBtn.style.display = 'inline-block';
} else {
  reviewBtn.style.display = 'none';
}

    document.getElementById('reviewModal').style.display = 'block';
  });
});


document.querySelectorAll('.resolve-btn').forEach(btn => {
  btn.addEventListener('click', function() {
    currentComplaintId = btn.dataset.id;
    document.getElementById('res_id').textContent = 'CMP' + currentComplaintId;
    document.getElementById('resolveModal').style.display = 'block';
  });
});

function markReviewed() {
  fetch('mark_reviewed.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: 'complaint_id=' + currentComplaintId
  })
  .then(res => res.text())
  .then(data => {
    if (data === 'success') {
      
      const reviewBtn = document.querySelector(`button.review-btn[data-id="${currentComplaintId}"]`);
      document.getElementById('markReviewBtn').style.display = 'none';
      document.getElementById('reviewModal').style.display = 'none';


     
      const statusSpan = reviewBtn.closest('tr').querySelector('td:nth-child(5) > span');
      statusSpan.textContent = 'Active';
      statusSpan.className = 'status active';

    
      reviewBtn.dataset.status = 'Active';

      closeReviewModal();
    } else {
      alert('Error marking reviewed');
    }
  });
}


function submitResolution() {
  const feedback = document.getElementById('f_reason').value.trim();
  if (feedback === '') {
    alert('Please enter feedback or reason.');
    return;
  }
  const formData = new URLSearchParams();
  formData.append('complaint_id', currentComplaintId);
  formData.append('resolution', resolutionType);
  formData.append('message', feedback);

  fetch('resolve_complaint.php', {
    method: 'POST',
    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
    body: formData.toString()
  })
  .then(res => res.text())
  .then(data => {
    if (data === 'success') {
      location.reload();
    } else {
      alert('Error submitting resolution.');
    }
  });
}
</script>

</body>
</html>
