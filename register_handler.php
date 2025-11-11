<?php
// Set up maximum error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// --- CRITICAL FIX: DYNAMICALLY SET INCLUDE PATH ---
// We explicitly add the custom PEAR directory to the PHP include path.
// This ensures that Mail/mime.php can be found, AND crucially, that Mail/mime.php
// can find its internal dependencies (like Mail/mimePart.php).
$custom_pear_path = '/home/e604649/php/';
set_include_path($custom_pear_path . PATH_SEPARATOR . get_include_path());

// Now, require_once with the standard relative path (which will be resolved by the new include path).
require_once 'Mail/mime.php';

// --- CONFIGURATION ---
// EMAIL SETTINGS
$recipient_email = "info@eirecosan.ie"; 
$sender_email    = "info@eirecosan.ie";    
$sender_name     = "EireCosan Registration Form"; // UPDATE APPLIED HERE
$redirect_url    = "register.html"; // Redirect back to the form page
$subject_prefix  = "New Job Registration Submission"; 
$eol = "\r\n"; // End of Line sequence required by email protocols

// --- Form Submission Logic ---
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    // Handling non-POST requests
    header("Location: " . $redirect_url . '?status=error&code=405');
    exit;
}

// 1. Collect and Sanitize Input Data
$fields = [
    'first_name'    => filter_input(INPUT_POST, 'first_name', FILTER_SANITIZE_FULL_SPECIAL_CHARS),
    'last_name'     => filter_input(INPUT_POST, 'last_name', FILTER_SANITIZE_FULL_SPECIAL_CHARS),
    'mobile'        => filter_input(INPUT_POST, 'mobile', FILTER_SANITIZE_FULL_SPECIAL_CHARS),
    'email'         => filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL),
    'city_of_birth' => filter_input(INPUT_POST, 'city_of_birth', FILTER_SANITIZE_FULL_SPECIAL_CHARS),
    'dob'           => filter_input(INPUT_POST, 'dob', FILTER_SANITIZE_FULL_SPECIAL_CHARS),
    'address_1'     => filter_input(INPUT_POST, 'address_1', FILTER_SANITIZE_FULL_SPECIAL_CHARS),
    'address_2'     => filter_input(INPUT_POST, 'address_2', FILTER_SANITIZE_FULL_SPECIAL_CHARS),
    'city'          => filter_input(INPUT_POST, 'city', FILTER_SANITIZE_FULL_SPECIAL_CHARS),
    'state'         => filter_input(INPUT_POST, 'state', FILTER_SANITIZE_FULL_SPECIAL_CHARS),
    'country'       => filter_input(INPUT_POST, 'country', FILTER_SANITIZE_FULL_SPECIAL_CHARS),
    'zip_code'      => filter_input(INPUT_POST, 'zip_code', FILTER_SANITIZE_FULL_SPECIAL_CHARS),
    'about_you'     => filter_input(INPUT_POST, 'about_you', FILTER_SANITIZE_FULL_SPECIAL_CHARS)
];

$full_name = trim($fields['first_name'] . ' ' . $fields['last_name']);
$final_subject = $subject_prefix . " from " . $full_name;

// 2. Handle File Upload (CV)
$upload_ok = true;
$file_info = $_FILES['cv_file'] ?? null;
$error_message = '';
$file_tmp = '';
$file_name = '';
$file_type = 'application/octet-stream';
$file_content = ''; // New variable to store binary content

