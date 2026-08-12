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
    }

    return $con;
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
    $stmt = hms_db()->prepare('SELECT username, email, spec, docFees FROM doctb ORDER BY username');
    $stmt->execute();
    return $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
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
