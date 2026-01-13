document.addEventListener("DOMContentLoaded", function () {
    const chatToggle = document.getElementById("chat-toggle");
    const chatContainer = document.getElementById("chat-container");
    const closeChat = document.getElementById("close-chat");
    const chatInput = document.getElementById("chat-input");
    const sendChatBtn = document.getElementById("send-chat");
    const chatMessages = document.getElementById("chat-messages");

    console.log("✅ chatbot.js is loaded and running!");

    if (!chatToggle || !chatContainer || !closeChat || !chatInput || !sendChatBtn || !chatMessages) {
        console.error("❌ Missing one or more chatbot elements!");
        return;
    }

    chatContainer.style.display = "none";

    chatToggle.addEventListener("click", function () {
        console.log("✅ Chat icon clicked!");
        chatContainer.style.display = (chatContainer.style.display === "none" || chatContainer.style.display === "") ? "block" : "none";
        autoScroll(); // Ensure the latest message is visible when chat is opened
    });

    closeChat.addEventListener("click", function () {
        console.log("✅ Chat closed!");
        chatContainer.style.display = "none";
    });

    function sendMessage() {
        let userMessage = chatInput.value.trim();
        if (userMessage === "") return;

        displayMessage(userMessage, "user-message");

        chatInput.value = "";
        chatInput.disabled = true; // Disable input while waiting
        sendChatBtn.disabled = true;

        console.log(`📨 Sending message: ${userMessage}`);

        // Show "typing..." effect
        const typingIndicator = document.createElement("div");
        typingIndicator.textContent = "Typing...";
        typingIndicator.classList.add("bot-message", "typing-indicator");
        chatMessages.appendChild(typingIndicator);
        autoScroll(); // Auto-scroll to latest message

        fetch("chatbot.php", {
            method: "POST",
            headers: { "Content-Type": "application/json" },
            body: JSON.stringify({ message: userMessage })
        })
        .then(response => response.json())
        .then(data => {
            console.log(`🤖 Bot reply: ${data.reply}`);
            chatMessages.removeChild(typingIndicator); // Remove typing indicator
            displayMessage(data.reply, "bot-message");
        })
        .catch(error => {
            console.error("❌ Error connecting to chatbot:", error);
            chatMessages.removeChild(typingIndicator);
            displayMessage("Sorry, there was an error connecting to the chatbot.", "bot-message");
        })
        .finally(() => {
            chatInput.disabled = false; // Re-enable input
            sendChatBtn.disabled = false;
            chatInput.focus();
        });
    }

    function displayMessage(message, className) {
        let messageDiv = document.createElement("div");
        messageDiv.classList.add(className);

        // Format the message for bot responses
        if (className === "bot-message") {
            messageDiv.innerHTML = formatMessage(message); // Use innerHTML for formatting
        } else {
            messageDiv.textContent = message; // Plain text for user messages
        }

        chatMessages.appendChild(messageDiv);
        autoScroll(); // ✅ Always scroll to the bottom after adding a new message
    }

    function formatMessage(message) {
        // Format bold text and lists
        return message
            .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>') // Bold text
            .replace(/\n/g, '<br>') // Line breaks
            .replace(/•\s*(.*?)(<br>|$)/g, '<li>$1</li>') // Bullet points
            .replace(/<li>/g, '<ul><li>') // Start unordered list
            .replace(/<\/li>/g, '</li></ul>'); // End unordered list
    }

    function autoScroll() {
        setTimeout(() => {
            chatMessages.scrollTop = chatMessages.scrollHeight;
        }, 50); // Shorter delay for faster scrolling
    }

    sendChatBtn.addEventListener("click", sendMessage);
    chatInput.addEventListener("keypress", function (event) {
        if (event.key === "Enter") {
            sendMessage();
        }
    });

    chatInput.disabled = false;
});