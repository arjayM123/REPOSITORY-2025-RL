<?php
function trackVisitor($conn) {
    $ip = $_SERVER['REMOTE_ADDR'];
    $userAgent = $_SERVER['HTTP_USER_AGENT'];
    
    $stmt = $conn->prepare("INSERT IGNORE INTO visitors (ip_address, user_agent) VALUES (?, ?)");
    $stmt->bind_param("ss", $ip, $userAgent);
    $stmt->execute();
}

function getTotalVisitors($conn) {
    $result = $conn->query("SELECT COUNT(DISTINCT CONCAT(ip_address, user_agent)) as total FROM visitors");
    $row = $result->fetch_assoc();
    return $row['total'];
}