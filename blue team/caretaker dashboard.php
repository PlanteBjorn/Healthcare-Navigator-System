<!--Caretaker Dashboard.php-->
<!--[Bjorn] same as patient dashboard, had to remove the php and turn this file into a php-->


<!--connects to HNSDatabase-->
<?php
session_start();

// ensures only logged in caretakers can access this dashboard
if (!isset($_SESSION["HNS_user_id"]) ||
    $_SESSION["HNS_user_type"] !== "Caretaker") {
    header("Location: signin.php");
    exit();
}



// retrieves caretaker account
$user_id = (int) $_SESSION["HNS_user_id"];
$username = $_SESSION["HNS_username"];
$first_name = $_SESSION["HNS_first_name"];
$last_name = $_SESSION["HNS_last_name"];
?>

<!--this connects to the hospital database through the HIM and HCM-->
<?php
include("HNSDatabase.php");

// cross site request prevention
if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION["csrf_token"];

$connection_message = "";
$connection_error = "";
?>

<?php

if (isset($_POST["connect_patient"])) {

    // verifies CSRF token
    if (!isset($_POST["csrf_token"]) ||
        !hash_equals($csrf_token, $_POST["csrf_token"])) {
        $connection_error = "Invalid request. Please try again.";
    } else {
        $auth_code = trim($_POST["authorization_code"] ?? "");
        if ($auth_code === "") {
            $connection_error = "Please enter an authorization code.";
        } else {
            $auth_code_hash = hash("sha256", $auth_code);
            mysqli_begin_transaction($hns_conn);
            try {

                // retrieves active authorization code
                $sql = "SELECT authorization_id, patient_user_id
                        FROM hns_caretaker_auth
                        WHERE authorization_code_hash = ?
                          AND expiration_time > NOW()
                          AND is_used = 0
                        FOR UPDATE";

                $stmt = mysqli_prepare($hns_conn, $sql);
                mysqli_stmt_bind_param($stmt, "s", $auth_code_hash);
                mysqli_stmt_execute($stmt);
                $result = mysqli_stmt_get_result($stmt);
                $authorization = mysqli_fetch_assoc($result);
                mysqli_stmt_close($stmt);
                if (!$authorization) {
                    throw new Exception("Authorization code is invalid, expired, or already used.");
                }

                $patient_user_id = (int) $authorization["patient_user_id"];

                // verifies patient account
                $sql = "SELECT HNS_user_id
                        FROM hns_users
                        WHERE HNS_user_id = ?
                          AND HNS_user_type = 'Patient'";

                $stmt = mysqli_prepare($hns_conn, $sql);
                mysqli_stmt_bind_param($stmt, "i", $patient_user_id);
                mysqli_stmt_execute($stmt);

                $result = mysqli_stmt_get_result($stmt);
                $patient_account = mysqli_fetch_assoc($result);

                mysqli_stmt_close($stmt);

                if (!$patient_account) {
                    throw new Exception("Patient account could not be found.");
                }

                // checks existing connection
                $sql = "SELECT connection_id, status
                        FROM hns_caretaker_patients
                        WHERE caretaker_user_id = ?
                          AND patient_user_id = ?
                        FOR UPDATE";

                $stmt = mysqli_prepare($hns_conn, $sql);
                mysqli_stmt_bind_param($stmt, "ii", $user_id, $patient_user_id);
                mysqli_stmt_execute($stmt);

                $result = mysqli_stmt_get_result($stmt);
                $existing_connection = mysqli_fetch_assoc($result);

                mysqli_stmt_close($stmt);

                if ($existing_connection && $existing_connection["status"] === "Active") {
                    throw new Exception("You are already connected to this patient.");
                }

                // marks authorization code as used
                $sql = "UPDATE hns_caretaker_auth
                        SET is_used = 1
                        WHERE authorization_id = ?
                          AND is_used = 0";

                $stmt = mysqli_prepare($hns_conn, $sql);

                mysqli_stmt_bind_param($stmt, "i", $authorization["authorization_id"]);
                mysqli_stmt_execute($stmt);

                if (mysqli_stmt_affected_rows($stmt) !== 1) {
                    mysqli_stmt_close($stmt);
                    throw new Exception("Authorization code has already been used.");
                }

                mysqli_stmt_close($stmt);

                // creates or restores connection
                if ($existing_connection) {
                    $sql = "UPDATE hns_caretaker_patients
                            SET status = 'Active',
                                connected_at = CURRENT_TIMESTAMP
                            WHERE connection_id = ?";

                    $stmt = mysqli_prepare($hns_conn, $sql);

                    mysqli_stmt_bind_param($stmt, "i", $existing_connection["connection_id"]);
                } else {
                    $sql = "INSERT INTO hns_caretaker_patients
                            (
                                caretaker_user_id,
                                patient_user_id,
                                status
                            )
                            VALUES (?, ?, 'Active')";

                    $stmt = mysqli_prepare($hns_conn, $sql);

                    mysqli_stmt_bind_param($stmt, "ii", $user_id, $patient_user_id);
                }

                if (!mysqli_stmt_execute($stmt)) {
                    mysqli_stmt_close($stmt);
                    throw new Exception("Unable to create patient connection.");
                }

                mysqli_stmt_close($stmt);
                mysqli_commit($hns_conn);

                $connection_message = "Successfully connected to patient.";

            } catch (Throwable $e) {

                mysqli_rollback($hns_conn);

                $connection_error = $e->getMessage();
            }
        }
    }
}

