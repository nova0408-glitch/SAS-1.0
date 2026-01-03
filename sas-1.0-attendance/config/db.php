<?php
$conn = new mysqli("localhost", "root", "", "sas_db");

if ($conn->connect_error) {
    die("DB Connection failed: " . $conn->connect_error);
}