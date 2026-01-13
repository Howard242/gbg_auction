<?php
session_start(); // Start session for chat history and user tracking

// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Database connection
$servername = "localhost";
$username = "root"; // Change if needed
$password = ""; // Change if needed
$database = "gbg_auction"; // Your database name

$conn = new mysqli($servername, $username, $password, $database);

// Check connection
if ($conn->connect_error) {
    die(json_encode(["reply" => "Database connection failed: " . $conn->connect_error]));
}

// Process only POST requests
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $data = json_decode(file_get_contents("php://input"), true);
    
    if (!isset($data["message"]) || empty(trim($data["message"]))) {
        echo json_encode(["reply" => "Please type a message."]);
        exit;
    }

    $userMessage = trim($data["message"]); // Trim input

    // Initialize chat history if not set
    if (!isset($_SESSION['chat_history'])) {
        $_SESSION['chat_history'] = [];
    }

    // Store user message in chat history
    $_SESSION['chat_history'][] = ["user" => $userMessage];

    // Log user message for debugging
    file_put_contents("chatbot_log.txt", date("Y-m-d H:i:s") . " - User: " . $userMessage . "\n", FILE_APPEND);

    // ✅ **Context-Aware Responses**
    $botResponse = null;
    $lastBotResponse = null;

    // Get the last bot response from the chat history
    foreach (array_reverse($_SESSION['chat_history']) as $message) {
        if (isset($message['bot'])) {
            $lastBotResponse = $message['bot'];
            break;
        }
    }

    // Check if the user is asking for steps after being told they can create an account
    if ($lastBotResponse && strpos(strtolower($lastBotResponse), "you can create an account") !== false && strpos(strtolower($userMessage), "what are the steps") !== false) {
        $botResponse = "To create an account, follow these steps:\n1. Click the Sign Up button.\n2. Fill in your details.\n3. Verify your email.\n4. Start using your account!";
    }

    // If no context-aware response is found, fall back to existing logic
    if (!$botResponse) {
        // ✅ **Time & Date Queries**
        if (strpos(strtolower($userMessage), "what is the time") !== false) {
            $botResponse = "The current time is " . date("h:i A");
        } elseif (strpos(strtolower($userMessage), "what is the date") !== false) {
            $botResponse = "Today's date is " . date("Y-m-d");
        }

        // ✅ **Greetings & Basic Queries**
        $greetings = ["hi", "hello", "hey", "good morning", "good afternoon", "good evening"];
        if (in_array(strtolower($userMessage), $greetings)) {
            $botResponse = "Hello! How can I assist you today?";
        }

        if (strtolower($userMessage) == "how are you") {
            $botResponse = "I'm just a chatbot, but I'm always ready to help!";
        }

        if (strtolower($userMessage) == "who are you" || strtolower($userMessage) == "what are you") {
            $botResponse = "I'm GBG Auction's chatbot, here to help you with auction-related questions!";
        }

        // ✅ **Fetch Active Auctions**
        if (strpos(strtolower($userMessage), "list auctions") !== false || strpos(strtolower($userMessage), "active auctions") !== false) {
            $stmt = $conn->prepare("SELECT item_name, starting_price FROM auctions WHERE status = 'active' ORDER BY created_at DESC LIMIT 5");
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $botResponse = "Here are some active auctions:\n";
                while ($row = $result->fetch_assoc()) {
                    $botResponse .= "- " . $row['item_name'] . " (Starting Price: $" . $row['starting_price'] . ")\n";
                }
            } else {
                $botResponse = "There are currently no active auctions.";
            }
        }

        // ✅ **Fetch Specific Auction Item Details**
        if (preg_match('/details of (.+)/', strtolower($userMessage), $matches)) {
            $item_name = $matches[1];
            $stmt = $conn->prepare("SELECT item_name, description, starting_price, highest_bid, end_time FROM auction_details WHERE item_name LIKE ?");
            $searchTerm = "%" . $item_name . "%";
            $stmt->bind_param("s", $searchTerm);
            $stmt->execute();
            $result = $stmt->get_result();

            if ($result->num_rows > 0) {
                $row = $result->fetch_assoc();
                $botResponse = "Auction Details:\n";
                $botResponse .= "Item: " . $row['item_name'] . "\n";
                $botResponse .= "Description: " . $row['description'] . "\n";
                $botResponse .= "Starting Price: $" . $row['starting_price'] . "\n";
                $botResponse .= "Highest Bid: $" . $row['highest_bid'] . "\n";
                $botResponse .= "Auction Ends: " . $row['end_time'];
            } else {
                $botResponse = "I couldn't find details for '" . $item_name . "'. Please check the name and try again.";
            }
        }

        // ✅ **Check the Highest Bid for an Item**
        if (preg_match('/highest bid for (.+)/', strtolower($userMessage), $matches)) {
            $item_name = $matches[1];
            $stmt = $conn->prepare("SELECT highest_bid FROM auction_details WHERE item_name LIKE ?");
            $searchTerm = "%" . $item_name . "%";
            $stmt->bind_param("s", $searchTerm);
            $stmt->execute();
            $stmt->store_result();

            if ($stmt->num_rows > 0) {
                $stmt->bind_result($highest_bid);
                $stmt->fetch();
                $botResponse = "The highest bid for '" . $item_name . "' is $" . $highest_bid;
            } else {
                $botResponse = "There are no bids for '" . $item_name . "' yet.";
            }
        }

        // ✅ **Normalize User Input for Matching**
        $normalizedUserMessage = strtolower(trim($userMessage));
        $normalizedUserMessage = preg_replace('/[^\w\s]/', '', $normalizedUserMessage); // Remove punctuation
        $normalizedUserMessage = preg_replace('/\s+/', ' ', $normalizedUserMessage); // Remove extra spaces

        // Log normalized input for debugging
        file_put_contents("chatbot_log.txt", date("Y-m-d H:i:s") . " - Normalized Input: " . $normalizedUserMessage . "\n", FILE_APPEND);

        // ✅ **Check for Predefined Responses**
        // First, try exact matching
        $stmt = $conn->prepare("SELECT bot_response FROM chatbot_responses WHERE LOWER(user_input) = ? ORDER BY priority DESC LIMIT 1");
        $stmt->bind_param("s", $normalizedUserMessage);
        $stmt->execute();
        $stmt->store_result();

        // Log the exact matching query
        file_put_contents("chatbot_log.txt", date("Y-m-d H:i:s") . " - Exact Matching Query: SELECT bot_response FROM chatbot_responses WHERE LOWER(user_input) = '" . $normalizedUserMessage . "' ORDER BY priority DESC LIMIT 1\n", FILE_APPEND);

        if ($stmt->num_rows > 0) {
            $stmt->bind_result($botResponse);
            $stmt->fetch();
            // Log the matched response
            file_put_contents("chatbot_log.txt", date("Y-m-d H:i:s") . " - Matched Response: " . $botResponse . "\n", FILE_APPEND);
        } else {
            // If no exact match, fall back to keyword-based matching with a similarity threshold
            $stmt = $conn->prepare("
                SELECT bot_response, 
                       MATCH(keywords) AGAINST(? IN BOOLEAN MODE) AS score 
                FROM chatbot_responses 
                WHERE MATCH(keywords) AGAINST(? IN BOOLEAN MODE) 
                ORDER BY score DESC, priority DESC 
                LIMIT 1
            ");
            $stmt->bind_param("ss", $normalizedUserMessage, $normalizedUserMessage);
            $stmt->execute();
            $stmt->store_result();

            // Log the keyword matching query
            file_put_contents("chatbot_log.txt", date("Y-m-d H:i:s") . " - Keyword Matching Query: SELECT bot_response, MATCH(keywords) AGAINST('" . $normalizedUserMessage . "' IN BOOLEAN MODE) AS score FROM chatbot_responses WHERE MATCH(keywords) AGAINST('" . $normalizedUserMessage . "' IN BOOLEAN MODE) ORDER BY score DESC, priority DESC LIMIT 1\n", FILE_APPEND);

            if ($stmt->num_rows > 0) {
                $stmt->bind_result($botResponse, $score);
                $stmt->fetch();

                // Set a similarity threshold (e.g., score > 1)
                if ($score > 1) {
                    // Log the matched response
                    file_put_contents("chatbot_log.txt", date("Y-m-d H:i:s") . " - Matched Response: " . $botResponse . " (Score: " . $score . ")\n", FILE_APPEND);
                } else {
                    // If no match meets the threshold, store the unknown query
                    $botResponse = "I'm not sure about that. Would you like to teach me?";
                    $stmt = $conn->prepare("INSERT INTO chatbot_training (user_input) VALUES (?)");
                    $stmt->bind_param("s", $userMessage);
                    $stmt->execute();
                }
            } else {
                // ✅ **Store Unknown Queries for Future Training**
                $botResponse = "I'm not sure about that. Would you like to teach me?";
                $stmt = $conn->prepare("INSERT INTO chatbot_training (user_input) VALUES (?)");
                $stmt->bind_param("s", $userMessage);
                $stmt->execute();
            }
        }
    }

    // Store bot response in chat history
    $_SESSION['chat_history'][] = ["bot" => $botResponse];

    // Return the bot response
    echo json_encode(["reply" => $botResponse]);

    // Close database connection
    $stmt->close();
    $conn->close();
} else {
    echo json_encode(["reply" => "Invalid request!"]);
}
?>