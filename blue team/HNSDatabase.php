<!--this is our personal database, seperate from any hospital-->
<?php
$hns_conn = mysqli_connect("localhost", "root", "", "hns_database");
if (!$hns_conn) {
    die("HNS Connection failed: " . mysqli_connect_error());
}
?>