?>

<?php

// retrieves connected patients
$connected_patients = [];

$sql = "SELECT
            connections.patient_user_id,
            users.HNS_first_name,
            users.HNS_last_name,
            users.HNS_username,
            users.hospital_patient_id
        FROM hns_caretaker_patients AS connections
        INNER JOIN hns_users AS users
            ON connections.patient_user_id = users.HNS_user_id
        WHERE connections.caretaker_user_id = ?
          AND connections.status = 'Active'
          AND users.HNS_user_type = 'Patient'
        ORDER BY users.HNS_last_name ASC";

$stmt = mysqli_prepare($hns_conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);

while ($row = mysqli_fetch_assoc($result)) {
    $connected_patients[] = $row;
}

mysqli_stmt_close($stmt);


// patient selection
$selected_patient = null;
$patient_id = null;

// defaults to no selected patient
if (!empty($connected_patients)) {

    // No patient selected by default
    $selected_patient_user_id = null;

    // checks requested patient
    if (isset($_GET["patient"]) && $_GET["patient"] !== "") {
        $requested_patient_id = filter_var($_GET["patient"], FILTER_VALIDATE_INT);
        if ($requested_patient_id !== false) {
            foreach ($connected_patients as $connected_patient) {
                if ($connected_patient["patient_user_id"] === $requested_patient_id) {
                    $selected_patient_user_id =$requested_patient_id;
                    break;
                }
            }
        }
    }

    // retrieves selected patient
    foreach ($connected_patients as $connected_patient) {
        if ($selected_patient_user_id !== null && $connected_patient["patient_user_id"] === $selected_patient_user_id) {
            $selected_patient = $connected_patient;
            break;
        }
    }

    // retrieves hospital patient ID
    if ($selected_patient) {
        $patient_id = $selected_patient["hospital_patient_id"] ?? null;
    }
}

?>

<?php

// initialises hospital information
$patient = null;
$doctors = null;
$appointments = null;
$calendar_appointments = [];

// loads selected patient's hospital information
if (!empty($patient_id)) {
    include("HIM.php");
    include("HCM.php");
}


// retrieves patient information
if ($patient) {
    $patient_name = $patient["first_name"] . " " . $patient["last_name"];
    $birth_date = new DateTime($patient["date_of_birth"]);
    $today = new DateTime();
    $patient_age = $today->diff($birth_date)->y;
    $blood_type = $patient["blood_type"];
} else {
    $patient = null;

    $patient_name = "";
    $patient_age = null;
    $blood_type = "";

}

// Insurance provider
$provider_name = $patient["provider_name"] ?? "Insurance";

// placeholder medical information
// replace when these fields exist in the hospital database
$height = "175 cm";
$weight = "75 kg";

$blood_pressure = "120/80 mmHg";
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>HNS - Caretaker Dashboard</title>
    <link rel="stylesheet" href="style.css">
</head>

