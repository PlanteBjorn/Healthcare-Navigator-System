<?php

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $username = trim($_POST["username"] ?? "");
    $email = trim($_POST["email"] ?? "");
    $password = $_POST["password"] ?? "";
    $confirm_password = $_POST["confirm_password"] ?? "";

    if (
        empty($username) ||
        empty($email) ||
        empty($password) ||
        empty($confirm_password)
    ) {

        echo "Please fill in all fields.";

    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {

        echo "Please enter a valid email address.";

    } elseif ($password != $confirm_password) {

        echo "Passwords do not match.";

    } else {

        echo "<h2>Registration Successful!</h2>";

        echo "<p>Welcome, " .
             htmlspecialchars($username, ENT_QUOTES, 'UTF-8') .
             "!</p>";

        echo "<p>Your form has been submitted successfully.</p>";

    }

} else {

    echo "Invalid request.";

}

?>