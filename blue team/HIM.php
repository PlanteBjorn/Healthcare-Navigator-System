<!--HIM.php-->
<!--This is the Hospital Information Manager-->
<!-- it will access the prebuilt psuedo hospital database in MyPHPAdmin -->
<?php
$hospital_conn = mysqli_connect("localhost", "root", "", "hospital_database1");
if (!$hospital_conn) {
    die("Connection failed (HIM Error): " . mysqli_connect_error());
}

// patient info
$patient = null;

if (isset($patient_id) && !empty($patient_id)) {
    $sql = "SELECT patient_id,
                   medical_record_no,
                   first_name,
                   last_name,
                   date_of_birth,
                   blood_type
            FROM patients
            WHERE patient_id = ?";

    $stmt = mysqli_prepare($hospital_conn, $sql);
    mysqli_stmt_bind_param($stmt, "i", $patient_id);
    mysqli_stmt_execute($stmt);

    $result = mysqli_stmt_get_result($stmt);
    $patient = mysqli_fetch_assoc($result);

    if ($patient) {
        $birth_date = new DateTime($patient["date_of_birth"]);
        $today = new DateTime();
        $patient_age = $today->diff($birth_date)->y;
    }
}


// doctor slide
$doctors = null;

if ($patient) {
    $sql = "SELECT staff.staff_id,
                   staff.first_name,
                   staff.last_name,
                   staff.job_title,
                   departments.department_names,
                   CASE
                       WHEN insurance_staff.staff_id IS NOT NULL
                       THEN 1
                       ELSE 0
                   END AS insurance_covered
            FROM staff
            LEFT JOIN insurance_staff
                ON staff.staff_id = insurance_staff.staff_id
                AND insurance_staff.provider_id = 1
                AND insurance_staff.accepted_from <= CURDATE()
                AND (
                    insurance_staff.accepted_until IS NULL
                    OR insurance_staff.accepted_until >= CURDATE()
                )
            LEFT JOIN (
                SELECT sda.staff_id,
                       GROUP_CONCAT(
                           DISTINCT departments.department_name
                           SEPARATOR ', '
                       ) AS department_names
                FROM staff_department_assignments sda
                INNER JOIN departments
                    ON sda.department_id = departments.department_id
                WHERE sda.start_date <= CURDATE()
                  AND (
                      sda.end_date IS NULL
                      OR sda.end_date >= CURDATE()
                  )
                GROUP BY sda.staff_id
            ) departments
                ON staff.staff_id = departments.staff_id
            WHERE staff.active = 1
              AND staff.job_title IN (
                  'Physician',
                  'Pediatrician',
                  'Emergency Physician',
                  'Neurologist',
                  'Cardiologist'
              )
            ORDER BY staff.last_name";

    $doctors = mysqli_query($hospital_conn, $sql);
}
?>

<!--[bjorn] more will be added, to this, the HCM will also be added but aas it's seperate php file
sorry for the delay, busy weekend-->