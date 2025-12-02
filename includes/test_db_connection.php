<?php
// test_db_connection.php

// Define the database connection details from your image_b49583.jpg
$host = "localhost";
$user = "ehubph_Andrei";
$pass = "Charles29!";
$db   = "ehubph_water_monitoring";
$port = 3306;

echo "Attempting to connect to the database...<br>";

// Establish the connection
$conn = new mysqli($host, $user, $pass, $db, $port);

// CRITICAL: Check the connection and stop the script if it fails.
if ($conn->connect_error) {
    die("<h1 style='color: red;'>Database Connection FAILED!</h1>" .
        "<strong>Error:</strong> " . $conn->connect_error . "<br>" .
        "Please check the host, username, password, and database name in includes/db.php.");
}

// Connection successful
echo "<h1 style='color: green;'>Database Connection SUCCESSFUL! 🎉</h1>";

// Check if the 'refilling_stations' table exists (the one used to fetch the stations)
$table_check_sql = "SELECT 1 FROM refilling_stations LIMIT 1";
if ($conn->query($table_check_sql) === FALSE) {
    echo "<h3 style='color: orange;'>⚠️ Table Check Warning:</h3>";
    echo "The query <code>" . htmlspecialchars($table_check_sql) . "</code> failed.<br>";
    echo "This usually means the table <strong>refilling_stations</strong> is missing from the <strong>$db</strong> database.<br>";
    echo "You must create this table and the <strong>user_stations</strong> table.";
} else {
    echo "<h3>Table *refilling_stations* exists!</h3>";
}

$conn->close();
echo "Connection closed.";
