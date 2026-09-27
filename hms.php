<?php
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

function hms_start_session()
{
    if (session_status() !== PHP_SESSION_ACTIVE) {
        session_start();
    }
}

function hms_db()
{
    static $con = null;

    if ($con === null) {
        $con = new mysqli('localhost', 'root', '', 'myhmsdb');
        $con->set_charset('utf8mb4');
        hms_ensure_schema($con);
    }

    return $con;
}

function hms_ensure_schema($con)
{
    static $done = false;

    if ($done) {
        return;
    }

    $done = true;

    $result = $con->query("SHOW COLUMNS FROM doctor_table LIKE 'available_days'");
    if ($result->num_rows === 0) {
        $con->query("ALTER TABLE doctor_table ADD COLUMN available_days varchar(30) NOT NULL DEFAULT '1,2,3,4,5', ADD COLUMN available_start time NOT NULL DEFAULT '09:00:00', ADD COLUMN available_end time NOT NULL DEFAULT '17:00:00'");
    }

    $result = $con->query("SHOW INDEX FROM appointment_table WHERE Key_name = 'doctor_slot_status'");
    if ($result->num_rows === 0) {
        $con->query('ALTER TABLE appointment_table ADD INDEX doctor_slot_status (doctor, appdate, apptime, userStatus, doctorStatus)');
    }
}

function hms_escape($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function hms_redirect($path)
{
    header('Location: ' . $path);
    exit;
}

function hms_require_role($role)
{
    hms_start_session();

    if (empty($_SESSION['role']) || $_SESSION['role'] !== $role) {
        hms_redirect('index.php');
    }
}

function hms_flash($message = null, $type = 'success')
{
    hms_start_session();

    if ($message !== null) {
        $_SESSION['flash'] = ['message' => $message, 'type' => $type];
        return null;
    }

    if (empty($_SESSION['flash'])) {
        return null;
    }

    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $flash;
}

function hms_post($key, $default = '')
{
    return isset($_POST[$key]) ? trim($_POST[$key]) : $default;
}

function hms_get_doctors()
{
    $stmt = hms_db()->prepare('SELECT username, email, spec, docFees, available_days, available_start, available_end FROM doctor_table ORDER BY username');
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
}

function hms_weekday_options()
{
    return [
        0 => 'Sunday',
        1 => 'Monday',
        2 => 'Tuesday',
        3 => 'Wednesday',
        4 => 'Thursday',
        5 => 'Friday',
        6 => 'Saturday',
    ];
}

function hms_normalize_available_days($days)
{
    if (!is_array($days)) {
        $days = explode(',', (string) $days);
    }

    $clean = [];
    foreach ($days as $day) {
        $day = trim((string) $day);
        if ($day !== '' && ctype_digit($day)) {
            $dayNumber = (int) $day;
            if ($dayNumber >= 0 && $dayNumber <= 6) {
                $clean[$dayNumber] = true;
            }
        }
    }

    $cleanDays = array_keys($clean);
    sort($cleanDays);
    return implode(',', $cleanDays);
}

function hms_normalize_time($time)
{
    $time = trim((string) $time);

    if (!preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $time)) {
        return '';
    }

    $parts = explode(':', $time);
    $hour = (int) $parts[0];
    $minute = (int) $parts[1];
    $second = isset($parts[2]) ? (int) $parts[2] : 0;

    if ($hour < 0 || $hour > 23 || $minute < 0 || $minute > 59 || $second < 0 || $second > 59) {
        return '';
    }

    return sprintf('%02d:%02d:00', $hour, $minute);
}

function hms_time_to_minutes($time)
{
    $time = hms_normalize_time($time);
    if ($time === '') {
        return null;
    }

    $parts = explode(':', $time);
    return ((int) $parts[0] * 60) + (int) $parts[1];
}

function hms_doctor_available_days($doctor)
{
    $days = hms_normalize_available_days($doctor['available_days'] ?? '');
    if ($days === '') {
        return [];
    }

    return array_map('intval', explode(',', $days));
}

function hms_format_doctor_availability($doctor)
{
    $weekdayOptions = hms_weekday_options();
    $dayNames = [];

    foreach (hms_doctor_available_days($doctor) as $day) {
        $dayNames[] = $weekdayOptions[$day];
    }

    $days = $dayNames ? implode(', ', $dayNames) : 'No days selected';
    $start = substr(hms_normalize_time($doctor['available_start'] ?? ''), 0, 5);
    $end = substr(hms_normalize_time($doctor['available_end'] ?? ''), 0, 5);

    return $days . ' from ' . $start . ' to ' . $end;
}

function hms_is_doctor_available($doctor, $appdate, $apptime)
{
    $date = DateTime::createFromFormat('Y-m-d', $appdate);
    $dateErrors = DateTime::getLastErrors();
    if (!$date || ($dateErrors !== false && ($dateErrors['warning_count'] > 0 || $dateErrors['error_count'] > 0)) || $date->format('Y-m-d') !== $appdate) {
        return false;
    }

    $selectedDay = (int) $date->format('w');
    if (!in_array($selectedDay, hms_doctor_available_days($doctor), true)) {
        return false;
    }

    $selectedTime = hms_time_to_minutes($apptime);
    $startTime = hms_time_to_minutes($doctor['available_start'] ?? '');
    $endTime = hms_time_to_minutes($doctor['available_end'] ?? '');

    if ($selectedTime === null || $startTime === null || $endTime === null || $startTime >= $endTime) {
        return false;
    }

    return $selectedTime >= $startTime && $selectedTime <= $endTime && (($selectedTime - $startTime) % 30) === 0;
}

function hms_status_label($appointment)
{
    if ((int) $appointment['userStatus'] === 1 && (int) $appointment['doctorStatus'] === 1) {
        return 'Active';
    }

    if ((int) $appointment['userStatus'] === 0) {
        return 'Cancelled by patient';
    }

    return 'Cancelled by doctor';
}
?>
