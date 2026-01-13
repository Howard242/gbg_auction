document.addEventListener("DOMContentLoaded", function() {
    fetch('php/fetch_auctions.php')
        .then(response => response.json())
        .then(data => {
            let liveContainer = document.getElementById("live-auctions");
            let timedContainer = document.getElementById("timed-auctions");
            let recommendedContainer = document.getElementById("recommended-section"); 

            liveContainer.innerHTML = '';
            timedContainer.innerHTML = '';
            recommendedContainer.innerHTML = '';

            let recommendedItems = [];

            data.forEach(auction => {
                let endTime = new Date(auction.end_time).getTime();
                let auctionDiv = document.createElement("div");
                auctionDiv.className = "col-md-4 auction-item";

                auctionDiv.innerHTML = `
                    <div class="card mb-4">
                        <img src="uploads/${auction.image}" class="card-img-top" alt="${auction.title}">
                        <div class="card-body">
                            <h5 class="card-title">${auction.title}</h5>
                            <p class="card-text">Starting Price: Ksh ${parseFloat(auction.starting_price).toFixed(2)}</p>
                            <p class="countdown text-danger" id="countdown-${auction.id}"></p>
                            <a href="auction_details.php?id=${auction.id}" class="btn btn-primary">View Auction</a>
                        </div>
                    </div>
                `;

                if (auction.auction_type === "live") {
                    liveContainer.appendChild(auctionDiv);
                } else {
                    timedContainer.appendChild(auctionDiv);
                }

                // Only add active auctions to recommendations
                if (auction.status !== "ended") {
                    recommendedItems.push(auction);
                }

                startCountdown(`countdown-${auction.id}`, endTime);
            });

            // Start rotating recommended items
            if (recommendedItems.length > 0) {
                startImageRotation(recommendedItems);
            }
        })
        .catch(error => console.error('Error fetching auctions:', error));
});

function startCountdown(elementId, endTime) {
    let countdownElement = document.getElementById(elementId);
    
    let countdownInterval = setInterval(function() {
        let now = new Date().getTime();
        let timeLeft = endTime - now;

        if (timeLeft <= 0) {
            clearInterval(countdownInterval);
            countdownElement.innerHTML = "<span class='text-danger'>Auction Ended</span>";
            return;
        }

        let days = Math.floor(timeLeft / (1000 * 60 * 60 * 24));
        let hours = Math.floor((timeLeft % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        let minutes = Math.floor((timeLeft % (1000 * 60 * 60)) / (1000 * 60));
        let seconds = Math.floor((timeLeft % (1000 * 60)) / 1000);

        countdownElement.innerHTML = `<strong>${days}d ${hours}h ${minutes}m ${seconds}s</strong>`;
    }, 1000);
}

// Function to rotate recommended items
function startImageRotation(recommendedItems) {
    let recommendedContainer = document.getElementById("recommended-section");
    let currentIndex = 0;

    function showNextImage() {
        let auction = recommendedItems[currentIndex];
        recommendedContainer.innerHTML = `
            <div class="card recommended-card">
                <img src="uploads/${auction.image}" class="card-img-top recommended-image" alt="${auction.title}">
                <div class="card-body text-center">
                    <h5 class="card-title">${auction.title}</h5>
                    <a href="auction_details.php?id=${auction.id}" class="btn btn-primary">View Item</a>
                </div>
            </div>
        `;

        currentIndex = (currentIndex + 1) % recommendedItems.length;
    }

    showNextImage(); // Show first item immediately
    setInterval(showNextImage, 5000); // Change 5000 to 3000 for a 3-second transition
}