<body class="patient-dashboard"> <!--keep this even though it's calling to patient-->

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

        <div class="header-right">

            <div class="patient-profile">

                <span class="patient-badge">
                    Caretaker
                </span>

                <div class="patient-info">

                    <strong><?php echo htmlspecialchars($first_name . " " . $last_name); ?></strong>
                    <small><?php echo htmlspecialchars($username); ?></small>

                </div>

                <div class="avatar">👤</div>

            </div>

            <section class="caretaker-auth">

                <h3>Connect to Patient</h3>

                <form method="POST">

                    <input
                        type="hidden"
                        name="csrf_token"
                        value="<?php echo htmlspecialchars($csrf_token); ?>"
                    >

                    <input
                        type="text"
                        name="authorization_code"
                        placeholder="Enter authorization code"
                        required
                    >

                    <button type="submit" name="connect_patient">Connect</button>

                </form>

                <?php if (!empty($connection_message)) { ?>

                    <p><?php echo htmlspecialchars($connection_message); ?></p>

                <?php } ?>

                <?php if (!empty($connection_error)) { ?>

                    <p><?php echo htmlspecialchars($connection_error); ?></p>

                <?php } ?>

            </section>

            <nav class="site-navigation">

                <details class="nav-dropdown">

                    <summary class="menu">☰</summary>

                    <div class="nav-links">

                        <a href="index.html">Home</a>

                    </div>

                </details>

            </nav>

        </div>

    </header>

    <main>

        <h2 class="welcome-title">
            Welcome Back, <?php echo htmlspecialchars($first_name); ?>!
        </h2>

        <section class="patient-selection">

            <?php if (!empty($connection_message)) { ?>

                <p><?php echo htmlspecialchars($connection_message); ?></p>

            <?php } ?>

            <?php if (!empty($connection_error)) { ?>

                <p><?php echo htmlspecialchars($connection_error); ?></p>

            <?php } ?>

        </section>


        <section class="patient-selection">

            <h3>Select Patient</h3>

            <?php if (empty($connected_patients)) { ?>

                <p>You haven't connected to any patients yet.</p>

            <?php } else { ?>

                <form method="GET">

                    <select
                        name="patient"
                        id="patientSelect"
                        onchange="this.form.submit()"
                    >

                        <option value="" <?php echo !$selected_patient ? "selected" : ""; ?>>--</option>

                        <?php foreach ($connected_patients as $connected_patient) { ?>

                            <option
                                value="<?php echo $connected_patient["patient_user_id"]; ?>"
                                <?php echo ($selected_patient && $selected_patient["patient_user_id"] === $connected_patient["patient_user_id"]) ? "selected" : ""; ?>
                            >
                                <?php echo htmlspecialchars($connected_patient["HNS_first_name"] . " " . $connected_patient["HNS_last_name"]); ?>
                            </option>

                        <?php } ?>

                    </select>

                </form>

            <?php } ?>

        </section>

        <?php if ($selected_patient) { ?>

        <div class="dashboard-grid">

            <!-- Left Column -->

            <div class="left-column">

                <section class="appointment-section">

                    <h3>Patient Appointment</h3>

                    <div class="appointment-card">

                        <h4>Upcoming Appointments</h4>

                        <?php if (!$patient) { ?>
                            <p>Appointments unavailable. Hospital account not connected.</p>
                        <?php } elseif ($appointments && mysqli_num_rows($appointments) > 0) { ?>
                            <?php while ($appointment = mysqli_fetch_assoc($appointments)) { ?>
                                <?php if ($appointment["status"] === "Scheduled") { ?>
                                    <div class="appointment-details">

                                        <p>
                                            Dr. <?php echo htmlspecialchars($appointment["first_name"] . " " . $appointment["last_name"]); ?>
                                            <br>
                                            <?php echo date("F j, Y, g:i A", strtotime($appointment["appointment_time"])); ?>
                                        </p>

                                        <p>
                                            <?php echo htmlspecialchars($appointment["department_name"]); ?>
                                        </p>

                                        <p>
                                            <?php echo htmlspecialchars($appointment["reason"] ?? "No reason provided"); ?>
                                        </p>

                                        <form method="POST" onsubmit="return confirmAppointmentCancellation();">

                                            <input
                                                type="hidden"
                                                name="appointment_id"
                                                value="<?php echo $appointment["appointment_id"]; ?>"
                                            >

                                            <button type="submit" name="cancel_appointment">
                                                Cancel Appointment
                                            </button>

                                        </form>

                                    </div>

                                <?php } ?>
                            <?php } ?>

                        <?php } else { ?>
                            <p>No upcoming appointments.</p>
                        <?php } ?>

                    </div>

                </section>


                <section class="medical-section">

                    <h3>
                        <?php echo htmlspecialchars($patient_name); ?>'s Medical Record
                    </h3>

                    <div class="medical-card">

                        <h4>Medical Records</h4>

                        <?php if ($patient) { ?>

                            <p>
                                Patient Name:
                                <?php echo htmlspecialchars($patient_name); ?>
                            </p>

                            <p>
                                Age:
                                <?php echo $patient_age; ?> Years
                            </p>

                            <p>
                                Blood Type:
                                <?php echo htmlspecialchars($blood_type); ?>
                            </p>

                            <p>
                                Height:
                                <?php echo htmlspecialchars($height); ?>
                            </p>

                            <p>
                                Weight:
                                <?php echo htmlspecialchars($weight); ?>
                            </p>

                            <p>
                                Blood Pressure:
                                <?php echo htmlspecialchars($blood_pressure); ?>
                            </p>

                            <p>Allergies: None Reported</p>

                            <p>Last Checkup: September 10, 2026</p>

                        <?php } else { ?>

                            <p>
                                Medical records are not synced
                                with the hospital account.
                            </p>

                        <?php } ?>

                    </div>

                </section>


                <section class="quick-actions">

                    <h3>Quick Actions</h3>

                    <a href="BookAppointment.php?patient=<?php echo $selected_patient["patient_user_id"]; ?>">
                        📅 Book Patient Appointment →
                    </a>

                    <a href="#">
                        📋 View Patient Test Results →
                    </a>

                    <a href="#">
                        💬 Contact Patient's Doctor →
                    </a>

                    <a href="#">
                        💊 Manage Medications →
                    </a>

                </section>

            </div>

            <!-- Right Column -->

            <div class="right-column">

                <div class="calendar">

                    <div class="calendar-header">

                        <button type="button" onclick="changeMonth(-1)">‹</button>

                        <select
                            id="monthSelect"
                            onchange="renderCalendar()">
                        </select>

                        <select
                            id="yearSelect"
                            onchange="renderCalendar()">
                        </select>

                        <button type="button" onclick="changeMonth(1)">›</button>

                    </div>

                    <div class="calendar-days">

                        <span>Su</span>
                        <span>Mo</span>
                        <span>Tu</span>
                        <span>We</span>
                        <span>Th</span>
                        <span>Fr</span>
                        <span>Sa</span>

                    </div>

                    <div
                        class="calendar-dates"
                        id="calendarDates">
                    </div>

                    <div
                        class="calendar-appointments"
                        id="calendarAppointments">

                        <h4>Appointments</h4>

                        <p>Select a date to view appointments.</p>

                    </div>

                </div>


                <section class="doctors-section">

                    <h3>Recommended Doctors:</h3>

                    <div class="doctors-list">
                        <?php if (!$patient) { ?>
                            <p>Doctors not available. Hospital account not connected.</p>
                        <?php } elseif ($doctors === false) { ?>
                            <p>Unable to retrieve doctors at this time.</p>
                        <?php } elseif (mysqli_num_rows($doctors) > 0) { ?>
                            <?php while ($doctor = mysqli_fetch_assoc($doctors)) { ?>
                                <div class="doctor-card <?php echo $doctor["insurance_covered"] ? 'selected' : ''; ?>">
                                    <p>
                                        👤 Dr.
                                        <?php echo htmlspecialchars($doctor["first_name"] . " " . $doctor["last_name"]); ?>
                                    </p>

                                    <span>
                                        <?php echo htmlspecialchars($doctor["job_title"]); ?>
                                        <?php if (!empty($doctor["department_names"])) { ?>
                                            – <?php echo htmlspecialchars($doctor["department_names"]); ?>
                                        <?php } ?>
                                    </span>

                                    <?php if ($doctor["insurance_covered"]) { ?>
                                        <small>✓ <?php echo htmlspecialchars($provider_name); ?> Covered</small>
                                    <?php } ?>
                                </div>

                            <?php } ?>

                        <?php } else { ?>
                            <p>No doctors are currently available.</p>
                        <?php } ?>

                    </div>

                </section>

            </div>

        </div>

        <?php } else { ?>

            <section class="medical-section">

                <div class="medical-card">

                    <h3>No Patient Selected</h3>

                    <p>
                        Please select a Patient, or add one by inputting 
                        the authorization code 
                        to access their medical information.
                    </p>

                </div>

            </section>

        <?php } ?>

    </main>

