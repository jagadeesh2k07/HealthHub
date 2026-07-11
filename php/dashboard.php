<?php
session_start();
include '../db.php';

if (!isset($_SESSION['user_id'])) { header("Location: ../login.html"); exit(); }
if ($_SESSION['role'] === 'admin') { header("Location: admin.php"); exit(); }

$uid = $_SESSION['user_id'];

// Fetch user
$stmt = mysqli_prepare($conn, "SELECT * FROM users WHERE id = ?");
mysqli_stmt_bind_param($stmt, 'i', $uid);
mysqli_stmt_execute($stmt);
$user = mysqli_fetch_assoc(mysqli_stmt_get_result($stmt));

// Fetch appointments
$appts = mysqli_query($conn, "SELECT a.*, d.name AS doctor_name, d.specialization, d.fee
    FROM appointments a
    JOIN doctors d ON a.doctor_id = d.id
    WHERE a.patient_id = $uid
    ORDER BY a.appointment_date DESC, a.appointment_time DESC");

// Stats
$total    = mysqli_num_rows($appts);
$pending  = 0; $confirmed = 0; $cancelled = 0;
$apptList = [];
mysqli_data_seek($appts, 0);
while ($r = mysqli_fetch_assoc($appts)) {
    $apptList[] = $r;
    if ($r['status'] === 'pending')   $pending++;
    if ($r['status'] === 'confirmed') $confirmed++;
    if ($r['status'] === 'cancelled') $cancelled++;
}

// Fetch doctors for booking
$doctors = mysqli_query($conn, "SELECT * FROM doctors ORDER BY name ASC");
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Dashboard — HealthHub</title>
  <link rel="stylesheet" href="../css/style.css"/>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap" rel="stylesheet">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css"/>
  <style>
    body { background: var(--bg); }
    /* BOOK MODAL */
    .book-overlay {
      position:fixed;inset:0;z-index:200;
      display:flex;align-items:center;justify-content:center;padding:20px;
      background:rgba(15,23,42,0.6);backdrop-filter:blur(8px);
    }
    .book-overlay.hidden { display:none!important; }
    .book-card {
      background:white;border-radius:20px;padding:36px 32px;
      max-width:480px;width:100%;box-shadow:var(--shadow-lg);
      border:1px solid var(--border);position:relative;
      animation:modalPop 0.35s cubic-bezier(.34,1.4,.64,1) both;
    }
    @keyframes modalPop{from{opacity:0;transform:scale(0.88)}to{opacity:1;transform:scale(1)}}
    .book-title{font-size:1.2rem;font-weight:800;color:var(--text);margin-bottom:6px;}
    .book-sub{font-size:13px;color:var(--text2);margin-bottom:22px;}
    .book-field{margin-bottom:16px;}
    .book-field label{display:block;font-size:13px;font-weight:600;color:var(--text);margin-bottom:6px;}
    .book-field select, .book-field input{
      width:100%;padding:11px 14px;background:var(--bg);
      border:1.5px solid var(--border);border-radius:10px;
      font-family:var(--font);font-size:14px;color:var(--text);outline:none;
      transition:border-color var(--ease);
    }
    .book-field select:focus,.book-field input:focus{border-color:var(--teal);box-shadow:0 0 0 3px var(--teal-dim);}
    .book-field select option{background:white;}
    .time-slots{display:flex;flex-wrap:wrap;gap:8px;margin-top:6px;}
    .tslot{
      padding:7px 14px;border-radius:8px;font-size:13px;font-weight:500;
      background:var(--bg2);border:1.5px solid var(--border);color:var(--text2);
      cursor:pointer;transition:all var(--ease);
    }
    .tslot:hover{border-color:var(--teal);color:var(--teal);}
    .tslot.selected{background:var(--teal-dim);border-color:var(--teal);color:var(--teal);font-weight:600;}
    .cancel-note{
      background:#fffbeb;border:1px solid #fde68a;border-radius:8px;
      padding:10px 14px;font-size:12px;color:#92400e;margin-bottom:16px;
      display:flex;align-items:flex-start;gap:8px;
    }
    .cancel-note i{color:#f59e0b;margin-top:1px;flex-shrink:0;}
    /* profile card */
    .profile-card{
      display:flex;align-items:center;gap:18px;
      background:white;border:1px solid var(--border);border-radius:var(--radius);
      padding:24px;box-shadow:var(--shadow);margin-bottom:24px;
    }
    .profile-avatar-lg{
      width:64px;height:64px;border-radius:50%;
      background:var(--teal-dim);border:3px solid var(--teal-border);
      display:flex;align-items:center;justify-content:center;
      font-size:24px;color:var(--teal);flex-shrink:0;
    }
    .profile-info h2{font-size:1.1rem;font-weight:700;color:var(--text);}
    .profile-info p{font-size:13px;color:var(--text2);margin-top:2px;}
    .profile-info .role-tag{
      display:inline-block;margin-top:6px;
      padding:2px 10px;border-radius:20px;font-size:11px;font-weight:600;
      background:var(--teal-dim);color:var(--teal);border:1px solid var(--teal-border);
    }
  </style>
</head>
<body>

<!-- NAV -->
<nav class="dash-nav">
  <div class="dash-nav-inner">
    <a href="../index.html" class="dash-logo"><i class="fas fa-heartbeat"></i> HealthHub</a>
    <div class="dash-nav-right">
      <span class="dash-user">Hello, <strong><?= htmlspecialchars($user['first_name']) ?> 👋</strong></span>
      <a href="logout.php" class="btn-logout"><i class="fas fa-sign-out-alt"></i> Logout</a>
    </div>
  </div>
</nav>

<div class="dash-main">

  <!-- PROFILE -->
  <div class="profile-card">
    <div class="profile-avatar-lg"><i class="fas fa-user"></i></div>
    <div class="profile-info">
      <h2><?= htmlspecialchars($user['first_name'].' '.$user['last_name']) ?></h2>
      <p><?= htmlspecialchars($user['email']) ?><?= $user['phone'] ? ' · '.$user['phone'] : '' ?></p>
      <span class="role-tag"><i class="fas fa-user-injured"></i> Patient</span>
    </div>
    <button class="btn-primary" style="margin-left:auto" onclick="openBookModal()">
      <i class="fas fa-plus"></i> Book Appointment
    </button>
  </div>

  <!-- STATS -->
  <div class="stats-grid">
    <div class="stat-card">
      <div class="stat-card-icon"><i class="fas fa-calendar-days"></i></div>
      <div class="stat-card-info"><h3><?= $total ?></h3><p>Total Appointments</p></div>
    </div>
    <div class="stat-card">
      <div class="stat-card-icon" style="background:#fffbeb"><i class="fas fa-clock" style="color:#d97706"></i></div>
      <div class="stat-card-info"><h3><?= $pending ?></h3><p>Pending</p></div>
    </div>
    <div class="stat-card">
      <div class="stat-card-icon" style="background:var(--ok-bg)"><i class="fas fa-check-circle" style="color:var(--ok)"></i></div>
      <div class="stat-card-info"><h3><?= $confirmed ?></h3><p>Confirmed</p></div>
    </div>
    <div class="stat-card">
      <div class="stat-card-icon" style="background:var(--err-bg)"><i class="fas fa-times-circle" style="color:var(--err)"></i></div>
      <div class="stat-card-info"><h3><?= $cancelled ?></h3><p>Cancelled</p></div>
    </div>
  </div>

  <!-- APPOINTMENTS TABLE -->
  <div class="section-card">
    <div class="section-card-title"><i class="fas fa-calendar-check"></i> My Appointments</div>

    <!-- FILTER BAR -->
    <div class="filter-bar">
      <div class="search-wrap">
        <i class="fas fa-search"></i>
        <input type="text" class="search-input" id="searchInput" placeholder="Search doctor or specialization…" oninput="filterTable()"/>
      </div>
      <select class="filter-select" id="statusFilter" onchange="filterTable()">
        <option value="">All Status</option>
        <option value="pending">Pending</option>
        <option value="confirmed">Confirmed</option>
        <option value="cancelled">Cancelled</option>
      </select>
    </div>

    <?php if (empty($apptList)): ?>
      <div style="text-align:center;padding:40px;color:var(--text2);">
        <i class="fas fa-calendar-xmark" style="font-size:40px;color:var(--text3);margin-bottom:14px;display:block"></i>
        <p style="font-size:15px;font-weight:600;color:var(--text);margin-bottom:6px">No appointments yet</p>
        <p style="font-size:13px">Book your first appointment with a doctor!</p>
        <button class="btn-primary" style="margin-top:16px" onclick="openBookModal()"><i class="fas fa-plus"></i> Book Now</button>
      </div>
    <?php else: ?>
    <table class="data-table" id="apptTable">
      <thead>
        <tr>
          <th>#</th>
          <th>Doctor</th>
          <th>Specialization</th>
          <th>Date</th>
          <th>Time</th>
          <th>Fee</th>
          <th>Status</th>
          <th>Action</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($apptList as $i => $a): ?>
        <tr data-status="<?= $a['status'] ?>" data-search="<?= strtolower($a['doctor_name'].' '.$a['specialization']) ?>">
          <td><?= $i+1 ?></td>
          <td><strong><?= htmlspecialchars($a['doctor_name']) ?></strong></td>
          <td><?= htmlspecialchars($a['specialization']) ?></td>
          <td><?= date('d M Y', strtotime($a['appointment_date'])) ?></td>
          <td><?= date('h:i A', strtotime($a['appointment_time'])) ?></td>
          <td>₹<?= number_format($a['fee'], 0) ?></td>
          <td>
            <span class="badge badge-<?= $a['status'] ?>">
              <?= ucfirst($a['status']) ?>
            </span>
            <?php if ($a['status'] === 'cancelled' && $a['cancelled_by']): ?>
              <br><small style="color:var(--text3);font-size:11px">by <?= $a['cancelled_by'] ?></small>
            <?php endif; ?>
          </td>
          <td>
            <?php
            $apptDT = strtotime($a['appointment_date'].' '.$a['appointment_time']);
            $canCancel = ($a['status'] !== 'cancelled') && ((time() + 4*3600) < $apptDT);
            ?>
            <?php if ($canCancel): ?>
              <button class="btn-sm btn-sm-red" onclick="confirmCancel(<?= $a['id'] ?>)">
                <i class="fas fa-times"></i> Cancel
              </button>
            <?php elseif ($a['status'] !== 'cancelled'): ?>
              <span style="font-size:11px;color:var(--text3)">Cannot cancel</span>
            <?php else: ?>
              <span style="color:var(--text3);font-size:12px">—</span>
            <?php endif; ?>
          </td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
    <?php endif; ?>
  </div>

</div>

<!-- BOOK APPOINTMENT MODAL -->
<div class="book-overlay hidden" id="bookOverlay">
  <div class="book-card">
    <button class="modal-close" onclick="closeBookModal()"><i class="fas fa-times"></i></button>
    <div class="book-title"><i class="fas fa-calendar-plus" style="color:var(--teal);margin-right:8px"></i>Book Appointment</div>
    <div class="book-sub">Choose a doctor, date and time slot</div>

    <div class="cancel-note">
      <i class="fas fa-info-circle"></i>
      <span>You can cancel your appointment up to <strong>4 hours before</strong> the scheduled time.</span>
    </div>

    <div class="book-field">
      <label>Select Doctor</label>
      <select id="bookDoctor" onchange="updateDoctorInfo()">
        <option value="">— Choose a doctor —</option>
        <?php mysqli_data_seek($doctors, 0); while ($d = mysqli_fetch_assoc($doctors)): ?>
        <option value="<?= $d['id'] ?>" data-spec="<?= htmlspecialchars($d['specialization']) ?>" data-fee="<?= $d['fee'] ?>" data-avail="<?= $d['availability'] ?>">
          Dr. <?= htmlspecialchars($d['name']) ?> — <?= htmlspecialchars($d['specialization']) ?>
        </option>
        <?php endwhile; ?>
      </select>
    </div>

    <div id="doctorInfo" class="hidden" style="background:var(--teal-dim);border:1px solid var(--teal-border);border-radius:10px;padding:12px 14px;margin-bottom:16px;font-size:13px;color:var(--teal-dark);">
    </div>

    <div class="book-field">
      <label>Appointment Date</label>
      <input type="date" id="bookDate" min="<?= date('Y-m-d') ?>"/>
    </div>

    <div class="book-field">
      <label>Select Time Slot</label>
      <div class="time-slots" id="timeSlots">
        <?php
        $slots = ['09:00','10:00','11:00','12:00','14:00','15:00','16:00','17:00'];
        foreach ($slots as $s):
        ?>
        <div class="tslot" onclick="selectSlot(this, '<?= $s ?>')"><?= date('h:i A', strtotime($s)) ?></div>
        <?php endforeach; ?>
      </div>
      <input type="hidden" id="bookTime"/>
    </div>

    <div class="toast hidden" id="bookToast"></div>

    <button class="btn-submit" style="margin-top:8px" onclick="submitBooking()">
      <span id="bookTxt"><i class="fas fa-calendar-check"></i> Confirm Booking</span>
      <span id="bookLoad" class="spin hidden"><i class="fas fa-circle-notch fa-spin"></i></span>
    </button>
  </div>
</div>

<!-- CANCEL CONFIRM MODAL -->
<div class="del-overlay hidden" id="cancelOverlay">
  <div class="del-card">
    <div class="del-icon"><i class="fas fa-calendar-xmark"></i></div>
    <div class="del-title">Cancel Appointment?</div>
    <div class="del-sub" id="cancelSub">Are you sure you want to cancel this appointment? This cannot be undone.</div>
    <div class="del-btns">
      <button class="btn-cancel" onclick="closeCancelModal()">Keep It</button>
      <button class="btn-confirm-del" id="cancelConfirmBtn"><i class="fas fa-times"></i> Yes, Cancel</button>
    </div>
  </div>
</div>

<script>
let cancelId = null;

function filterTable() {
  const q     = document.getElementById('searchInput').value.toLowerCase();
  const status= document.getElementById('statusFilter').value;
  document.querySelectorAll('#apptTable tbody tr').forEach(row => {
    const matchSearch = !q || row.dataset.search.includes(q);
    const matchStatus = !status || row.dataset.status === status;
    row.style.display = matchSearch && matchStatus ? '' : 'none';
  });
}

function openBookModal()  { document.getElementById('bookOverlay').classList.remove('hidden'); }
function closeBookModal() { document.getElementById('bookOverlay').classList.add('hidden'); }

function updateDoctorInfo() {
  const sel = document.getElementById('bookDoctor');
  const opt = sel.options[sel.selectedIndex];
  const info= document.getElementById('doctorInfo');
  if (!sel.value) { info.classList.add('hidden'); return; }
  info.classList.remove('hidden');
  info.innerHTML = `<strong>Specialization:</strong> ${opt.dataset.spec} &nbsp;|&nbsp; <strong>Fee:</strong> ₹${parseFloat(opt.dataset.fee).toFixed(0)} &nbsp;|&nbsp; <strong>Available:</strong> ${opt.dataset.avail}`;
}

function selectSlot(el, time) {
  document.querySelectorAll('.tslot').forEach(s => s.classList.remove('selected'));
  el.classList.add('selected');
  document.getElementById('bookTime').value = time + ':00';
}

function showBookToast(msg, type) {
  const t = document.getElementById('bookToast');
  t.textContent = msg; t.className = `toast ${type}`; t.classList.remove('hidden');
  setTimeout(() => t.classList.add('hidden'), 4000);
}

function submitBooking() {
  const doctorId = document.getElementById('bookDoctor').value;
  const date     = document.getElementById('bookDate').value;
  const time     = document.getElementById('bookTime').value;

  if (!doctorId) { showBookToast('Please select a doctor.', 'bad'); return; }
  if (!date)     { showBookToast('Please select a date.', 'bad'); return; }
  if (!time)     { showBookToast('Please select a time slot.', 'bad'); return; }

  document.getElementById('bookTxt').classList.add('hidden');
  document.getElementById('bookLoad').classList.remove('hidden');

  const fd = new FormData();
  fd.append('doctor_id', doctorId);
  fd.append('appointment_date', date);
  fd.append('appointment_time', time);

  fetch('book_appointment.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(data => {
      document.getElementById('bookTxt').classList.remove('hidden');
      document.getElementById('bookLoad').classList.add('hidden');
      if (data.status === 'success') {
        showBookToast('✓ Appointment booked!', 'ok');
        setTimeout(() => location.reload(), 1500);
      } else {
        showBookToast('✗ ' + data.message, 'bad');
      }
    });
}

function confirmCancel(id) {
  cancelId = id;
  document.getElementById('cancelOverlay').classList.remove('hidden');
}
function closeCancelModal() {
  cancelId = null;
  document.getElementById('cancelOverlay').classList.add('hidden');
}

document.getElementById('cancelConfirmBtn').addEventListener('click', function () {
  if (!cancelId) return;
  const fd = new FormData();
  fd.append('id', cancelId);
  fetch('cancel_appointment.php', { method: 'POST', body: fd })
    .then(r => r.json())
    .then(data => {
      if (data.status === 'success') location.reload();
      else alert('Error: ' + data.message);
    });
});

// Close modals on overlay click
document.getElementById('bookOverlay').addEventListener('click', function(e) {
  if (e.target === this) closeBookModal();
});
</script>
</body>
</html>
