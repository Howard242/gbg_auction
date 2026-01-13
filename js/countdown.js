document.addEventListener("DOMContentLoaded", function() {
    fetch('fetch_auctions.php')
        .then(response => response.json())
        .then(data => {
            let liveAuctionsContainer = document.getElementById("live-auctions");
            let timedAuctionsContainer = document.getElementById("timed-auctions");

            liveAuctionsContainer.innerHTML = '';
            timedAuctionsContainer.innerHTML = '';

            data.forEach(auction => {
                let endTime = new Date(auction.end_time).getTime();
                let auctionDiv = document.createElement("div");
                auctionDiv.className = "col-md-4 auction-item";

                auctionDiv.innerHTML = `
                    <div class="card">
                        <img src="uploads/${auction.image}" class="card-img-top" alt="${auction.title}">
                        <div class="card-body">
                            <h5 class="card-title">${auction.title}</h5>
                            <p class="card-text">Starting Price: Ksh ${parseFloat(auction.starting_price).toFixed(2)}</p>
                            <p class="countdown" id="countdown-${auction.id}"></p>
                            <a href="auction_details.php?id=${auction.id}" class="btn btn-primary">View Auction</a>
                        </div>
                    </div>
                `;

                // Append to correct auction type section
                if (auction.auction_type === "live") {
                    liveAuctionsContainer.appendChild(auctionDiv);
                    startCountdown(`countdown-${auction.id}`, endTime);
                } else {
                    timedAuctionsContainer.appendChild(auctionDiv);
                }
            });
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
            countdownElement.innerHTML = "<strong>Auction Ended</strong>";
            return;
        }

        let days = Math.floor(timeLeft / (1000 * 60 * 60 * 24));
        let hours = Math.floor((timeLeft % (1000 * 60 * 60 * 24)) / (1000 * 60 * 60));
        let minutes = Math.floor((timeLeft % (1000 * 60 * 60)) / (1000 * 60));
        let seconds = Math.floor((timeLeft % (1000 * 60)) / 1000);

        countdownElement.innerHTML = `${days}d ${hours}h ${minutes}m ${seconds}s`;
    }, 1000);
}
