<!--This is the Hospital Communication Manager-->
<!-- it communicates with the hospital database through the Patient Dashboard -->

<?php

$hospital_conn = mysqli_connect("localhost", "root", "", "hospital_database1");

if (!$hospital_conn) {
    die("Connection failed (HCM Error): " . mysqli_connect_error());
}


// appointment booking
$booking_message = "";

if (
    isset($_POST["book_appointment"]) &&
    !empty($patient_id)
) {

    $staff_id = (int) $_POST["staff_id"];
    $department_id = (int) $_POST["department_id"];
    $appointment_time = $_POST["appointment_time"];
    $reason = trim($_POST["reason"]);

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
        $appointment_time,
        $reason
    );

    if (mysqli_stmt_execute($stmt)) {
        $booking_message = "Appointment successfully booked.";
    } else {
        $booking_message = "Unable to book appointment.";
    }
} 

// appointment cancellation
if (
    isset($_POST["cancel_appointment"]) &&
    !empty($patient_id)
) {

    $appointment_id = (int) $_POST["appointment_id"];

    $sql = "UPDATE appointments
            SET status = 'Cancelled'
            WHERE appointment_id = ?
              AND patient_id = ?
              AND status = 'Scheduled'";

    $stmt = mysqli_prepare($hospital_conn, $sql);

    mysqli_stmt_bind_param(
        $stmt,
        "ii",
        $appointment_id,
        $patient_id
    );

    if (mysqli_stmt_execute($stmt) &&
        mysqli_stmt_affected_rows($stmt) > 0) {
        $booking_message = "Appointment successfully cancelled.";
    } else {
        $booking_message = "Unable to cancel appointment.";
    }
}

// appointment information
$appointments = null;

if (isset($patient_id) && !empty($patient_id)) {
    $sql = "SELECT appointments.appointment_id,
                   appointments.appointment_time,
                   appointments.status,
                   appointments.reason,
                   staff.first_name,
                   staff.last_name,
                   staff.job_title,
                   departments.department_name
            FROM appointments
            INNER JOIN staff
                ON appointments.staff_id = staff.staff_id
            INNER JOIN departments
                ON appointments.department_id = departments.department_id
            WHERE appointments.patient_id = ?
            ORDER BY appointments.appointment_time ASC";

    $stmt = mysqli_prepare($hospital_conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $patient_id);
    mysqli_stmt_execute($stmt);
    $appointments = mysqli_stmt_get_result($stmt);

    // calendar appointment dates
    $calendar_appointments = [];

    if ($appointments) {
        while ($appointment = mysqli_fetch_assoc($appointments)) {
            if ($appointment["status"] === "Scheduled") {
                $calendar_appointments[] = date("Y-m-d", strtotime($appointment["appointment_time"]));
            }
        }
        // reset 
        mysqli_data_seek($appointments, 0);
    }
}

?>