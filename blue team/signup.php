<!--[Bjorn] Changed from html to php-->
<?php
include("HNSDatabase.php");
$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $first_name = $_POST["first_name"];
    $last_name = $_POST["last_name"];
    $username = $_POST["username"];
    $email = $_POST["email"];
    $password = $_POST["password"];
    $confirm_password = $_POST["confirm_password"];

    // password character requirement
    if (strlen($password) < 8) {
        $error = "Password must be at least 8 characters long.";
    } elseif ($password !== $confirm_password) {
        $error = "Passwords do not match.";
    } else {

        // check if username or email already used
        $sql = "SELECT HNS_user_id FROM hns_users
                WHERE HNS_username = ? OR Email = ?";

        $stmt = mysqli_prepare($hns_conn, $sql);
        mysqli_stmt_bind_param($stmt, "ss", $username, $email);
        mysqli_stmt_execute($stmt);
        $result = mysqli_stmt_get_result($stmt);

        if (mysqli_num_rows($result) > 0) {
            $error = "Username or email is already registered.";
        } else {
            $sql = "INSERT INTO hns_users
                    (HNS_first_name, HNS_last_name, HNS_username,
                     Email, HNS_password, HNS_user_type)
                    VALUES (?, ?, ?, ?, ?, 'Patient')";

            $stmt = mysqli_prepare($hns_conn, $sql);
            mysqli_stmt_bind_param(
                $stmt,
                "sssss",
                $first_name,
                $last_name,
                $username,
                $email,
                $password
            );

            if (mysqli_stmt_execute($stmt)) {
                header("Location: signin.php");
                exit();
            } else {
                $error = "Registration failed. Please try again.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HNS Patient Sign Up</title>
    <link rel="stylesheet" href="style.css">
</head>

<body class="home"> 
    <header>
        <div class="logo">
    <svg width="45" height="38" viewBox="0 0 50 45">
        <path d="M22 13 C15 0, 0 9, 7 22 L22 37 L37 22 C44 9, 29 0, 22 13"
              fill="none"
              stroke="#00B9C7"
              stroke-width="3"/>

        <path d="M32 18 C25 5, 12 14, 17 26 L32 41 L45 26 C52 14, 39 5, 32 18"
              fill="none"
              stroke="#00B9C7"
              stroke-width="3"/>
    </svg>
</div>

        <nav>
            <details>
                <summary>☰</summary>
                <a href="index.html">Home</a>
                <a href="signin.html">Sign In</a>
                <a href="signup.html">Sign Up</a>
            </details>
        </nav>
    </header>
    
    <main>
        <section class="signup">
            <h1>HNS</h1>
            <p>Make it Easier with HNS</p>
            <h3>Welcome to HNS!</h3>
            <p>Create your account and take the first step towards better healthcare.</p>

            <?php
            if ($error != "") {
                echo "<p style='color:red;'>" . htmlspecialchars($error) . "</p>";
            }

             if ($success != "") {
                echo "<p style='color:green;'>" . htmlspecialchars($success) . "</p>";
            }
            ?>

            <form action="" method="POST">
                <label for="first_name">First Name:</label>
                <input type="text" id="first_name" name="first_name" required>

                 <label for="last_name">Last Name:</label>
                <input type="text" id="last_name" name="last_name" required>

                <label for="username">Username:</label>
                <input type="text" id="username" name="username" required>

                <label for="email">Email Address:</label>
                <input type="email" id="email" name="email" required>

                <label for="password">Password:</label>
                <input type="password" id="password" name="password" required>

                <label for="confirm_password">Confirm Password:</label>
                <input type="password" id="confirm_password"
                       name="confirm_password" minlength="8" required>
     
                <button type="submit">Sign Up</button>
            </form>
        </section>

        <section class="signup-picture">
            <img src="patient2.png/Caretaker and Patient Login/image2.png" alt="Patient using a tablet">
        </section>
    </main>

    <footer>
        <p>HNS - Healthcare Navigator System</p>
        <p>SE 250 Blue Team</p>
    </footer>
    
</body>
</html>