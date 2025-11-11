<?php
// --- TEMPORARY DEBUGGING BLOCK: REMOVE AFTER FIXING 500 ERROR ---
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
// --- END DEBUGGING BLOCK ---

// Set headers for security and CORS
header("Access-Control-Allow-Origin: *");

// --- CONFIGURATION ---
$recipient_email = "roshyantonyp@gmail.com"; 
$sender_email    = "info@eirecosan.ie";    
$redirect_url    = "http://eirecosan.ie/contact-us.html"; 
// Updated prefix to match your desired wording
$subject_prefix  = "New Contact Form Submission"; 

// --- Form Submission Logic ---
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    // Handling non-POST requests: safe and secure
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Method Not Allowed"]);
    exit;
}

// 3. Collect and Sanitize Input Data
// NOTE: Replaced deprecated FILTER_SANITIZE_STRING with FILTER_SANITIZE_FULL_SPECIAL_CHARS
$name    = filter_var($_POST['name']    ?? '', FILTER_SANITIZE_FULL_SPECIAL_CHARS);
$email   = filter_var($_POST['email']   ?? '', FILTER_SANITIZE_EMAIL);
// Subject is now correctly filtered as a STRING equivalent
$subject = filter_var($_POST['subject'] ?? '', FILTER_SANITIZE_FULL_SPECIAL_CHARS); 
$message = filter_var($_POST['message'] ?? '', FILTER_SANITIZE_FULL_SPECIAL_CHARS);

// Basic Validation Check
if (strlen($name) < 1 || strlen($message) < 1 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    // Failure condition: Redirect with error status
    header("Location: " . $redirect_url . '?status=error');
    exit;
}

// 4. Construct HTML Email Body (for visual appeal)
$submission_time = date('Y-m-d H:i:s'); // Define the time variable here
// Final subject line constructed to match your desired text:
$final_subject = $subject_prefix . " on " . $subject . " by " . $name;
$client_ip = $_SERVER['REMOTE_ADDR'] ?? 'N/A';

$html_body = <<<HTML
<html>
<body style="font-family: Arial, sans-serif; background-color: #f4f4f4; padding: 20px;">
    <div style="max-width: 600px; margin: auto; background: #ffffff; padding: 30px; border-radius: 8px; box-shadow: 0 4px 8px rgba(0,0,0,0.1);">
        <h2 style="color: #007bff; border-bottom: 2px solid #007bff; padding-bottom: 10px;">New Eirecosan Contact Request</h2>
        
        <table width="100%" cellpadding="10" cellspacing="0" border="0" style="border-collapse: collapse;">
            <tr style="background-color: #f8f9fa;">
                <td style="font-weight: bold; width: 30%; border: 1px solid #dee2e6;">Name:</td>
                <td style="width: 70%; border: 1px solid #dee2e6;">{$name}</td>
            </tr>
            <tr>
                <td style="font-weight: bold; background-color: #f8f9fa; border: 1px solid #dee2e6;">Email:</td>
                <td style="border: 1px solid #dee2e6;">{$email}</td>
            </tr>
            <tr style="background-color: #f8f9fa;">
                <td style="font-weight: bold; border: 1px solid #dee2e6;">Subject:</td>
                <td style="border: 1px solid #dee2e6;">{$subject}</td>
            </tr>
        </table>

        <h3 style="margin-top: 25px; color: #333;">Message Details:</h3>
        <div style="border: 1px solid #ced4da; padding: 15px; background-color: #fcfcfc; border-radius: 4px; white-space: pre-wrap; line-height: 1.5;">
            {$message}
        </div>

        <p style="margin-top: 30px; font-size: 10px; color: #888;">
            --- Metadata ---<br>
            Submitted: {$submission_time}<br>
            Client IP: {$client_ip}
        </p>
    </div>
</body>
</html>
HTML;
// Removed problematic sprintf() call on line 82

// 5. Define Headers for HTML Email
$headers = "From: " . $sender_email . "\r\n"; 
$headers .= "Reply-To: " . $email . "\r\n";
$headers .= "MIME-Version: 1.0\r\n"; // CRITICAL: Enables multipart message
$headers .= "Content-Type: text/html; charset=UTF-8\r\n"; // CRITICAL: Sets format to HTML
$headers .= "X-Mailer: PHP/" . phpversion();

// 6. Send the email using PHP's built-in mail() function and handle redirect
if (mail($recipient_email, $final_subject, $html_body, $headers)) {
    // Success: Set HTTP 302 redirect header
    header("Location: " . $redirect_url . '?status=success');
    exit; // CRITICAL: Stop script execution immediately after sending header
} else {
    // Failure: Redirect with error status
    error_log("MAIL FAILURE: Simple mail() failed to send."); // Log failure for debugging
    header("Location: " . $redirect_url . '?status=error');
    exit; // CRITICAL: Stop script execution immediately after sending header
}
?>