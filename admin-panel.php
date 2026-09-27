<?php
require_once __DIR__ . '/hms.php';
hms_require_role('patient');

$con = hms_db();
$flash = hms_flash();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && hms_post('action') === 'book_appointment') {
    $doctor = hms_post('doctor');
    $appdate = hms_post('appdate');
    $apptime = hms_normalize_time(hms_post('apptime'));

    $stmt = $con->prepare('SELECT username, docFees, available_days, available_start, available_end FROM doctor_table WHERE username = ? LIMIT 1');
    $stmt->bind_param('s', $doctor);
    $stmt->execute();
    $doctorRow = $stmt->get_result()->fetch_assoc();

    if (!$doctorRow) {
        hms_flash('Please select a valid doctor.', 'error');
        hms_redirect('admin-panel.php');
    }

    if ($apptime === '') {
        hms_flash('Please select a valid appointment time.', 'error');
        hms_redirect('admin-panel.php');
    }

    if (strtotime($appdate . ' ' . $apptime) <= time()) {
        hms_flash('Please select a future date and time.', 'error');
        hms_redirect('admin-panel.php');
    }

    if (!hms_is_doctor_available($doctorRow, $appdate, $apptime)) {
        hms_flash('This doctor is available only on ' . hms_format_doctor_availability($doctorRow) . '.', 'error');
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
    $slotTaken = false;

    $con->query('LOCK TABLES appointment_table WRITE');
    try {
        $stmt = $con->prepare('SELECT ID FROM appointment_table WHERE doctor = ? AND appdate = ? AND apptime = ? AND userStatus = 1 AND doctorStatus = 1 LIMIT 1');
        $stmt->bind_param('sss', $doctor, $appdate, $apptime);
        $stmt->execute();

        if ($stmt->get_result()->num_rows > 0) {
            $slotTaken = true;
        } else {
            $stmt = $con->prepare('INSERT INTO appointment_table (pid, fname, lname, gender, email, contact, doctor, docFees, appdate, apptime, userStatus, doctorStatus) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)');
            $stmt->bind_param('issssssissii', $pid, $fname, $lname, $gender, $email, $contact, $doctor, $docFees, $appdate, $apptime, $userStatus, $doctorStatus);
            $stmt->execute();
        }
    } finally {
        $con->query('UNLOCK TABLES');
    }

    if ($slotTaken) {
        hms_flash('That doctor already has an appointment at this time.', 'error');
        hms_redirect('admin-panel.php');
    }

    hms_flash('Appointment booked successfully.');
    hms_redirect('admin-panel.php');
}

if (isset($_GET['cancel'])) {
    $appointmentId = (int) $_GET['cancel'];
    $pid = (int) $_SESSION['pid'];
    $stmt = $con->prepare('UPDATE appointment_table SET userStatus = 0 WHERE ID = ? AND pid = ?');
    $stmt->bind_param('ii', $appointmentId, $pid);
    $stmt->execute();
    hms_flash('Appointment cancelled.');
    hms_redirect('admin-panel.php');
}

$doctors = hms_get_doctors();
$doctorSchedules = [];
foreach ($doctors as $doctorRow) {
    $doctorSchedules[$doctorRow['username']] = [
        'days' => hms_doctor_available_days($doctorRow),
        'start' => substr(hms_normalize_time($doctorRow['available_start']), 0, 5),
        'end' => substr(hms_normalize_time($doctorRow['available_end']), 0, 5),
        'label' => hms_format_doctor_availability($doctorRow),
    ];
}

$bookedSlots = [];
$bookedRows = $con->query("SELECT doctor, appdate, TIME_FORMAT(apptime, '%H:%i') AS apptime FROM appointment_table WHERE userStatus = 1 AND doctorStatus = 1 AND appdate >= CURDATE()")->fetch_all(MYSQLI_ASSOC);
foreach ($bookedRows as $bookedRow) {
    $bookedSlots[$bookedRow['doctor']][$bookedRow['appdate']][] = $bookedRow['apptime'];
}

$pid = (int) $_SESSION['pid'];
$stmt = $con->prepare('SELECT ID, doctor, docFees, appdate, apptime, userStatus, doctorStatus FROM appointment_table WHERE pid = ? ORDER BY appdate DESC, apptime DESC');
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
                        <select name="doctor" id="doctor-select" required>
                            <option value="">Select doctor</option>
                            <?php foreach ($doctors as $doctor): ?>
                                <option value="<?php echo hms_escape($doctor['username']); ?>">
                                    <?php echo hms_escape($doctor['username'] . ' - ' . $doctor['spec'] . ' (Fee: ' . $doctor['docFees'] . ')'); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small id="doctor-availability" class="field-help">Select a doctor to see available days and time.</small>
                    </label>
                    <label>Date
                        <input type="date" name="appdate" id="appointment-date" min="<?php echo date('Y-m-d'); ?>" required>
                    </label>
                    <label>Time
                        <select name="apptime" id="appointment-time" required disabled>
                            <option value="">Select doctor and date first</option>
                        </select>
                        <small id="slot-help" class="field-help">Booked times will not appear here.</small>
                    </label>
                </div>
                <button type="submit" id="book-button" disabled>Book Appointment</button>
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
    <script>
        const doctorSchedules = <?php echo json_encode($doctorSchedules, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>;
        const bookedSlots = <?php echo json_encode($bookedSlots, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT); ?>;
        const doctorSelect = document.getElementById('doctor-select');
        const dateInput = document.getElementById('appointment-date');
        const timeSelect = document.getElementById('appointment-time');
        const availabilityText = document.getElementById('doctor-availability');
        const slotHelp = document.getElementById('slot-help');
        const bookButton = document.getElementById('book-button');

        function toMinutes(time) {
            const parts = time.split(':').map(Number);
            return (parts[0] * 60) + parts[1];
        }

        function toTime(minutes) {
            const hour = String(Math.floor(minutes / 60)).padStart(2, '0');
            const minute = String(minutes % 60).padStart(2, '0');
            return hour + ':' + minute;
        }

        function selectedWeekday(dateValue) {
            const parts = dateValue.split('-').map(Number);
            return new Date(parts[0], parts[1] - 1, parts[2]).getDay();
        }

        function isSelectedToday(dateValue) {
            const parts = dateValue.split('-').map(Number);
            const selectedDate = new Date(parts[0], parts[1] - 1, parts[2]);
            const today = new Date();
            return selectedDate.getFullYear() === today.getFullYear()
                && selectedDate.getMonth() === today.getMonth()
                && selectedDate.getDate() === today.getDate();
        }

        function setTimeOptions(options, disabledMessage) {
            timeSelect.innerHTML = '';

            if (options.length === 0) {
                const option = document.createElement('option');
                option.value = '';
                option.textContent = disabledMessage;
                timeSelect.appendChild(option);
                timeSelect.disabled = true;
                bookButton.disabled = true;
                return;
            }

            const placeholder = document.createElement('option');
            placeholder.value = '';
            placeholder.textContent = 'Select available time';
            timeSelect.appendChild(placeholder);

            options.forEach((time) => {
                const option = document.createElement('option');
                option.value = time;
                option.textContent = time;
                timeSelect.appendChild(option);
            });

            timeSelect.disabled = false;
            bookButton.disabled = false;
        }

        function refreshAppointmentSlots() {
            const doctor = doctorSelect.value;
            const date = dateInput.value;
            const schedule = doctorSchedules[doctor];

            if (!schedule) {
                availabilityText.textContent = 'Select a doctor to see available days and time.';
                slotHelp.textContent = 'Booked times will not appear here.';
                setTimeOptions([], 'Select doctor and date first');
                return;
            }

            availabilityText.textContent = 'Available: ' + schedule.label;

            if (!date) {
                slotHelp.textContent = 'Choose a date to load appointment times.';
                setTimeOptions([], 'Select date first');
                return;
            }

            if (!schedule.days.includes(selectedWeekday(date))) {
                slotHelp.textContent = 'This doctor is not available on the selected day.';
                setTimeOptions([], 'Doctor unavailable that day');
                return;
            }

            const bookedForDate = new Set(((bookedSlots[doctor] || {})[date] || []));
            const now = new Date();
            const currentMinutes = (now.getHours() * 60) + now.getMinutes();
            const slots = [];
            for (let minutes = toMinutes(schedule.start); minutes <= toMinutes(schedule.end); minutes += 30) {
                const time = toTime(minutes);
                if (!bookedForDate.has(time) && (!isSelectedToday(date) || minutes > currentMinutes)) {
                    slots.push(time);
                }
            }

            if (bookedForDate.size > 0) {
                slotHelp.textContent = 'Already booked: ' + Array.from(bookedForDate).sort().join(', ');
            } else {
                slotHelp.textContent = 'All shown times are currently available.';
            }

            setTimeOptions(slots, 'No free times on this date');
        }

        doctorSelect.addEventListener('change', refreshAppointmentSlots);
        dateInput.addEventListener('change', refreshAppointmentSlots);
        refreshAppointmentSlots();
    </script>
</body>
</html>
