document.addEventListener("DOMContentLoaded", function () {
    const chatbotToggle = document.getElementById("chatbot-toggle");
    const chatbotContainer = document.getElementById("chatbot-container");
    const chatbotMessages = document.getElementById("chatbot-messages");
    const modal = document.getElementById("login-modal");
    const closeModal = document.querySelector(".close-modal");
    const chatbotClose = document.getElementById("chatbot-close");

    let isAwaitingResponse = false;
    let listNumber = 1;

    if (chatbotToggle) {
        chatbotToggle.addEventListener("click", () => {
            if (modal) {
                modal.classList.toggle("hidden");
            } else {
                chatbotContainer.classList.toggle("hidden");
            }
            chatbotToggle.classList.toggle("hidden");
        });
    }

    if (chatbotClose) {
        chatbotClose.addEventListener("click", () => {
            chatbotContainer.classList.add("hidden");
            chatbotToggle.classList.remove("hidden");
        });
    }

    if (closeModal) {
        closeModal.addEventListener("click", () => {
            modal.classList.add("hidden");
        });
    }

    function addMessage(content, sender) {
        const lastMessage = chatbotMessages.lastElementChild;
        if (lastMessage && lastMessage.textContent.trim() === content.trim()) {
            return;
        }

        const messageContainer = document.createElement("div");
        const icon = document.createElement("i");
        const text = document.createElement("p");

        text.innerHTML = formatResponse(content);
        icon.className = `message-icon fas fa-${sender === "bot" ? "robot" : "user"}`;
        messageContainer.className = `message ${sender === "bot" ? "bot-message" : "user-message"}`;

        messageContainer.appendChild(icon);
        messageContainer.appendChild(text);
        chatbotMessages.appendChild(messageContainer);
        chatbotMessages.scrollTop = chatbotMessages.scrollHeight;

        listNumber = 1;
    }

    function formatResponse(response) {
        // Replace the various formatting patterns (bold, unordered list, numbered list)
        return response
            .replace(/【\d+:\d+\u2020source】/g, '')  // Remove source annotations.
            .replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>')  // Bold text.
            .replace(/\n- (.*?)(?=\n|$)/g, '<ul><li>$1</li></ul>')  // Unordered list.
            .replace(/\n/g, '<br style="line-height: 1px;">')
            .replace(/\n(\d+)\. (.*?)(?=\n|$)/g, (match, p1, p2) => {
                // Reset the number for each question (always starts at 1)
                return `<ol start="${listNumber++}"><li>${p2}</li></ol>`;
            });
    }

    function setLoadingState(isLoading) {
        isAwaitingResponse = isLoading;
        const inputField = document.getElementById("chatbot-input");
        const sendButton = document.getElementById("chatbot-send");

        if (isLoading) {
            sendButton.disabled = true;
            inputField.disabled = true;
            sendButton.innerHTML = `<img src="${chatbotConfig.pluginUrl}assets/gif/loading.gif" alt="Wait for your response here..." style="width: 30px; height: 30px;">`;
        } else {
            sendButton.disabled = false;
            inputField.disabled = false;
            sendButton.textContent = "Send";
        }
    }

    function bindSendEvent() {
        const sendButton = document.getElementById("chatbot-send");
        const inputField = document.getElementById("chatbot-input");

        function sendMessage() {
            if (isAwaitingResponse) return;

            const question = inputField.value.trim();
            if (!question) {
                addMessage("Please enter a message.", "bot");
                return;
            }

            if (question.length > 300) {
                addMessage("Please keep your message under 300 characters.", "bot");
                return;
            }

            addMessage(question, "user");
            inputField.value = "";
            setLoadingState(true);

            fetch(chatbotConfig.ajaxUrl, {
                method: "POST",
                headers: { "Content-Type": "application/x-www-form-urlencoded" },
                body: new URLSearchParams({
                    action: "chatbot_request",
                    question: question,
                    nonce: chatbotConfig.nonce
                }),
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        addMessage(data.data.answer, "bot");
                    } else {
                        addMessage(`Error: ${data.data.message || 'Unknown error occurred.'}`, "bot");
                    }
                    setLoadingState(false);
                })
                .catch(error => {
                    console.error("Error fetching chatbot response:", error);
                    addMessage("An error occurred while processing your request.", "bot");
                    setLoadingState(false);
                });
        }

        if (sendButton && inputField) {
            sendButton.addEventListener("click", sendMessage);
            inputField.addEventListener("keydown", (event) => {
                if (event.key === "Enter") {
                    event.preventDefault();
                    sendMessage();
                }
            });
        }
    }

    bindSendEvent();
});
