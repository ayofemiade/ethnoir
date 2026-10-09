<?php
/**
 * Ethnoir Contact Form Handler
 * 
 * DEPLOYMENT INSTRUCTIONS FOR HOSTINGER:
 * 1. This file must be uploaded to the `public_html/api/` directory (or equivalent public root).
 * 2. It expects a configuration file `contact-config.php` to exist one level ABOVE `public_html` 
 *    for security (e.g. `/home/u123456789/domains/ethnoir.com/contact-config.php`).
 * 3. It expects PHPMailer to be installed or uploaded. If using Hostinger File Manager, 
 *    download PHPMailer and upload the `src/` folder to `/home/u123456789/domains/ethnoir.com/PHPMailer/`.
 */

header('Content-Type: application/json');

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method Not Allowed']);
    exit;
}

// 1. Basic Abuse Protection (Honeypot)
if (!empty($_POST['website_url'])) {
    // If the honeypot is filled, silently reject but pretend it succeeded to trick bots.
    echo json_encode(['success' => true]);
    exit;
}

// 2. Load Configuration (Securely outside public web root if possible)
// Adjust this path for the actual Hostinger deployment structure.
$configPath = realpath(__DIR__ . '/../../contact-config.php'); 
if (!$configPath || !file_exists($configPath)) {
    http_response_code(500);
    // DO NOT expose path details to the client
    error_log("Ethnoir Form Error: Missing configuration file.");
    echo json_encode(['success' => false, 'message' => 'Server configuration error.']);
    exit;
}

$config = require $configPath;

// 3. Input Validation and Sanitization
$name = trim(filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING));
$email = trim(filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL));
$organization = trim(filter_input(INPUT_POST, 'organization', FILTER_SANITIZE_STRING));
$role = trim(filter_input(INPUT_POST, 'role', FILTER_SANITIZE_STRING));
$inquiryType = trim(filter_input(INPUT_POST, 'inquiry_type', FILTER_SANITIZE_STRING));
$description = trim(filter_input(INPUT_POST, 'description', FILTER_SANITIZE_STRING));
$privacyAck = isset($_POST['privacy_ack']) ? true : false;

$institutionalDetails = trim(filter_input(INPUT_POST, 'institutional_details', FILTER_SANITIZE_STRING));
$checkinIssue = trim(filter_input(INPUT_POST, 'checkin_issue', FILTER_SANITIZE_STRING));

// Basic required fields
if (empty($name) || empty($email) || empty($inquiryType) || empty($description)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Please complete all required fields.']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Please provide a valid email address.']);
    exit;
}

if (!$privacyAck) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'You must acknowledge the privacy notice.']);
    exit;
}

// Validate Categories
$allowedCategories = [
    'Institutional or venture-development challenge',
    'Collaboration or referral',
    'Venture Readiness Check-In support',
    'General inquiry'
];

if (!in_array($inquiryType, $allowedCategories)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid inquiry type selected.']);
    exit;
}

// Conditional Validation
if ($inquiryType === 'Institutional or venture-development challenge') {
    if (empty($organization) || empty($role) || empty($institutionalDetails)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Please provide organization, role, and institutional details.']);
        exit;
    }
} else if ($inquiryType === 'Venture Readiness Check-In support') {
    if (empty($checkinIssue)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Please select the issue you experienced with the Check-In.']);
        exit;
    }
}

// 4. Construct Email Payload (Plain Text)
$subject = "Ethnoir Inquiry: " . $inquiryType;

$message = "You have received a new inquiry via the Ethnoir Contact Form.\n\n";
$message .= "--- SENDER DETAILS ---\n";
$message .= "Name: $name\n";
$message .= "Email: $email\n";
if (!empty($organization)) $message .= "Organization: $organization\n";
if (!empty($role)) $message .= "Role: $role\n";
$message .= "\n--- INQUIRY DETAILS ---\n";
$message .= "Type: $inquiryType\n";

if ($inquiryType === 'Institutional or venture-development challenge') {
    $message .= "Institutional Question/Decision:\n$institutionalDetails\n\n";
} else if ($inquiryType === 'Venture Readiness Check-In support') {
    $message .= "Check-In Issue: $checkinIssue\n\n";
}

$message .= "Description:\n$description\n\n";
$message .= "--- SYSTEM ---\n";
$message .= "Privacy Acknowledged: Yes\n";
$message .= "Timestamp: " . gmdate('Y-m-d H:i:s') . " UTC\n";

// Prevent Email Header Injection in the reply-to
$cleanEmail = str_replace(["\r", "\n"], '', $email);
$cleanName = str_replace(["\r", "\n"], '', $name);

// 5. Send Email Using PHPMailer (Authenticated SMTP)
// We check if PHPMailer classes are available where configured.
if (file_exists($config['phpmailer_path'] . '/PHPMailer.php')) {
    require_once $config['phpmailer_path'] . '/Exception.php';
    require_once $config['phpmailer_path'] . '/PHPMailer.php';
    require_once $config['phpmailer_path'] . '/SMTP.php';
    
    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = $config['smtp_host'];
        $mail->SMTPAuth   = true;
        $mail->Username   = $config['smtp_user'];
        $mail->Password   = $config['smtp_pass'];
        $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS; // Usually Port 465 on Hostinger
        $mail->Port       = $config['smtp_port'];

        // Recipients
        $mail->setFrom($config['sender_email'], 'Ethnoir Website Form');
        $mail->addAddress($config['recipient_email']);
        $mail->addReplyTo($cleanEmail, $cleanName);

        // Content
        $mail->isHTML(false);
        $mail->Subject = $subject;
        $mail->Body    = $message;

        $mail->send();
        
        echo json_encode(['success' => true]);
        
    } catch (Exception $e) {
        // Log safely, don't expose SMTP errors to the client
        error_log("Mailer Error: {$mail->ErrorInfo}");
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'The server could not send the message at this time.']);
    }
} else {
    // FALLBACK: If PHPMailer is not installed, log the error and use native mail() as a last resort,
    // though this is heavily discouraged due to deliverability issues.
    error_log("Ethnoir Warning: PHPMailer not found at {$config['phpmailer_path']}. Falling back to native mail().");
    
    $headers = "From: " . $config['sender_email'] . "\r\n";
    $headers .= "Reply-To: " . $cleanName . " <" . $cleanEmail . ">\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion();

    $success = @mail($config['recipient_email'], $subject, $message, $headers);
    
    if ($success) {
        echo json_encode(['success' => true]);
    } else {
        error_log("Ethnoir Error: Native mail() failed to send.");
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'The server could not send the message at this time.']);
    }
}
