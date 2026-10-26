<?php
/**
 * Ethnoir Contact Form Handler
 * 
 * HOSTINGER DEPLOYMENT (ZERO-CONFIGURATION):
 * - Works completely out-of-the-box using native PHP mail().
 * - When uploading your build to Hostinger, this file is automatically placed at `public_html/api/contact.php`.
 * - All inquiries are automatically routed to omichael@ethnoirconsulting.com.
 * - No external libraries (like PHPMailer) are required!
 * 
 * (Optional) If you ever want to route via custom external SMTP, you can place a `contact-config.php`
 * file one level above `public_html/`, but this is entirely optional.
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

// 2. Load Configuration (Securely outside public web root if possible, with fallbacks)
$config = [];
$candidatePaths = [
    realpath(__DIR__ . '/../../contact-config.php'), // 1 level above public_html
    realpath(__DIR__ . '/../contact-config.php'),    // in public_html
    realpath(__DIR__ . '/contact-config.php'),       // in public_html/api
];

foreach ($candidatePaths as $path) {
    if ($path && file_exists($path)) {
        $loadedConfig = require $path;
        if (is_array($loadedConfig)) {
            $config = $loadedConfig;
            break;
        }
    }
}

// Default settings
$recipientEmail = !empty($config['recipient_email']) ? $config['recipient_email'] : 'omichael@ethnoirconsulting.com';
$senderEmail    = !empty($config['sender_email']) ? $config['sender_email'] : 'omichael@ethnoirconsulting.com';
$phpmailerPath  = !empty($config['phpmailer_path']) ? $config['phpmailer_path'] : realpath(__DIR__ . '/../../PHPMailer/src');


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

// Validate Categories
$allowedCategories = [
    'Organization inquiry: a program or support challenge',
    'Organization inquiry',
    'Institutional or venture-development challenge',
    'Collaboration or referral',
    'Workshop, training or speaking',
    'Venture Readiness Check-In support',
    'General inquiry'
];

if (!in_array($inquiryType, $allowedCategories)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Invalid inquiry type selected.']);
    exit;
}

// Conditional Validation
if (
    $inquiryType === 'Organization inquiry' || 
    $inquiryType === 'Organization inquiry: a program or support challenge' || 
    $inquiryType === 'Institutional or venture-development challenge' ||
    $inquiryType === 'Workshop, training or speaking'
) {
    if (empty($organization) || empty($role)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Please provide your organization name and your role.']);
        exit;
    }
} else if ($inquiryType === 'Venture Readiness Check-In support') {
    if (empty($checkinIssue)) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Please select what happened when you used the Check-In.']);
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

if ($inquiryType === 'Venture Readiness Check-In support' && !empty($checkinIssue)) {
    $message .= "Check-In Issue: $checkinIssue\n\n";
}
if (!empty($institutionalDetails)) {
    $message .= "Institutional Context:\n$institutionalDetails\n\n";
}

$message .= "Description:\n$description\n\n";
$message .= "--- SYSTEM ---\n";
$message .= "Marketing Subscription: None created\n";
$message .= "Timestamp: " . gmdate('Y-m-d H:i:s') . " UTC\n";

// Prevent Email Header Injection in the reply-to
$cleanEmail = str_replace(["\r", "\n"], '', $email);
$cleanName = str_replace(["\r", "\n"], '', $name);

// 5. Send Email Using PHPMailer (Authenticated SMTP)
// We check if PHPMailer classes are available where configured.
if (!empty($phpmailerPath) && file_exists($phpmailerPath . '/PHPMailer.php') && !empty($config['smtp_host'])) {
    require_once $phpmailerPath . '/Exception.php';
    require_once $phpmailerPath . '/PHPMailer.php';
    require_once $phpmailerPath . '/SMTP.php';
    
    $mail = new \PHPMailer\PHPMailer\PHPMailer(true);

    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host       = $config['smtp_host'];
        $mail->SMTPAuth   = true;
        $mail->Username   = !empty($config['smtp_user']) ? $config['smtp_user'] : $senderEmail;
        $mail->Password   = !empty($config['smtp_pass']) ? $config['smtp_pass'] : '';
        $mail->SMTPSecure = \PHPMailer\PHPMailer\PHPMailer::ENCRYPTION_SMTPS; // Usually Port 465 on Hostinger
        $mail->Port       = !empty($config['smtp_port']) ? $config['smtp_port'] : 465;

        // Recipients
        $mail->setFrom($senderEmail, 'Ethnoir Website Form');
        $mail->addAddress($recipientEmail);
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
    // Standard Native PHP mail() delivery (Default & recommended for Hostinger)
    error_log("Ethnoir Notice: Using native PHP mail() to $recipientEmail.");
    
    // Properly format sender headers with UTF-8 support
    $fromHeader = "Ethnoir Inquiries <" . $senderEmail . ">";
    $replyToHeader = mb_encode_mimeheader($cleanName, "UTF-8") . " <" . $cleanEmail . ">";
    
    $headers = [];
    $headers[] = "MIME-Version: 1.0";
    $headers[] = "Content-Type: text/plain; charset=UTF-8; format=flowed";
    $headers[] = "Content-Transfer-Encoding: 8bit";
    $headers[] = "From: " . $fromHeader;
    $headers[] = "Reply-To: " . $replyToHeader;
    $headers[] = "X-Mailer: PHP/" . phpversion();
    
    $headerString = implode("\r\n", $headers);
    
    // Attempt sending with -f envelope parameter for Hostinger SPF/DMARC compatibility
    $success = @mail($recipientEmail, $subject, $message, $headerString, "-f " . $senderEmail);
    
    // If the -f parameter is restricted by server configuration, retry without it
    if (!$success) {
        $success = @mail($recipientEmail, $subject, $message, $headerString);
    }
    
    if ($success) {
        echo json_encode(['success' => true]);
    } else {
        error_log("Ethnoir Error: Native PHP mail() failed to send.");
        http_response_code(500);
        echo json_encode(['success' => false, 'message' => 'The server could not send the message at this time. Please try emailing directly to ' . $recipientEmail]);
    }
}
