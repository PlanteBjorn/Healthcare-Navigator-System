<!--BookAppointment.php-->
<?php
session_start();

// ensures only logged in patients and caretakers can access this page
if (!isset($_SESSION["HNS_user_id"]) ||
    !in_array($_SESSION["HNS_user_type"], ["Patient", "Caretaker"], true)) {
    header("Location: signin.php");
    exit();
}

// retrieves account
$user_id = (int) $_SESSION["HNS_user_id"];
$user_type = $_SESSION["HNS_user_type"];
$username = $_SESSION["HNS_username"];
$first_name = $_SESSION["HNS_first_name"];
$last_name = $_SESSION["HNS_last_name"];
?>

<?php
// connects to HNS database
include("HNSDatabase.php");

// connects to hospital database
$hospital_conn = new mysqli("localhost", "root", "", "hospital_database1");

if ($hospital_conn->connect_error) {
    die("Unable to connect to hospital database.");
}

$hospital_conn->set_charset("utf8mb4");

// creates CSRF token
if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

$csrf_token = $_SESSION["csrf_token"];

$error = "";
$success = "";

// identifies the patient
$patient_id = null;
$selected_patient_user_id = null;

if ($user_type === "Patient") {

    // retrieves patient's hospital ID
    $sql = "SELECT hospital_patient_id
            FROM hns_users
            WHERE HNS_user_id = ?
              AND HNS_user_type = 'Patient'";

    $stmt = mysqli_prepare($hns_conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $user_id);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);
    $account = mysqli_fetch_assoc($result);

    $patient_id = $account["hospital_patient_id"] ?? null;

    mysqli_stmt_close($stmt);

} else {

    // retrieves selected patient from caretaker dashboard
    $selected_patient_user_id = filter_input(
        INPUT_GET,
        "patient",
        FILTER_VALIDATE_INT
    );

    if ($selected_patient_user_id) {

        // verifies active caretaker connection
        $sql = "SELECT u.hospital_patient_id
                FROM hns_caretaker_patients cp
                JOIN hns_users u
                    ON cp.patient_user_id = u.HNS_user_id
                WHERE cp.caretaker_user_id = ?
                  AND cp.patient_user_id = ?
                  AND cp.status = 'Active'
                  AND u.HNS_user_type = 'Patient'";

        $stmt = mysqli_prepare($hns_conn, $sql);

        mysqli_stmt_bind_param(
            $stmt,
            "ii",
            $user_id,
            $selected_patient_user_id
        );

        mysqli_stmt_execute($stmt);

        $result = mysqli_stmt_get_result($stmt);
        $account = mysqli_fetch_assoc($result);

        $patient_id = $account["hospital_patient_id"] ?? null;

        mysqli_stmt_close($stmt);
    }
}

// checks whether patient has a hospital account
if (empty($patient_id)) {
    $error = "Patient hospital account unavailable or caretaker access not authorized.";
}

// retrieves doctors
$doctors = false;

// retrieves insurance provider
$provider_name = "Insurance";

$sql = "SELECT provider_name
        FROM insurance_providers
        WHERE provider_id = 1";

$result = mysqli_query($hospital_conn, $sql);

if ($result && mysqli_num_rows($result) > 0) {
    $provider = mysqli_fetch_assoc($result);
    $provider_name = $provider["provider_name"];
}

// retrieves doctors
if (!empty($patient_id)) {

    $sql = "SELECT
                staff.staff_id,
                staff.first_name,
                staff.last_name,
                staff.job_title,
                departments.department_id,
                departments.department_name,
                CASE
                    WHEN insurance_staff.staff_id IS NOT NULL
                    THEN 1
                    ELSE 0
                END AS insurance_covered

            FROM staff

            INNER JOIN staff_department_assignments
                ON staff.staff_id = staff_department_assignments.staff_id

            INNER JOIN departments
                ON staff_department_assignments.department_id = departments.department_id

            LEFT JOIN insurance_staff
                ON staff.staff_id = insurance_staff.staff_id
                AND insurance_staff.provider_id = 1
                AND insurance_staff.accepted_from <= CURDATE()
                AND (
                    insurance_staff.accepted_until IS NULL
                    OR insurance_staff.accepted_until >= CURDATE()
                )

            WHERE staff.active = 1
              AND staff.job_title IN (
                  'Physician',
                  'Pediatrician',
                  'Emergency Physician',
                  'Neurologist',
                  'Cardiologist'
              )
              AND staff_department_assignments.start_date <= CURDATE()
              AND (
                  staff_department_assignments.end_date IS NULL
                  OR staff_department_assignments.end_date >= CURDATE()
              )

            ORDER BY
                insurance_covered DESC,
                staff.last_name,
                staff.first_name";

    $doctors = mysqli_query($hospital_conn, $sql);
}
// retrieves departments
$departments = [];

$sql = "SELECT department_id, department_name
        FROM departments
        ORDER BY department_name";

$result = mysqli_query($hospital_conn, $sql);

