<?php

$caretaker_name = "Ramos";
$patient_name = "Robert Smith";

$patient_age = 72;
$blood_type = "O+";
$height = "170 cm";
$weight = "78 kg";

$appointment_doctor = "Dr. Victor von Doom";
$appointment_date = "November 5, 2026";
$appointment_time = "10:00 AM";

echo "<h2>Caretaker Dashboard Information</h2>";

echo "<p>Caretaker: " . htmlspecialchars($caretaker_name) . "</p>";

echo "<h3>Patient Information</h3>";

echo "<p>Patient Name: " . htmlspecialchars($patient_name) . "</p>";
echo "<p>Age: " . $patient_age . "</p>";
echo "<p>Blood Type: " . $blood_type . "</p>";
echo "<p>Height: " . $height . "</p>";
echo "<p>Weight: " . $weight . "</p>";

