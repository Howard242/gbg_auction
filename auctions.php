<?php 
session_start();
include $_SERVER['DOCUMENT_ROOT'] . '/gbg_auction/php/db_config.php';

// Include the navbar
include $_SERVER['DOCUMENT_ROOT'] . '/gbg_auction/templates/navbar.php';

// Move expired auctions to 'sold' or 'expired' before displaying active ones
require $_SERVER['DOCUMENT_ROOT'] . '/gbg_auction/php/auction_expiry.php';
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Auctions | GoBidGo</title>
    
    <!-- Bootstrap & Custom CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="css/style.css">
</head>
<body>

<div class="container mt-5">
    <h2 class="text-center">Live Auctions</h2>
    <div id="live-auctions" class="row"></div>

    <h2 class="text-center mt-4">Timed Auctions</h2>
    <div id="timed-auctions" class="row"></div>

    <h2 class="text-center mt-4 text-success">Sold Items</h2>
    <div id="sold-auctions" class="row"></div>

    <h2 class="text-center mt-4 text-danger">Expired Auctions</h2>
    <div id="expired-auctions" class="row"></div>
</div>

<!-- JavaScript -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
document.addEventListener("DOMContentLoaded", function() {
    fetch('/gbg_auction/php/fetch_auctions.php')  // Fetch all auctions
        .then(response => response.json())
        .then(data => {
            console.log("Fetched Auctions Data:", data); // Debugging log

            let liveContainer = document.getElementById("live-auctions");
            let timedContainer = document.getElementById("timed-auctions");
            let soldContainer = document.getElementById("sold-auctions");
            let expiredContainer = document.getElementById("expired-auctions");

            liveContainer.innerHTML = '';
            timedContainer.innerHTML = '';
            soldContainer.innerHTML = '';
            expiredContainer.innerHTML = '';

            // Check if the response contains valid data
            if (data.live) {
                data.live.forEach(auction => {
                    if (new Date(auction.end_time).getTime() > new Date().getTime()) {
                        addAuction(liveContainer, auction);
                    }
                });
            }
            if (data.timed) {
                data.timed.forEach(auction => {
                    if (new Date(auction.end_time).getTime() > new Date().getTime()) {
                        addAuction(timedContainer, auction);
                    }
                });
            }
            if (data.sold) {
                data.sold.forEach(auction => addSoldAuction(soldContainer, auction));
            }
            if (data.expired) {
                data.expired.forEach(auction => addExpiredAuction(expiredContainer, auction));
            }
        })
        .catch(error => console.error('Error fetching auctions:', error));
});

// Function to create auction cards (for active auctions)
function addAuction(container, auction) {
    let endTime = new Date(auction.end_time).getTime();
    let auctionDiv = document.createElement("div");
    auctionDiv.className = "col-md-4 mb-4";
    auctionDiv.innerHTML = `
        <div class="card shadow">
            <img src="uploads/${auction.image}" class="card-img-top" alt="${auction.title}">
            <div class="card-body">
                <h5 class="card-title">${auction.title}</h5>
                <p class="card-text">Starting Price: Ksh ${parseFloat(auction.starting_price).toFixed(2)}</p>
                <p class="countdown" id="countdown-${auction.id}"></p>
                <a href="auction_details.php?id=${auction.id}" class="btn btn-primary">View Auction</a>
            </div>
        </div>
    `;
    container.appendChild(auctionDiv);

    if (container.id === "live-auctions" || container.id === "timed-auctions") {
        startCountdown(`countdown-${auction.id}`, endTime);
    }
}

// Function to create Sold Auctions cards
function addSoldAuction(container, auction) {
    let auctionDiv = document.createElement("div");
    auctionDiv.className = "col-md-4 mb-4";
    auctionDiv.innerHTML = `
        <div class="card shadow border-success">
            <img src="uploads/${auction.image}" class="card-img-top" alt="${auction.title}">
            <div class="card-body">
                <h5 class="card-title">${auction.title}</h5>
                <p class="text-success"><strong>Sold for:</strong> Ksh ${parseFloat(auction.final_price).toFixed(2)}</p>
                <p><strong>Seller:</strong> ${auction.seller_name}</p>
                <p><strong>Auction Ended:</strong> ${new Date(auction.end_time).toLocaleString()}</p>
            </div>
        </div>
    `;
    container.appendChild(auctionDiv);
}

