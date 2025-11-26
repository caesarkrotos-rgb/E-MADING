<?php
include 'config.php';

// Update all articles to published
$update = mysqli_query($conn, "UPDATE artikel SET status = 'published'");
echo "Updated articles: " . ($update ? "Success" : "Failed - " . mysqli_error($conn)) . "<br>";

// Check result
$result = mysqli_query($conn, "SELECT id, judul, status FROM artikel LIMIT 5");
while ($row = mysqli_fetch_assoc($result)) {
    echo "ID: " . $row['id'] . " - " . $row['judul'] . " - Status: " . $row['status'] . "<br>";
}

echo "<br><a href='index.php'>Go to Landing Page</a>";
?>