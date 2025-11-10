<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json");

// 1. Check for POST data
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // 2. Collect and sanitize form data
    $name = filter_var($_POST['name'], FILTER_SANITIZE_STRING);
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $subject = filter_var($_POST['subject'], FILTER_SANITIZE_EMAIL);
    $message = filter_var($_POST['message'], FILTER_SANITIZE_STRING);

    // 3. Define the email details
    $to = "bcgowtham17@gmail.com"; // CHANGE THIS to your business email
    $subject = "New Contact Form Submission on " . $subject . $name;
    
    $body = "Name: " . $name . "\n";
    $body .= "Email: " . $email . "\n";
    $body .= "Subject: " . $subject . "\n";
    $body .= "Message: " . $message . "\n";

    $headers = "From: info@eirecosan.ie\r\n"; 
    $headers .= "Reply-To: " . $email . "\r\n";
    $headers .= "X-Mailer: PHP/" . phpversion();

    // 4. Send the email using PHP's built-in mail() function
    if (mail($to, $subject, $body, $headers)) {
        http_response_code(200);
        echo json_encode(["status" => "success", "message" => "Thank you! Your message has been sent."]);
    } else {
        http_response_code(500);
        echo json_encode(["status" => "error", "message" => "Oops! Something went wrong and we couldn't send your message."]);
    }
} else {
    // Handle non-POST requests
    http_response_code(405);
    echo json_encode(["status" => "error", "message" => "Method Not Allowed"]);
}
?>