// Function to create Expired Auctions cards
function addExpiredAuction(container, auction) {
    let auctionDiv = document.createElement("div");
    auctionDiv.className = "col-md-4 mb-4";
    auctionDiv.innerHTML = `
        <div class="card shadow border-danger">
            <img src="uploads/${auction.image}" class="card-img-top" alt="${auction.title}">
            <div class="card-body">
                <h5 class="card-title">${auction.title}</h5>
                <p><strong>Starting Price:</strong> Ksh ${parseFloat(auction.starting_price).toFixed(2)}</p>
                <p><strong>Seller:</strong> ${auction.seller_name}</p>
                <p><strong>Auction Ended:</strong> ${new Date(auction.end_time).toLocaleString()}</p>
                <button class="btn btn-primary relist-button" data-title="${auction.title}" data-seller-id="${auction.seller_id}">Relist</button>
            </div>
        </div>
    `;
    container.appendChild(auctionDiv);
}

// Countdown Timer Function
function startCountdown(elementId, endTime) {
    let countdownElement = document.getElementById(elementId);
    
    let countdownInterval = setInterval(function() {
        let now = new Date().getTime();
        let timeLeft = endTime - now;

        if (timeLeft <= 0) {
            clearInterval(countdownInterval);
            countdownElement.innerHTML = "Auction Ended";
            return;
        }

        let days = Math.floor(timeLeft / (1000 * 60 * 60 * 24));
        let hours = Math.floor((timeLeft % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        let minutes = Math.floor((timeLeft % (1000 * 60 * 60)) / (1000 * 60));
        let seconds = Math.floor((timeLeft % (1000 * 60)) / 1000);

        countdownElement.innerHTML = `${days}d ${hours}h ${minutes}m ${seconds}s`;
    }, 1000);
}

// Relist Button Functionality
document.addEventListener("click", function(event) {
    if (event.target.classList.contains("relist-button")) {
        event.preventDefault();

        const title = event.target.getAttribute("data-title"); // Use title instead of auction_id
        const sellerId = event.target.getAttribute("data-seller-id");
        const currentUserId = "<?php echo $_SESSION['user_id'] ?? ''; ?>"; // Get current user ID from session

        console.log("Relist button clicked"); // Debugging: Check if the button is clicked
        console.log(`Title: ${title}, Seller ID: ${sellerId}, Current User ID: ${currentUserId}`); // Debugging: Log IDs

        if (currentUserId === sellerId) {
            // Seller is relisting the item
            console.log("Seller is relisting the item"); // Debugging: Check if seller logic is triggered
            relistItem(title); // Pass title instead of auction_id
        } else {
            // Buyer is requesting to relist the item
            console.log("Buyer is requesting to relist the item"); // Debugging: Check if buyer logic is triggered
            requestRelist(title); // Pass title instead of auction_id
        }
    }
});

function relistItem(title) {
    console.log(`Relisting item with title: ${title}`); // Debugging: Log title
    fetch('/gbg_auction/php/relist_item.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ title: title }), // Send title in the request body
    })
    .then(response => response.json())
    .then(data => {
        console.log("Relist response:", data); // Debugging: Log the response
        if (data.success) {
            alert('Item has been relisted.');
            window.location.reload(); // Refresh the page to reflect changes
        } else {
            alert(data.message || 'Failed to relist the item. Please try again.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred. Please check the console for details.');
    });
}

function requestRelist(title) {
    console.log(`Requesting relist for item with title: ${title}`); // Debugging: Log title
    fetch('/gbg_auction/php/request_relist.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ title: title }), // Send title in the request body
    })
    .then(response => response.json())
    .then(data => {
        console.log("Request relist response:", data); // Debugging: Log the response
        if (data.success) {
            alert('A message has been sent to the seller to relist the item.');
        } else {
            alert(data.message || 'Failed to send the request. Please try again.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred. Please check the console for details.');
    });
}
// Relist Button Functionality
document.addEventListener("click", function(event) {
    if (event.target.classList.contains("relist-button")) {
        event.preventDefault();

        const title = event.target.getAttribute("data-title"); // Use title instead of auction_id
        const currentUserId = "<?php echo $_SESSION['user_id'] ?? ''; ?>"; // Get current user ID from session

        console.log("Relist button clicked"); // Debugging: Check if the button is clicked
        console.log(`Title: ${title}, Current User ID: ${currentUserId}`); // Debugging: Log IDs

        relistItem(title); // Pass title to relist function
    }
});

function relistItem(title) {
    console.log(`Relisting item with title: ${title}`); // Debugging: Log title
    fetch('/gbg_auction/php/relist_item.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ title: title }), // Send title in the request body
    })
    .then(response => response.json())
    .then(data => {
        console.log("Relist response:", data); // Debugging: Log the response
        if (data.success) {
            alert('Item has been relisted.');
            window.location.reload(); // Refresh the page to reflect changes
        } else {
            alert(data.message || 'Failed to relist the item. Please try again.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('An error occurred. Please check the console for details.');
    });
}


</script>
<?php include 'templates/footer.php'; ?>

</body>
</html>