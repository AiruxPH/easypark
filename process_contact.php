<?php
// process_contact.php
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = filter_input(INPUT_POST, 'name', FILTER_SANITIZE_STRING);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $message = filter_input(INPUT_POST, 'message', FILTER_SANITIZE_STRING);

    if (empty($name) || empty($email) || empty($message)) {
        echo json_encode(['status' => 'error', 'message' => 'All fields are required.']);
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo json_encode(['status' => 'error', 'message' => 'Invalid email address.']);
        exit;
    }

    // Configuration:
    // IMPORTANT: On Hostinger/Live Servers, the 'From' email MUST be an actual email account created on that domain.
    // Example: if your site is 'easypark.com', this must be 'no-reply@easypark.com' or 'support@easypark.com'.
    // Using 'gmail.com' or non-existent domains here will cause the email to be blocked.
    $senderEmail = "no-reply@" . $_SERVER['SERVER_NAME']; // Defines sender as no-reply@yourdomain.com automatically
    // OR manually set it: $senderEmail = "admin@your-actual-domain.com";

    // Admin Email Configuration
    $adminEmail = "randythegreat000@gmail.com";
    $subjectAdmin = "New Contact Us Message from $name";
    $bodyAdmin = "You have received a new message from the EasyPark Contact Us form.\n\n" .
        "Name: $name\n" .
        "Email: $email\n" .
        "Message:\n$message\n\n" .
        "Date: " . date("Y-m-d H:i:s");

    // Header for Admin Email
    // 'From' must be the server email. 'Reply-To' is the user's email so you can hit reply.
    $headersAdmin = "From: EasyPark Site <$senderEmail>" . "\r\n" .
        "Reply-To: $email" . "\r\n" .
        "X-Mailer: PHP/" . phpversion();

    // User Acknowledgment Email Configuration
    $subjectUser = "We received your message - EasyPark";
    $bodyUser = "Hi $name,\n\n" .
        "Thank you for contacting EasyPark. This is an automated acknowledgment to let you know we have received your message.\n\n" .
        "We will get back to you as soon as possible.\n\n" .
        "Your Message:\n$message\n\n" .
        "Best Regards,\nEasyPark Team";

    // Header for User Email
    $headersUser = "From: EasyPark Support <$senderEmail>" . "\r\n" .
        "Reply-To: $adminEmail" . "\r\n" .
        "X-Mailer: PHP/" . phpversion();

    // Send Emails
    $sentAdmin = mail($adminEmail, $subjectAdmin, $bodyAdmin, $headersAdmin);

    // Attempt to send user acknowledgement even if admin mail fails, or depeding on logic.
    // Usually strict logic requires admin notification first.
    if ($sentAdmin) {
        $sentUser = mail($email, $subjectUser, $bodyUser, $headersUser);
        echo json_encode(['status' => 'success', 'message' => 'Message sent successfully! Check your email for confirmation.']);
    } else {
        // Fallback for local environments where mail() might fail without config
        // In production, you might want to log this error.
        echo json_encode(['status' => 'error', 'message' => 'Failed to send message. Please try again later (Server Error).']);
    }

} else {
    echo json_encode(['status' => 'error', 'message' => 'Invalid request method.']);
}
?>