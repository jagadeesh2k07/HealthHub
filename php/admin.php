<?php
session_start();
include '../db.php';

if (!isset($_SESSION['user_id'])) { header("Location: ../login.html"); exit(); }
if ($_SESSION['role'] !== 'admin') { header("Location: dashboard.php"); exit(); }

$uid = $_SESSION['user_id'];
$user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt = (function() use ($conn, $uid) {
    $s = mysqli_prepare($conn, "SELECT * FROM users WHERE id = ?");
    mysqli_stmt_bind_param($s, 'i', $uid); mysqli_stmt_execute($s); return $s;
})()));

// Stats
$totalUsers    = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM users"))['c'];
$totalDoctors  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM doctors"))['c'];
$totalAppts    = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM appointments"))['c'];
$pendingAppts  = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM appointments WHERE status='pending'"))['c'];
$todayAppts    = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM appointments WHERE appointment_date = CURDATE()"))['c'];
$cancelledAppts= mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) c FROM appointments WHERE status='cancelled'"))['c'];

// All appointments
$allAppts = mysqli_query($conn, "SELECT a.*, u.first_name, u.last_name, u.email AS patient_email, d.name AS doctor_name, d.specialization
    FROM appointments a
    JOIN users u ON a.patient_id = u.id
    JOIN doctors d ON a.doctor_id = d.id
    ORDER BY a.appointment_date DESC, a.appointment_time DESC");

// All doctors
$allDoctors = mysqli_query($conn, "SELECT * FROM doctors ORDER BY name ASC");

// All users
$allUsers = mysqli_query($conn, "SELECT * FROM users ORDER BY created_at DESC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Admin Panel — HealthHub</title>
  <link rel="stylesheet" href="../css/style.css"/>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"/>
  <style>
    body{background:var(--bg);}
    .tab-bar{display:flex;gap:4px;background:white;border:1px solid var(--border);border-radius:12px;padding:4px;margin-bottom:24px;width:fit-content;}
    .tab-btn{
      padding:9px 20px;border-radius:9px;font-size:13px;font-weight:600;
      cursor:pointer;border:none;background:transparent;color:var(--text2);
      font-family:var(--font);transition:all var(--ease);
    }
    .tab-btn.active{background:var(--teal);color:white;box-shadow:0 2px 8px rgba(13,148,136,0.3);}
    .tab-panel{display:none;}
    .tab-panel.active{display:block;}
    /* add doctor modal */
    .add-overlay{position:fixed;inset:0;z-index:200;display:flex;align-items:center;justify-content:center;padding:20px;background:rgba(15,23,42,0.6);backdrop-filter:blur(8px);}
    .add-overlay.hidden{display:none!important;}
    .add-card{background:white;border-radius:20px;padding:36px 32px;max-width:480px;width:100%;box-shadow:var(--shadow-lg);border:1px solid var(--border);position:relative;animation:modalPop 0.35s cubic-bezier(.34,1.4,.64,1) both;}
    @keyframes modalPop{from{opacity:0;transform:scale(0.88)}to{opacity:1;transform:scale(1)}}
    .add-field{margin-bottom:16px;}
    .add-field label{display:block;font-size:13px;font-weight:600;color:var(--text);margin-bottom:6px;}
    .add-field input,.add-field select{width:100%;padding:11px 14px;background:var(--bg);border:1.5px solid var(--border);border-radius:10px;font-family:var(--font);font-size:14px;color:var(--text);outline:none;transition:border-color var(--ease);}
    .add-field input:focus,.add-field select:focus{border-color:var(--teal);box-shadow:0 0 0 3px var(--teal-dim);}
    .add-row2{display:grid;grid-template-columns:1fr 1fr;gap:12px;}
    .admin-badge{background:var(--teal-dim);color:var(--teal);border:1px solid var(--teal-border);padding:2px 10px;border-radius:20px;font-size:11px;font-weight:600;}
  </style>
</head>
<body>

<!-- NAV -->
<nav class="dash-nav">
  <div class="dash-nav-inner">
    <a href="../index.html" class="dash-logo"><i class="fas fa-heartbeat"></i> HealthHub</a>
    <div class="dash-nav-right">
      <span class="dash-user">Admin: <strong><?= htmlspecialchars($user['first_name']) ?></strong> <span class="admin-badge">Admin</span></span>
      <a href="logout.php" class="btn-logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>
  </div>
</nav>

<div class="dash-main">
  <div class="page-title">Admin Dashboard</div>
  <div class="page-sub">Manage appointments, doctors, and users</div>

  <!-- STATS -->
  <div class="stats-grid" style="grid-template-columns:repeat(3,1fr);margin-bottom:28px;">
    <div class="stat-card">
      <div class="stat-card-icon"><i class="fas fa-users"></i></div>
      <div class="stat-card-info"><h3><?= $totalUsers ?></h3><p>Total Patients</p></div>
    </div>
    <div class="stat-card">
      <div class="stat-card-icon"><i class="fas fa-user-doctor"></i></div>
      <div class="stat-card-info"><h3><?= $totalDoctors ?></h3><p>Doctors</p></div>
    </div>
    <div class="stat-card">
      <div class="stat-card-icon"><i class="fas fa-calendar-days"></i></div>
      <div class="stat-card-info"><h3><?= $totalAppts ?></h3><p>Total Appointments</p></div>
    </div>
    <div class="stat-card">
      <div class="stat-card-icon" style="background:#fffbeb"><i class="fas fa-clock" style="color:#d97706"></i></div>
      <div class="stat-card-info"><h3><?= $pendingAppts ?></h3><p>Pending</p></div>
    </div>
    <div class="stat-card">
      <div class="stat-card-icon" style="background:var(--ok-bg)"><i class="fas fa-calendar-check" style="color:var(--ok)"></i></div>
      <div class="stat-card-info"><h3><?= $todayAppts ?></h3><p>Today's Appointments</p></div>
    </div>
    <div class="stat-card">
      <div class="stat-card-icon" style="background:var(--err-bg)"><i class="fas fa-ban" style="color:var(--err)"></i></div>
      <div class="stat-card-info"><h3><?= $cancelledAppts ?></h3><p>Cancelled</p></div>
    </div>
  </div>

  <!-- TABS -->
  <div class="tab-bar">
    <button class="tab-btn active" onclick="switchTab('appointments',this)"><i class="fas fa-calendar-days"></i> Appointments</button>
    <button class="tab-btn" onclick="switchTab('doctors',this)"><i class="fas fa-user-doctor"></i> Doctors</button>
    <button class="tab-btn" onclick="switchTab('users',this)"><i class="fas fa-users"></i> Users</button>
  </div>

  <!-- APPOINTMENTS TAB -->
  <div class="tab-panel active" id="tab-appointments">
    <div class="section-card">
      <div class="section-card-title"><i class="fas fa-calendar-check"></i> All Appointments</div>
      <div class="filter-bar">
        <div class="search-wrap">
          <i class="fas fa-search"></i>
          <input type="text" class="search-input" placeholder="Search patient or doctor…" oninput="filterAppts(this.value)"/>
        </div>
        <select class="filter-select" onchange="filterApptStatus(this.value)">
          <option value="">All Status</option>
          <option value="pending">Pending</option>
          <option value="confirmed">Confirmed</option>
          <option value="cancelled">Cancelled</option>
        </select>
      </div>
      <table class="data-table" id="apptsTable">
        <thead>
          <tr><th>#</th><th>Patient</th><th>Doctor</th><th>Specialization</th><th>Date</th><th>Time</th><th>Status</th><th>Action</th></tr>
        </thead>
        <tbody>
          <?php $i=1; while($a=mysqli_fetch_assoc($allAppts)): ?>
          <tr data-search="<?= strtolower($a['first_name'].' '.$a['last_name'].' '.$a['doctor_name']) ?>" data-status="<?= $a['status'] ?>">
            <td><?= $i++ ?></td>
            <td><strong><?= htmlspecialchars($a['first_name'].' '.$a['last_name']) ?></strong><br><small style="color:var(--text3)"><?= htmlspecialchars($a['patient_email']) ?></small></td>
            <td><?= htmlspecialchars($a['doctor_name']) ?></td>
            <td><?= htmlspecialchars($a['specialization']) ?></td>
            <td><?= date('d M Y',strtotime($a['appointment_date'])) ?></td>
            <td><?= date('h:i A',strtotime($a['appointment_time'])) ?></td>
            <td>
              <span class="badge badge-<?= $a['status'] ?>"><?= ucfirst($a['status']) ?></span>
              <?php if($a['cancelled_by']): ?><br><small style="color:var(--text3);font-size:11px">by <?= $a['cancelled_by'] ?></small><?php endif; ?>
            </td>
            <td>
              <div class="action-btns">
                <?php if($a['status']==='pending'): ?>
                  <button class="btn-sm btn-sm-teal" onclick="updateStatus(<?= $a['id'] ?>,'confirmed')"><i class="fas fa-check"></i> Confirm</button>
                <?php endif; ?>
                <?php if($a['status']!=='cancelled'): ?>
                  <button class="btn-sm btn-sm-red" onclick="adminCancel(<?= $a['id'] ?>)"><i class="fas fa-times"></i> Cancel</button>
                <?php else: ?>
                  <span style="color:var(--text3);font-size:12px">—</span>
                <?php endif; ?>
              </div>
            </td>
          </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- DOCTORS TAB -->
  <div class="tab-panel" id="tab-doctors">
    <div class="section-card">
      <div class="section-card-title" style="justify-content:space-between">
        <span><i class="fas fa-user-doctor"></i> Manage Doctors</span>
        <button class="btn-primary" onclick="openAddDoctor()"><i class="fas fa-plus"></i> Add Doctor</button>
      </div>
      <table class="data-table" id="doctorsTable">
        <thead>
          <tr><th>#</th><th>Name</th><th>Specialization</th><th>Experience</th><th>Fee</th><th>Availability</th><th>Action</th></tr>
        </thead>
        <tbody>
          <?php $i=1; mysqli_data_seek($allDoctors,0); while($d=mysqli_fetch_assoc($allDoctors)): ?>
          <tr>
            <td><?= $i++ ?></td>
            <td><strong><?= htmlspecialchars($d['name']) ?></strong></td>
            <td><span class="badge" style="background:var(--teal-dim);color:var(--teal);border:1px solid var(--teal-border)"><?= htmlspecialchars($d['specialization']) ?></span></td>
            <td><?= $d['experience'] ?> yrs</td>
            <td>₹<?= number_format($d['fee'],0) ?></td>
            <td style="font-size:12px;color:var(--text2)"><?= htmlspecialchars($d['availability']) ?></td>
            <td>
              <div class="action-btns">
                <button class="btn-sm btn-sm-red" onclick="deleteDoctor(<?= $d['id'] ?>, '<?= htmlspecialchars($d['name']) ?>')"><i class="fas fa-trash"></i> Delete</button>
              </div>
            </td>
          </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>

  <!-- USERS TAB -->
  <div class="tab-panel" id="tab-users">
    <div class="section-card">
      <div class="section-card-title"><i class="fas fa-users"></i> All Users</div>
      <table class="data-table">
        <thead>
          <tr><th>#</th><th>Name</th><th>Email</th><th>Phone</th><th>Role</th><th>Joined</th><th>Action</th></tr>
        </thead>
        <tbody>
          <?php $i=1; while($u=mysqli_fetch_assoc($allUsers)): ?>
          <tr>
            <td><?= $i++ ?></td>
            <td><strong><?= htmlspecialchars($u['first_name'].' '.$u['last_name']) ?></strong></td>
            <td><?= htmlspecialchars($u['email']) ?></td>
            <td><?= htmlspecialchars($u['phone']?:'—') ?></td>
            <td><span class="badge <?= $u['role']==='admin'?'badge-admin':'badge-patient' ?>"><?= ucfirst($u['role']) ?></span></td>
            <td><?= date('d M Y',strtotime($u['created_at'])) ?></td>
            <td>
              <?php if($u['id'] !== $uid): ?>
              <button class="btn-sm btn-sm-red" onclick="deleteUser(<?= $u['id'] ?>, '<?= htmlspecialchars($u['first_name']) ?>')"><i class="fas fa-trash"></i> Delete</button>
              <?php else: ?>
              <span style="color:var(--text3);font-size:12px">You</span>
              <?php endif; ?>
            </td>
          </tr>
          <?php endwhile; ?>
        </tbody>
      </table>
    </div>
  </div>

</div>

<!-- ADD DOCTOR MODAL -->
<div class="add-overlay hidden" id="addDoctorOverlay">
  <div class="add-card">
    <button class="modal-close" onclick="closeAddDoctor()"><i class="fas fa-times"></i></button>
    <div style="font-size:1.1rem;font-weight:800;color:var(--text);margin-bottom:6px"><i class="fas fa-user-plus" style="color:var(--teal);margin-right:8px"></i>Add New Doctor</div>
    <div style="font-size:13px;color:var(--text2);margin-bottom:20px">Fill in the doctor's details</div>
    <div class="add-row2">
      <div class="add-field">
        <label>Full Name</label>
        <input type="text" id="docName" placeholder="Dr. Name"/>
      </div>
      <div class="add-field">
        <label>Specialization</label>
        <input type="text" id="docSpec" placeholder="Cardiologist"/>
      </div>
    </div>
    <div class="add-row2">
      <div class="add-field">
        <label>Experience (years)</label>
        <input type="number" id="docExp" placeholder="5" min="0"/>
      </div>
      <div class="add-field">
        <label>Consultation Fee (₹)</label>
        <input type="number" id="docFee" placeholder="500" min="0"/>
      </div>
    </div>
    <div class="add-field">
      <label>Available Days</label>
      <input type="text" id="docAvail" placeholder="Mon,Tue,Wed,Thu,Fri"/>
    </div>
    <div class="toast hidden" id="addDocToast"></div>
    <button class="btn-submit" style="margin-top:8px" onclick="submitAddDoctor()">
      <span id="addDocTxt"><i class="fas fa-plus"></i> Add Doctor</span>
      <span id="addDocLoad" class="spin hidden"><i class="fas fa-circle-notch fa-spin"></i></span>
    </button>
  </div>
</div>

<!-- DELETE CONFIRM -->
<div class="del-overlay hidden" id="delOverlay">
  <div class="del-card">
    <div class="del-icon"><i class="fas fa-triangle-exclamation"></i></div>
    <div class="del-title" id="delTitle">Delete?</div>
    <div class="del-sub" id="delSub">This action cannot be undone.</div>
    <div class="del-btns">
      <button class="btn-cancel" onclick="closeDelModal()">Cancel</button>
      <button class="btn-confirm-del" id="delConfirmBtn"><i class="fas fa-trash"></i> Delete</button>
    </div>
  </div>
</div>

<script>
// Tabs
function switchTab(name, btn) {
  document.querySelectorAll('.tab-panel').forEach(p => p.classList.remove('active'));
  document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));
  document.getElementById('tab-' + name).classList.add('active');
  btn.classList.add('active');
}

