<!--[bjorn] had to get rid of the dashboard php and turn this html into a php--> 

<!--[Bjorn] seperating these php programs for visual clarity-->
<!--this connects to the HNSDatabase-->
<?php
session_start();

// ensures only logged in patients can access this dashboard
if (!isset($_SESSION["HNS_user_id"]) ||
    $_SESSION["HNS_user_type"] !== "Patient") {
    header("Location: signin.php");
    exit();
}



// retrieves account
$user_id = $_SESSION["HNS_user_id"];
$username = $_SESSION["HNS_username"];
$first_name = $_SESSION["HNS_first_name"];
$last_name = $_SESSION["HNS_last_name"];
?>

<!--this connects to the hospital database through the HIM and HCM-->
<?php
include("HNSDatabase.php");

$patient_id = null;
$sql = "SELECT hospital_patient_id
        FROM hns_users
        WHERE HNS_user_id = ?";
$stmt = mysqli_prepare($hns_conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $user_id);
mysqli_stmt_execute($stmt);

$result = mysqli_stmt_get_result($stmt);
$account = mysqli_fetch_assoc($result);

// patient info
$patient = null;
$patient_id = $account["hospital_patient_id"] ?? null;

if (!empty($patient_id)) {
    include("HIM.php");  // [bjorn] HIM implemented
    include("HCM.php");
}

if ($patient) {
    $patient_name = $patient["first_name"] . " " . $patient["last_name"];
    $birth_date = new DateTime($patient["date_of_birth"]);
    $today = new DateTime();
    $patient_age = $today->diff($birth_date)->y;
    $blood_type = $patient["blood_type"];
} else {
    $patient = null;
}

// Insurance provider
$provider_name = $patient["provider_name"] ?? "Insurance";

$height = "175 cm"; // [bjorn] I messed up here and did not implement height or weight in the sql database (will fix later)
$weight = "75 kg";

$blood_pressure = "120/80 mmHg"; // [bjorn] do we need this for our specific system?
?>


<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>HNS - Patient Dashboard</title>
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

        <div class="header-right">

            <div class="patient-profile">

                <span class="patient-badge">
                    Patient
                </span>

                <div class="patient-info"> <!--now displays appropriate account depending on who is loggedin-->

                    <strong><?php echo htmlspecialchars($first_name . " " . $last_name); ?></strong>
                    <small><?php echo htmlspecialchars($username); ?></small>

                </div>

                <div class="avatar">👤</div>

            </div>

            <!-- Navigation Menu -->

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

        <div class="dashboard-grid">

            <!-- Left Column -->

            <div class="left-column">

                <section class="appointment-section">

                    <h3>My Appointment</h3>

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

                                        <form method="POST">

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

                    <h3>My Medical Record</h3>

                    <div class="medical-card">

                        <h4>Medical Records</h4>

                        <?php if ($patient) { ?>

                            <p>Patient Name: <?php echo htmlspecialchars($patient_name); ?></p>
                            <p>Age: <?php echo $patient_age; ?> Years</p>
                            <p>Blood Type: <?php echo htmlspecialchars($blood_type); ?></p>
                            <p>Height: 175 cm</p>
                            <p>Weight: 75 kg</p>
                            <p>Blood Pressure: 120/80 mmHg</p>
                            <p>Allergies: None Reported</p>
                            <p>Last Checkup: September 10, 2026</p>

                        <?php } else { ?>

                            <p>Medical Records not Synced.</p>

                        <?php } ?>

                    </div>

                </section>

                <section class="quick-actions">

                    <h3>Quick Actions</h3>

                    <a href="#">📅 Book Appointment →</a>

                    <a href="#">📋 View Test Results →</a>

                    <a href="#">💬 Contact Doctor →</a>

                </section>

            </div>

            <!-- Right Column -->

            <div class="right-column">

                <div class="calendar">

                    <div class="calendar-header">

                        <button onclick="changeMonth(-1)">‹</button> <!--[Bjorn] for anyone wondering i used numlock alt codes for these symbols, this one is +0139 -->

                        <select
                            id="monthSelect"
                            onchange="renderCalendar()">
                        </select>

                        <select
                            id="yearSelect"
                            onchange="renderCalendar()">
                        </select>

                        <button onclick="changeMonth(1)">›</button>

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

                    <div class="calendar-appointments" id="calendarAppointments">

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

</script>

</body>
</html>