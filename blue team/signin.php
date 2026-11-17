<!--[Bjorn] changed signin to a php file-->
<?php
session_start();
include("HNSDatabase.php");

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $username = $_POST["username"];
    $password = $_POST["password"];
    $sql = "SELECT * FROM hns_users
            WHERE HNS_username = ?
            AND HNS_user_type = 'Patient'";
    $stmt = mysqli_prepare($hns_conn, $sql);
    mysqli_stmt_bind_param($stmt, "s", $username);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    $user = mysqli_fetch_assoc($result);

    if ($user && $password == $user["HNS_password"]) {
        $_SESSION["HNS_user_id"] = $user["HNS_user_id"];
        $_SESSION["HNS_username"] = $user["HNS_username"];
        $_SESSION["HNS_first_name"] = $user["HNS_first_name"];
        $_SESSION["HNS_last_name"] = $user["HNS_last_name"];
        $_SESSION["HNS_user_type"] = $user["HNS_user_type"];
        header("Location: Patient Dashboard.php");
        exit();
    } else {
        $error = "Incorrect username or password!";
    }
}
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HNS Patient Sign In</title>
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
                
            </details>
        </nav>
    </header>

    <main>
        <section class="signin">
            <h1>HNS</h1>
            <p>Make it Easier with HNS</p>

            <h3>Welcome, Patient!</h3>

            <p>Your health matters. Stay connected and manage your healthcare easily with HNS.</p>

            <!-- had to adjust this -->
            <form method="POST" action="">

                <h4>Sign In</h4>

                <label for="username">Username:</label>
                <input type="text" id="username" name="username"
                       placeholder="HNS Username" required>

                <label for="password">Password:</label>
                <input type="password" id="password" name="password"
                       placeholder="Password" required>

                <?php
                if (isset($error)) {
                    echo "<p style='color:red;'>" . htmlspecialchars($error) . "</p>";
                }
                ?>

                <p><a href="#">Forgot password?</a></p>


                <button type="submit">Sign In</button>

                <div class="caretaker-option"> <!--[bjorn] moved this to be under sign-in-->
                    <a href="Caretaker%20signin.html">
                        Sign in as a Caretaker?
                    </a>
                </div>

            </form>
        </section>

        <section class="signin-picture">
            <img src="patient2.png/Caretaker and Patient Login/image2.png"
                 alt="Patient using a tablet">
        </section>
    </main>

    <footer>
        <p>HNS - Healthcare Navigator System</p>
        <p>SE 250 Blue Team</p>
    </footer>

<!--[Bjorn] got rid of temp javascript login-->
</body>
</html>
