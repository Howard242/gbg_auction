<?php
header('Content-Type: application/json');
include $_SERVER['DOCUMENT_ROOT'] . '/gbg_auction/php/db_config.php';

$response = ['live' => [], 'timed' => [], 'sold' => [], 'expired' => []];

// Fetch active auctions (live & timed)
$query = "SELECT id, title, image, starting_price, end_time, auction_type 
          FROM auctions 
          WHERE status = 'active' 
          ORDER BY end_time ASC";
$result = $conn->query($query);

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $auction_data = [
            'id' => $row['id'],
            'title' => $row['title'],
            'image' => basename($row['image']), // Extract only filename
            'starting_price' => $row['starting_price'],
            'end_time' => $row['end_time'],
            'type' => $row['auction_type'],
        ];

        if ($row['auction_type'] === 'live') {
            $response['live'][] = $auction_data;
        } else {
            $response['timed'][] = $auction_data;
        }
    }
} else {
    $response['error'] = "No active auctions found.";
}

// Fetch sold auctions
$query_sold = "SELECT id, title, image, starting_price, end_time 
               FROM auctions 
               WHERE status = 'sold' 
               ORDER BY end_time DESC";
$result_sold = $conn->query($query_sold);

if ($result_sold && $result_sold->num_rows > 0) {
    while ($row = $result_sold->fetch_assoc()) {
        $response['sold'][] = [
            'id' => $row['id'],
            'title' => $row['title'],
            'image' => basename($row['image']),
            'starting_price' => $row['starting_price'],
            'end_time' => $row['end_time'],
        ];
    }
} else {
    $response['error'] = "No sold auctions found.";
}

// Fetch expired auctions
$query_expired = "SELECT id, title, image, starting_price, end_time 
                  FROM auctions 
                  WHERE status = 'expired' 
                  ORDER BY end_time DESC";
$result_expired = $conn->query($query_expired);

if ($result_expired && $result_expired->num_rows > 0) {
    while ($row = $result_expired->fetch_assoc()) {
        $response['expired'][] = [
            'id' => $row['id'],
            'title' => $row['title'],
            'image' => basename($row['image']),
            'starting_price' => $row['starting_price'],
            'end_time' => $row['end_time'],
        ];
    }
} else {
    $response['error'] = "No expired auctions found.";
}

echo json_encode($response);
?>