if ($file_info && $file_info['error'] === UPLOAD_ERR_OK) {
    $file_size = $file_info['size'];
    $file_type = $file_info['type'] ?: 'application/octet-stream';
    $file_tmp  = $file_info['tmp_name'];
    $file_name = basename($file_info['name']);

    // Quantitative Check: Max size 5MB 
    if ($file_size > 5242880) {
        $upload_ok = false;
        $error_message = 'File size exceeds 5MB limit.';
    }
    
    // Quantitative Check: Allowed file types 
    $allowed_types = ['application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];
    if (!in_array($file_type, $allowed_types) && !preg_match('/\.(pdf|doc|docx)$/i', $file_name)) {
        $upload_ok = false;
        $error_message = 'Invalid file type. Only PDF, DOC, or DOCX are allowed.';
    }
    
    // --- FIX FOR 1KB FILE SIZE ISSUE ---
    if ($upload_ok) {
        // Read the actual content of the temporary file immediately
        $file_content = file_get_contents($file_tmp);
        if ($file_content === false) {
            $upload_ok = false;
            $error_message = 'Failed to read content of uploaded file due to server error.';
            error_log($error_message);
        }
    }
    // --- END FIX ---
    
    if (!$upload_ok) {
        error_log("CV Upload Failed for {$full_name}: {$error_message}");
    }

} else if ($file_info && $file_info['error'] !== UPLOAD_ERR_NO_FILE) {
    $upload_ok = false;
    $error_message = 'File upload failed with error code: ' . $file_info['error'];
    error_log("CV Upload Failed for {$full_name}: {$error_message}");
}


// 3. Construct HTML Email Body
$submission_time = date('Y-m-d H:i:s');
$client_ip = $_SERVER['REMOTE_ADDR'] ?? 'N/A';

$html_body = "
<html>
<body style=\"font-family: Arial, sans-serif; background-color: #f4f4f4; padding: 20px;\">
    <div style=\"max-width: 700px; margin: auto; background: #ffffff; padding: 30px; border-radius: 10px; box-shadow: 0 5px 15px rgba(0,0,0,0.1);\">
        <h2 style=\"color: #1a4f8b; border-bottom: 3px solid #4CAF50; padding-bottom: 10px;\">New Job Registration: {$full_name}</h2>
        
        <!-- Personal Information Table -->
        <h3 style=\"color: #4CAF50; margin-top: 25px;\">Personal Information</h3>
        <table width=\"100%\" cellpadding=\"10\" cellspacing=\"0\" style=\"border-collapse: collapse; margin-bottom: 20px;\">
            <tr style=\"background-color: #f8f9fa;\"><td style=\"font-weight: bold; width: 30%; border: 1px solid #dee2e6;\">Full Name:</td><td style=\"border: 1px solid #dee2e6;\">{$full_name}</td></tr>
            <tr><td style=\"font-weight: bold; background-color: #f8f9fa; border: 1px solid #dee2e6;\">Mobile:</td><td style=\"border: 1px solid #dee2e6;\">{$fields['mobile']}</td></tr>
            <tr style=\"background-color: #f8f9fa;\"><td style=\"font-weight: bold; border: 1px solid #dee2e6;\">Email:</td><td style=\"border: 1px solid #dee2e6;\">{$fields['email']}</td></tr>
            <tr><td style=\"font-weight: bold; background-color: #f8f9fa; border: 1px solid #dee2e6;\">City of Birth:</td><td style=\"border: 1px solid #dee2e6;\">{$fields['city_of_birth']}</td></tr>
            <tr style=\"background-color: #f8f9fa;\"><td style=\"font-weight: bold; border: 1px solid #dee2e6;\">Date of Birth:</td><td style=\"border: 1px solid #dee2e6;\">{$fields['dob']}</td></tr>
        </table>
        
        <!-- Address Table -->
        <h3 style=\"color: #4CAF50;\">Address</h3>
        <table width=\"100%\" cellpadding=\"10\" cellspacing=\"0\" style=\"border-collapse: collapse; margin-bottom: 20px;\">
            <tr style=\"background-color: #f8f9fa;\"><td style=\"font-weight: bold; width: 30%; border: 1px solid #dee2e6;\">Address Line 1:</td><td style=\"border: 1px solid #dee2e6;\">{$fields['address_1']}</td></tr>
            <tr><td style=\"font-weight: bold; background-color: #f8f9fa; border: 1px solid #dee2e6;\">Address Line 2:</td><td style=\"border: 1px solid #dee2e6;\">{$fields['address_2']}</td></tr>
            <tr style=\"background-color: #f8f9fa;\"><td style=\"font-weight: bold; border: 1px solid #dee2e6;\">City, State:</td><td style=\"border: 1px solid #dee2e6;\">{$fields['city']}, {$fields['state']}</td></tr>
            <tr><td style=\"font-weight: bold; background-color: #f8f9fa; border: 1px solid #dee2e6;\">Country, Zip:</td><td style=\"border: 1px solid #dee2e6;\">{$fields['country']}, {$fields['zip_code']}</td></tr>
        </table>

        <!-- Message/About You -->
        <h3 style=\"color: #4CAF50;\">Description About Applicant</h3>
        <div style=\"border: 1px solid #ced4da; padding: 15px; background-color: #fcfcfc; border-radius: 4px; white-space: pre-wrap; line-height: 1.5;\">
            {$fields['about_you']}
        </div>

        <p style=\"margin-top: 30px; font-size: 10px; color: #888;\">
            --- Metadata ---<br>
            Submitted: {$submission_time}<br>
            Client IP: {$client_ip}
        </p>
    </div>
</body>
</html>
";

if (!$upload_ok) {
    // Add warning if file failed to process
    $html_body = str_replace(
        '--- Metadata ---',
        "<span style='color: #d9534f; font-weight: bold;'>WARNING: CV NOT ATTACHED (Reason: {$error_message})</span><br>--- Metadata ---",
        $html_body
    );
}

// 4. Build Email using Mail_Mime
// Create a new Mail_Mime object
$mime = new Mail_Mime($eol);

// Set the HTML body
$mime->setHTMLBody($html_body);

// Add the attachment if the upload was successful and content was read
if ($upload_ok && !empty($file_content)) {
    // CRITICAL: Pass $file_content (the binary data) and explicitly set the fourth argument 
    // ($is_filename) to FALSE to tell Mail_Mime we are passing content, not a path.
    $mime->addAttachment($file_content, $file_type, $file_name, false, 'base64');
}

// Generate the message body and headers
$body = $mime->get(array('html_charset' => 'UTF-8', 'text_encoding' => '8bit'));
$headers = $mime->headers(array(
    'From'    => $sender_name . " <" . $sender_email . ">",
    'Reply-To' => $full_name . " <" . $fields['email'] . ">"
));

// 5. Send Email
// Note: We are using the native mail() function, passing the complete
// body and headers generated by Mail_Mime.
if (mail($recipient_email, $final_subject, $body, $headers)) {
    // Success: Redirect with status
    header("Location: " . $redirect_url . '?status=success');
    exit;
} else {
    // Failure: Log error and redirect with error status
    error_log("PEAR Mail_Mime message failed to send via native mail() for {$full_name}.");
    header("Location: " . $redirect_url . '?status=error&code=500'); // Internal Server Error
    exit;
}
?>