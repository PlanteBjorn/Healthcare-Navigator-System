<?php

if ($_SERVER["REQUEST_METHOD"] == "POST") {

    $caretaker_id = trim($_POST["caretaker_id"] ?? "");
    $password = $_POST["password"] ?? "";

    if (empty($caretaker_id) || empty($password)) {

        echo "Please fill in all fields.";

    } else {

        $safe_id = htmlspecialchars(
            $caretaker_id,
            ENT_QUOTES,
            "UTF-8"
        );

        echo "<h2>Sign In Form Submitted!</h2>";
        echo "<p>Welcome, " . $safe_id . "!</p>";
        echo "<p>Your information has been received.</p>";
        echo "<p>This is a demo. Login authentication is not configured yet.</p>";

    }

} else {

    echo "Invalid request.";

}

?>