// Filter appointments
function filterAppts(q) {
  document.querySelectorAll('#apptsTable tbody tr').forEach(row => {
    row.style.display = row.dataset.search.includes(q.toLowerCase()) ? '' : 'none';
  });
}
function filterApptStatus(s) {
  document.querySelectorAll('#apptsTable tbody tr').forEach(row => {
    row.style.display = (!s || row.dataset.status === s) ? '' : 'none';
  });
}

// Update appointment status
function updateStatus(id, status) {
  const fd = new FormData();
  fd.append('id', id); fd.append('status', status);
  fetch('update_appointment.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(data => { if (data.status === 'success') location.reload(); else alert(data.message); });
}

// Admin cancel
function adminCancel(id) {
  showDelModal('Cancel Appointment?', 'Are you sure you want to cancel this appointment?', () => {
    const fd = new FormData();
    fd.append('id', id);
    fetch('cancel_appointment.php', { method: 'POST', body: fd })
      .then(r => r.json()).then(data => { if (data.status === 'success') location.reload(); });
  });
}

// Add doctor modal
function openAddDoctor()  { document.getElementById('addDoctorOverlay').classList.remove('hidden'); }
function closeAddDoctor() { document.getElementById('addDoctorOverlay').classList.add('hidden'); }

function showAddDocToast(msg, type) {
  const t = document.getElementById('addDocToast');
  t.textContent = msg; t.className = `toast ${type}`; t.classList.remove('hidden');
  setTimeout(() => t.classList.add('hidden'), 4000);
}

function submitAddDoctor() {
  const name  = document.getElementById('docName').value.trim();
  const spec  = document.getElementById('docSpec').value.trim();
  const exp   = document.getElementById('docExp').value;
  const fee   = document.getElementById('docFee').value;
  const avail = document.getElementById('docAvail').value.trim();
  if (!name||!spec||!exp||!fee||!avail) { showAddDocToast('All fields are required.','bad'); return; }

  document.getElementById('addDocTxt').classList.add('hidden');
  document.getElementById('addDocLoad').classList.remove('hidden');

  const fd = new FormData();
  fd.append('name',name);fd.append('specialization',spec);
  fd.append('experience',exp);fd.append('fee',fee);fd.append('availability',avail);
  fetch('add_doctor.php', { method:'POST', body:fd })
    .then(r => r.json())
    .then(data => {
      document.getElementById('addDocTxt').classList.remove('hidden');
      document.getElementById('addDocLoad').classList.add('hidden');
      if (data.status==='success') { showAddDocToast('✓ Doctor added!','ok'); setTimeout(()=>location.reload(),1500); }
      else showAddDocToast('✗ '+data.message,'bad');
    });
}

// Delete doctor
function deleteDoctor(id, name) {
  showDelModal('Delete Doctor?', `Are you sure you want to delete "${name}"? All their appointments will also be removed.`, () => {
    const fd = new FormData(); fd.append('id', id);
    fetch('delete_doctor.php', { method:'POST', body:fd })
      .then(r=>r.json()).then(data => { if (data.status==='success') location.reload(); });
  });
}

// Delete user
function deleteUser(id, name) {
  showDelModal('Delete User?', `Are you sure you want to delete "${name}"? This cannot be undone.`, () => {
    const fd = new FormData(); fd.append('id', id);
    fetch('delete_user.php', { method:'POST', body:fd })
      .then(r=>r.json()).then(data => { if (data.status==='success') location.reload(); });
  });
}

// Generic delete modal
let delCallback = null;
function showDelModal(title, sub, callback) {
  document.getElementById('delTitle').textContent = title;
  document.getElementById('delSub').textContent   = sub;
  delCallback = callback;
  document.getElementById('delOverlay').classList.remove('hidden');
}
function closeDelModal() {
  delCallback = null;
  document.getElementById('delOverlay').classList.add('hidden');
}
document.getElementById('delConfirmBtn').addEventListener('click', () => {
  if (delCallback) { delCallback(); closeDelModal(); }
});
</script>
</body>
</html>
