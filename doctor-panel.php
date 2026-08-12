<?php
require_once __DIR__ . '/hms.php';
hms_require_role('doctor');

$con = hms_db();
$doctor = $_SESSION['doctor_username'];
$flash = hms_flash();

if (isset($_GET['cancel'])) {
    $appointmentId = (int) $_GET['cancel'];
    $stmt = $con->prepare('UPDATE appointmenttb SET doctorStatus = 0 WHERE ID = ? AND doctor = ?');
    $stmt->bind_param('is', $appointmentId, $doctor);
    $stmt->execute();
    hms_flash('Appointment cancelled.');
    hms_redirect('doctor-panel.php');
}

$stmt = $con->prepare('SELECT ID, pid, fname, lname, gender, email, contact, appdate, apptime, userStatus, doctorStatus FROM appointmenttb WHERE doctor = ? ORDER BY appdate DESC, apptime DESC');
$stmt->bind_param('s', $doctor);
$stmt->execute();
$appointments = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Doctor Dashboard - Sneha Health Care</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>
    <header class="topbar">
        <a class="brand brand-with-mark" href="doctor-panel.php">
            <span class="hospital-mark small" aria-hidden="true"></span>
            <span>Sneha Health Care</span>
        </a>
        <nav>
            <span>Dr. <?php echo hms_escape($doctor); ?></span>
            <a href="logout.php">Logout</a>
        </nav>
    </header>

    <main class="dashboard">
        <?php if ($flash): ?>
            <div class="notice <?php echo hms_escape($flash['type']); ?>"><?php echo hms_escape($flash['message']); ?></div>
        <?php endif; ?>

        <section class="panel">
            <h1>My Appointments</h1>
            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Patient</th>
                            <th>Gender</th>
                            <th>Email</th>
                            <th>Contact</th>
                            <th>Date</th>
                            <th>Time</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!$appointments): ?>
                            <tr><td colspan="9">No appointments found.</td></tr>
                        <?php endif; ?>
                        <?php foreach ($appointments as $appointment): ?>
                            <tr>
                                <td><?php echo hms_escape($appointment['ID']); ?></td>
                                <td><?php echo hms_escape($appointment['fname'] . ' ' . $appointment['lname']); ?></td>
                                <td><?php echo hms_escape($appointment['gender']); ?></td>
                                <td><?php echo hms_escape($appointment['email']); ?></td>
                                <td><?php echo hms_escape($appointment['contact']); ?></td>
                                <td><?php echo hms_escape($appointment['appdate']); ?></td>
                                <td><?php echo hms_escape(substr($appointment['apptime'], 0, 5)); ?></td>
                                <td><?php echo hms_escape(hms_status_label($appointment)); ?></td>
                                <td>
                                    <?php if ((int) $appointment['userStatus'] === 1 && (int) $appointment['doctorStatus'] === 1): ?>
                                        <a class="danger-link" href="doctor-panel.php?cancel=<?php echo hms_escape($appointment['ID']); ?>">Cancel</a>
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