</div>

<script>

    const months = [
        "Jan", "Feb", "Mar", "Apr", "May", "Jun",
        "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"
    ];

    const monthSelect = document.getElementById("monthSelect");
    const yearSelect = document.getElementById("yearSelect");
    const calendarAppointments = document.getElementById("calendarAppointments");
    const appointmentDates = <?php echo json_encode($calendar_appointments); ?>;
    const appointments = <?php
        $calendar_appointments = [];
        if ($patient && $appointments) {
            mysqli_data_seek($appointments, 0);
            while ($appointment = mysqli_fetch_assoc($appointments)) {
                if ($appointment["status"] === "Scheduled") {
                    $calendar_appointments[] = [
                        "date" => date("Y-m-d", strtotime($appointment["appointment_time"])),
                        "time" => date("g:i A", strtotime($appointment["appointment_time"])),
                        "doctor" => $appointment["first_name"] . " " . $appointment["last_name"],
                        "department" => $appointment["department_name"],
                        "reason" => $appointment["reason"] ?? "No reason provided"
                    ];
                }
            }
            mysqli_data_seek($appointments, 0);
        }
        echo json_encode($calendar_appointments);
    ?>;
    let selectedDate = null;

    months.forEach((month, index) => {
        monthSelect.add(new Option(month, index));
    });

    for (let year = 2020; year <= 2035; year++) {
        yearSelect.add(new Option(year, year));
    }

    monthSelect.value = 8;
    yearSelect.value = 2026;

    function renderCalendar() {

        const month = Number(monthSelect.value);
        const year = Number(yearSelect.value);

        const firstDay = new Date(year, month, 1).getDay();

        const daysInMonth =
            new Date(year, month + 1, 0).getDate();

        const calendarDates =
            document.getElementById("calendarDates");

        calendarDates.innerHTML = "";

        for (let i = 0; i < firstDay; i++) {
            calendarDates.appendChild(
                document.createElement("span")
            );
        }

        for (let day = 1; day <= daysInMonth; day++) {

            const date = document.createElement("span");

            date.textContent = day;

            date.addEventListener("click", function() {
                selectedDate = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
                renderCalendar();
                const selectedAppointments = appointments.filter(appointment => appointment.date === selectedDate);

                calendarAppointments.innerHTML = "<h4>Appointments</h4>";

                if (selectedAppointments.length === 0) {
                    calendarAppointments.innerHTML +=
                        "<p>No appointments scheduled for this day.</p>";
                } else {
                    selectedAppointments.forEach(appointment => {

                        const details = document.createElement("div");
                        details.classList.add("appointment-details");

                        const doctor = document.createElement("p");
                        doctor.textContent = "Dr. " + appointment.doctor;

                        const time = document.createElement("p");
                        time.textContent = appointment.time;

                        const department = document.createElement("p");
                        department.textContent = appointment.department;

                        const reason = document.createElement("p");
                        reason.textContent = appointment.reason;

                        details.append(doctor, time, department, reason);
                        calendarAppointments.appendChild(details);
                    });
                }
            });

            // [Bjorn] edited this to fit the appointment/calander logic
            const currentDate = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;

            if (appointmentDates.includes(currentDate)) {
                date.classList.add("highlight");
            }
            if (selectedDate === currentDate) {
                date.classList.add("selected");
            }
            calendarDates.appendChild(date);
        }
    }

    function changeMonth(direction) {

        let month = Number(monthSelect.value) + direction;
        let year = Number(yearSelect.value);

        if (month < 0) {
            month = 11;
            year--;
        }

        if (month > 11) {
            month = 0;
            year++;
        }

        if (year < 2020 || year > 2035) {
            return;
        }

        monthSelect.value = month;
        yearSelect.value = year;

        renderCalendar();
    }

    renderCalendar();

    function confirmAppointmentCancellation() {

    return confirm("Cancel this appointment?");

}

    function confirmCaretakerRemoval() {

        return confirm("Are you sure you want to remove this caretaker?");

    }


</script>

</body>
</html>