if ($result) {
    while ($department = mysqli_fetch_assoc($result)) {
        $departments[] = $department;
    }
}

// handles appointment booking
if ($_SERVER["REQUEST_METHOD"] === "POST" &&
    isset($_POST["book_appointment"])) {

    // verifies CSRF token
    if (!isset($_POST["csrf_token"]) ||
        !hash_equals($csrf_token, $_POST["csrf_token"])) {

        $error = "Invalid request. Please try again.";

    } elseif (empty($patient_id)) {

        $error = "Patient hospital account unavailable.";

    } else {

        $staff_id = filter_input(INPUT_POST, "staff_id", FILTER_VALIDATE_INT);
        $department_id = filter_input(INPUT_POST, "department_id", FILTER_VALIDATE_INT);

        $appointment_time = trim($_POST["appointment_time"] ?? "");
        $reason = trim($_POST["reason"] ?? "");

        if (!$staff_id || !$department_id || !$appointment_time) {

            $error = "Please complete all required fields.";

        } elseif (strlen($reason) > 200) {

            $error = "Appointment reason cannot exceed 200 characters.";

        } else {

            // validates appointment date and time
            $date = DateTime::createFromFormat("!Y-m-d\TH:i", $appointment_time);
            $date_errors = DateTime::getLastErrors();

            if (!$date ||
                ($date_errors && (
                    $date_errors["warning_count"] > 0 ||
                    $date_errors["error_count"] > 0
                )) ||
                $date->format("Y-m-d\TH:i") !== $appointment_time ||
                $date <= new DateTime()) {

                $error = "Please select a valid future appointment date and time.";

            } else {

                $sql_datetime = $date->format("Y-m-d H:i:s");
                $appointment_date = $date->format("Y-m-d");

                // verifies doctor belongs to selected department
                $sql = "SELECT staff_id
                        FROM staff_department_assignments
                        WHERE staff_id = ?
                          AND department_id = ?
                          AND start_date <= ?
                          AND (end_date IS NULL OR end_date >= ?)";

                $stmt = mysqli_prepare($hospital_conn, $sql);

                mysqli_stmt_bind_param(
                    $stmt,
                    "iiss",
                    $staff_id,
                    $department_id,
                    $appointment_date,
                    $appointment_date
                );

                mysqli_stmt_execute($stmt);

                $result = mysqli_stmt_get_result($stmt);
                $valid_doctor = mysqli_fetch_assoc($result);

                mysqli_stmt_close($stmt);

                if (!$valid_doctor) {

                    $error = "Invalid doctor or department.";

                } else {

                    // checks whether doctor already has an appointment
                    $sql = "SELECT appointment_id
                            FROM appointments
                            WHERE staff_id = ?
                              AND appointment_time = ?
                              AND status IN ('Scheduled', 'Checked In')
                            LIMIT 1";

                    $stmt = mysqli_prepare($hospital_conn, $sql);

                    mysqli_stmt_bind_param(
                        $stmt,
                        "is",
                        $staff_id,
                        $sql_datetime
                    );

                    mysqli_stmt_execute($stmt);

                    $result = mysqli_stmt_get_result($stmt);
                    $existing_appointment = mysqli_fetch_assoc($result);

                    mysqli_stmt_close($stmt);

                    // checks whether patient already has an appointment
                    $sql = "SELECT appointment_id
                            FROM appointments
                            WHERE patient_id = ?
                              AND appointment_time = ?
                              AND status IN ('Scheduled', 'Checked In')
                            LIMIT 1";

                    $stmt = mysqli_prepare($hospital_conn, $sql);

                    mysqli_stmt_bind_param(
                        $stmt,
                        "is",
                        $patient_id,
                        $sql_datetime
                    );

                    mysqli_stmt_execute($stmt);

                    $result = mysqli_stmt_get_result($stmt);
                    $patient_appointment = mysqli_fetch_assoc($result);

                    mysqli_stmt_close($stmt);

                    if ($existing_appointment) {

                        $error = "This doctor already has an appointment at that time.";

                    } elseif ($patient_appointment) {

                        $error = "This patient already has an appointment at that time.";

                    } else {

                        // inserts appointment into hospital database
                        $sql = "INSERT INTO appointments
                                (
                                    patient_id,
                                    staff_id,
                                    department_id,
                                    appointment_time,
                                    status,
                                    reason
                                )
                                VALUES (?, ?, ?, ?, 'Scheduled', ?)";

                        $stmt = mysqli_prepare($hospital_conn, $sql);

                        mysqli_stmt_bind_param(
                            $stmt,
                            "iiiss",
                            $patient_id,
                            $staff_id,
                            $department_id,
                            $sql_datetime,
                            $reason
                        );

                        if (mysqli_stmt_execute($stmt)) {

                            $success = "Appointment successfully booked!";

                        } else {

                            $error = "Unable to book appointment. Please try again.";

                        }

                        mysqli_stmt_close($stmt);
                    }
                }
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

    <title>HNS - Book Appointment</title>
    <link rel="stylesheet" href="style.css">
</head>

<body class="patient-dashboard">

<div class="dashboard">

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

        <section class="booking-back">

            <?php if ($user_type === "Patient") { ?>

                <a href="Patient Dashboard.php">
                    ← Back to Patient Dashboard
                </a>

            <?php } else { ?>

                <a href="Caretaker Dashboard.php?patient=<?php
                    echo (int)$selected_patient_user_id;
                ?>">
                    ← Back to Caretaker Dashboard
                </a>

            <?php } ?>

        </section>

        <div class="header-right">

            <div class="patient-profile">

                <span class="patient-badge">
                    <?php echo htmlspecialchars($user_type); ?>
                </span>

                <div class="patient-info">

                    <strong>
                        <?php echo htmlspecialchars($first_name . " " . $last_name); ?>
                    </strong>

                    <small>
                        <?php echo htmlspecialchars($username); ?>
                    </small>

                </div>

                <div class="avatar">👤</div>

            </div>

        </div>

    </header>

    <main>

        <h2 class="welcome-title">
            Book an Appointment
        </h2>

        <section class="booking-section">

            <div class="booking-card">

                <h3>Appointment Details</h3>

                <?php if (!empty($success)) { ?>

                    <p class="booking-success">
                        <?php echo htmlspecialchars($success); ?>
                    </p>

                <?php } ?>

                <?php if (!empty($error)) { ?>

                    <p class="auth-error">
                        <?php echo htmlspecialchars($error); ?>
                    </p>

                <?php } ?>

                <?php if ($patient_id) { ?>

                    <form method="POST">

                        <input
                            type="hidden"
                            name="csrf_token"
                            value="<?php echo htmlspecialchars($csrf_token); ?>"
                        >

                        <input
                            type="hidden"
                            name="department_id"
                            id="department_id"
                            value=""
                        >

                        
                        <section class="doctors-section">

                            <h3>Recommended Doctors:</h3>

                            <div class="doctors-list">

                                <select name="staff_id" id="staff_id" required>

                                    <option value="">Choose a Doctor</option>

                                    <?php if (empty($patient_id)) { ?>
                                        <option value="" disabled>Doctors not available. Hospital account not connected.</option>

                                    <?php } elseif ($doctors === false) { ?>
                                        <option value="" disabled>Unable to retrieve doctors at this time.</option>

                                    <?php } elseif (mysqli_num_rows($doctors) > 0) { ?>

                                        <?php while ($doctor = mysqli_fetch_assoc($doctors)) { ?>

                                            <option
                                                value="<?php echo (int)$doctor["staff_id"]; ?>"
                                                data-department="<?php echo (int)$doctor["department_id"]; ?>"
                                                class="<?php echo $doctor["insurance_covered"] ? 'selected' : ''; ?>"
                                            >

                                                <?php echo $doctor["insurance_covered"] ? '✓ ' : ''; ?>

                                                👤 Dr.
                                                <?php echo htmlspecialchars($doctor["first_name"] . " " . $doctor["last_name"]); ?>

                                                – <?php echo htmlspecialchars($doctor["job_title"]); ?>

                                                <?php if (!empty($doctor["department_name"])) { ?>
                                                    – <?php echo htmlspecialchars($doctor["department_name"]); ?>
                                                <?php } ?>

                                                <?php if ($doctor["insurance_covered"]) { ?>
                                                    (<?php echo htmlspecialchars($provider_name); ?> Covered)
                                                <?php } else { ?>
                                                    (Not Covered)
                                                <?php } ?>

                                            </option>

                                        <?php } ?>

                                    <?php } else { ?>

                                        <option value="" disabled>No doctors are currently available.</option>

                                    <?php } ?>

                                </select>

                            </div>

                        </section>

                        <div class="booking-field">

                            <label for="appointment_time">
                                Select Date and Time:
                            </label>

                            <input
                                type="datetime-local"
                                name="appointment_time"
                                id="appointment_time"
                                min="<?php echo date('Y-m-d\TH:i'); ?>"
                                required
                            >

                        </div>

                        <div class="booking-field">

                            <label for="reason">
                                Reason for Appointment:
                            </label>

                            <textarea
                                name="reason"
                                id="reason"
                                maxlength="200"
                                placeholder="Enter the reason for your appointment..."
                            ></textarea>

                        </div>

                        <button
                            type="submit"
                            name="book_appointment"
                            class="booking-button"
                        >
                            Confirm Appointment
                        </button>

                    </form>

                <?php } else { ?>

                    <p>
                        Please connect a hospital patient account
                        before booking an appointment.
                    </p>

                <?php } ?>

            </div>

        </section>

    </main>

</div>

<script>

const doctorSelect = document.getElementById("staff_id");
const departmentInput = document.getElementById("department_id");

doctorSelect.addEventListener("change", function() {

    const selectedDoctor = this.options[this.selectedIndex];

    departmentInput.value = selectedDoctor.dataset.department || "";
});

</script>

</body>
</html>