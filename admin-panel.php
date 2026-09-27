<?php
require_once __DIR__ . '/hms.php';
hms_require_role('patient');

$con = hms_db();
$flash = hms_flash();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && hms_post('action') === 'book_appointment') {
    $doctor = hms_post('doctor');
    $appdate = hms_post('appdate');
    $apptime = hms_post('apptime');

    $stmt = $con->prepare('SELECT docFees FROM doctb WHERE username = ? LIMIT 1');
    $stmt->bind_param('s', $doctor);
    $stmt->execute();
    $doctorRow = $stmt->get_result()->fetch_assoc();

    if (!$doctorRow) {
        hms_flash('Please select a valid doctor.', 'error');
        hms_redirect('admin-panel.php');
    }

    if (strtotime($appdate . ' ' . $apptime) <= time()) {
        hms_flash('Please select a future date and time.', 'error');
        hms_redirect('admin-panel.php');
    }

    $stmt = $con->prepare('SELECT ID FROM appointmenttb WHERE doctor = ? AND appdate = ? AND apptime = ? AND userStatus = 1 AND doctorStatus = 1 LIMIT 1');
    $stmt->bind_param('sss', $doctor, $appdate, $apptime);
    $stmt->execute();

    if ($stmt->get_result()->num_rows > 0) {
        hms_flash('That doctor already has an appointment at this time.', 'error');
        hms_redirect('admin-panel.php');
    }

    $pid = (int) $_SESSION['pid'];
    $fname = $_SESSION['fname'];
    $lname = $_SESSION['lname'];
    $gender = $_SESSION['gender'];
    $email = $_SESSION['email'];
    $contact = $_SESSION['contact'];
    $docFees = (int) $doctorRow['docFees'];
    $userStatus = 1;
    $doctorStatus = 1;

    $stmt = $con->prepare('INSERT INTO appointmenttb (pid, fname, lname, gender, email, contact, doctor, docFees, appdate, apptime, userStatus, doctorStatus) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
    $stmt->bind_param('issssssissii', $pid, $fname, $lname, $gender, $email, $contact, $doctor, $docFees, $appdate, $apptime, $userStatus, $doctorStatus);
    $stmt->execute();

    hms_flash('Appointment booked successfully.');
    hms_redirect('admin-panel.php');
}

if (isset($_GET['cancel'])) {
    $appointmentId = (int) $_GET['cancel'];
    $pid = (int) $_SESSION['pid'];
    $stmt = $con->prepare('UPDATE appointmenttb SET userStatus = 0 WHERE ID = ? AND pid = ?');
    $stmt->bind_param('ii', $appointmentId, $pid);
    $stmt->execute();
    hms_flash('Appointment cancelled.');
    hms_redirect('admin-panel.php');
}

$doctors = hms_get_doctors();
$pid = (int) $_SESSION['pid'];
$stmt = $con->prepare('SELECT ID, doctor, docFees, appdate, apptime, userStatus, doctorStatus FROM appointmenttb WHERE pid = ? ORDER BY appdate DESC, apptime DESC');
$stmt->bind_param('i', $pid);
$stmt->execute();
$appointments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Patient Dashboard - Sneha Health Care</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header class="topbar">
        <a class="brand brand-with-mark" href="admin-panel.php">
            <span class="hospital-mark small" aria-hidden="true"></span>
            <span>Sneha Health Care</span>
        </a>
        <nav>
            <span><?php echo hms_escape($_SESSION['patient_name']); ?></span>
            <a href="logout.php">Logout</a>
        </nav>
    </header>

    <main class="dashboard">
        <?php if ($flash): ?>
            <div class="notice <?php echo hms_escape($flash['type']); ?>"><?php echo hms_escape($flash['message']); ?></div>
        <?php endif; ?>

        <section class="panel">
            <h1>Book Appointment</h1>
            <form method="post" action="admin-panel.php">
                <input type="hidden" name="action" value="book_appointment">
                <div class="three-col">
                    <label>Doctor
                        <select name="doctor" required>
                            <option value="">Select doctor</option>
                            <?php foreach ($doctors as $doctor): ?>
                                <option value="<?php echo hms_escape($doctor['username']); ?>">
                                    <?php echo hms_escape($doctor['username'] . ' - ' . $doctor['spec'] . ' (Fee: ' . $doctor['docFees'] . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </label>
                    <label>Date
                        <input type="date" name="appdate" min="<?php echo date('Y-m-d'); ?>" required>
                    </label>
                    <label>Time
                        <input type="time" name="apptime" required>
                    </label>
                </div>
                <button type="submit">Book Appointment</button>
            </form>
        </section>

        <section class="panel">
            <h1>Appointment History</h1>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Doctor</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Fee</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!$appointments): ?>
                            <tr><td colspan="7">No appointments found.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($appointments as $appointment): ?>
                            <tr>
                                <td><?php echo hms_escape($appointment['ID']); ?></td>
                                <td><?php echo hms_escape($appointment['doctor']); ?></td>
                                <td><?php echo hms_escape($appointment['appdate']); ?></td>
                                <td><?php echo hms_escape(substr($appointment['apptime'], 0, 5)); ?></td>
                                <td><?php echo hms_escape($appointment['docFees']); ?></td>
                                <td><?php echo hms_escape(hms_status_label($appointment)); ?></td>
                                <td>
                                    <?php if ((int) $appointment['userStatus'] === 1 && (int) $appointment['doctorStatus'] === 1): ?>
                                        <a class="danger-link" href="admin-panel.php?cancel=<?php echo hms_escape($appointment['ID']); ?>">Cancel</a>
                                    <?php else: ?>
                                        -
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </section>
    </main>
</body>
</html>
