jQuery(document).ready(function ($) {
    // DOM Elements
    const chatbotToggler = document.querySelector(".bccbai-chatbot-toggler");
    const closeBtn = document.querySelector(".close-btn");
    const chatbox = document.querySelector(".bccbai-chatbox");
    const chatInput = document.querySelector(".bccbai-chat-input textarea");
    const sendChatBtn = document.querySelector(".bccbai-chat-input span");

    // Initial Variables
    let userMessage = null; // Variable to store user's message
    const inputInitHeight = chatInput.scrollHeight;

    // Utility Functions
    const createChatLi = (message, className) => {
        // Create a chat <li> element with passed message and className
        const chatLi = document.createElement("li");
        chatLi.classList.add("bccbai-chat", className);
        let chatContent = className === "outgoing" ? `<p></p>` : `<span class=""><img style="height:50px;" src="${bccbai_chatbot_vars.plugin_url}/assets/img/customer_service.png"></img></span><p></p>`;
        chatLi.innerHTML = chatContent;
        chatLi.querySelector("p").textContent = message;
        return chatLi; // return chat <li> element
    };

    const refreshNonce = () => {
        return new Promise((resolve, reject) => {
            $.ajax({
                url: bccbai_chatbot_vars.ajax_url,
                type: 'POST',
                data: {
                    action: 'bccbai_refresh_nonce'
                },
                success: function (response) {
                    if (response.success) {
                        bccbai_chatbot_vars.nonce = response.data.nonce;
                        
                        resolve();
                    } else {
                        
                        reject();
                    }
                },
                error: function () {
                    
                    reject();
                }
            });
        });
    };


    // Chat Functions
    const generateResponse = (chatElement) => {
        const API_URL = bccbai_chatbot_vars.api_url + "/bccbai/v1/chatbot";
        const messageElement = chatElement.querySelector("p");

        // Get the conversation ID from the cookie or generate a new one
        let conversationId = document.cookie.split('; ').find(row => row.startsWith('bccbai_conversationId='));
        if (conversationId) {
            conversationId = conversationId.split('=')[1];
        } else {
            conversationId = "";
        }

        // Define the properties and message for the API request
        const requestOptions = {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
                "X-WP-Nonce": bccbai_chatbot_vars.nonce,
            },
            body: JSON.stringify({
                message: userMessage,
                conversation_id: conversationId,
            }),
        };

        // Send POST request to API, get response and set the response as paragraph text
        fetch(API_URL, requestOptions)
            .then((res) => {
                if (res.status === 403) {
                    return refreshNonce().then(() => {
                        requestOptions.headers["X-WP-Nonce"] = bccbai_chatbot_vars.nonce;
                        // Retry the fetch after nonce is refreshed
                        return fetch(API_URL, requestOptions);
                    });
                }
                return res;
            })
            .then((res) => res.json())
            .then((response) => {
                data = response.data;
                messageElement.textContent = data.response.trim();
                // Update the conversation ID cookie
                document.cookie = `bccbai_conversationId=${data.conversation_id}; path=/`;
            })
            .catch((errorObj) => {
                messageElement.classList.add("error");
                messageElement.textContent = "Oops! Something went wrong. Please try again." + errorObj;
            })
            .finally(() => chatbox.scrollTo(0, chatbox.scrollHeight));
    };

    const handleChat = () => {
        jQuery('.bccbai-chat-inside').remove();
        userMessage = chatInput.value.trim(); // Get user entered message and remove extra whitespace
        if (!userMessage) return;

        // Clear the input textarea and set its height to default
        chatInput.value = "";
        chatInput.style.height = `${inputInitHeight}px`;

        // Append the user's message to the chatbox
        chatbox.appendChild(createChatLi(userMessage, "outgoing"));
        chatbox.scrollTo(0, chatbox.scrollHeight);

        setTimeout(() => {
            // Display "Thinking..." message while waiting for the response
            const incomingChatLi = createChatLi("Typing...", "incoming");
            chatbox.appendChild(incomingChatLi);
            chatbox.scrollTo(0, chatbox.scrollHeight);

            // Check if nonce is null or undefined
            if (!bccbai_chatbot_vars.nonce) {
                // Refresh nonce first if it is null or undefined
                refreshNonce().done(function () {
                    // After refreshing nonce, make the API call
                    generateResponse(incomingChatLi);
                });
            } else {
                // If nonce is available, make the API call directly
                generateResponse(incomingChatLi);
            }
        }, 100);
    };

    const fetchConversationHistory = () => {
        // Get the conversation ID from the cookie
        let conversationId = document.cookie.split('; ').find(row => row.startsWith('bccbai_conversationId='));
        if (conversationId) {
            conversationId = conversationId.split('=')[1];
    
            const API_URL = `${bccbai_chatbot_vars.api_url}/bccbai/v1/chatbot/history?conversation_id=${conversationId}`;
            const requestOptions = {
                method: "GET",
                headers: {
                    "Content-Type": "application/json",
                    "X-WP-Nonce": bccbai_chatbot_vars.nonce,
                }
            };
    
            fetch(API_URL, requestOptions)
                .then((res) => {
                    if (res.status === 403) {
                        return refreshNonce().then(() => {
                            requestOptions.headers["X-WP-Nonce"] = bccbai_chatbot_vars.nonce;
                            // Retry the fetch after nonce is refreshed
                            return fetch(API_URL, requestOptions);
                        });
                    }
                    return res;
                })
                .then((res) => {
                    if (!res.ok) {
                        throw new Error('Network response was not ok');
                    }
                    return res.json();
                })
                .then((data) => {
                    if (data) {
                        data.conversation_history.forEach(chat => {
                            const className = chat.m_type === 'user' ? 'outgoing' : 'incoming';
                            chatbox.appendChild(createChatLi(chat.message, className));
                        });
                        chatbox.scrollTo(0, chatbox.scrollHeight);
                    }
                })
                .catch((error) => {
                    
                });
        }
    };


    // Event Listeners
    chatInput.addEventListener("input", () => {
        // Adjust the height of the input textarea based on its content
        chatInput.style.height = `${inputInitHeight}px`;
        chatInput.style.height = `${chatInput.scrollHeight}px`;
    });

    chatInput.addEventListener("keydown", (e) => {
        // If Enter key is pressed without Shift key and the window 
        // width is greater than 800px, handle the chat
        if (e.key === "Enter" && !e.shiftKey && window.innerWidth > 800) {
            e.preventDefault();
            handleChat();
        }
    });

    sendChatBtn.addEventListener("click", handleChat);
    closeBtn.addEventListener("click", () => document.body.classList.remove("show-chatbot"));
    chatbotToggler.addEventListener("click", () => document.body.classList.toggle("show-chatbot"));
    jQuery('.bccbai-tooltip-close').on('click', function (event) {
        event.stopPropagation();
        jQuery('.bccbai-chatbot-status').addClass('bccbai-chatbot-status-hide');
    });

    jQuery('.bccbai-chat-inside').on('click', function () {
        // set textarea value
        jQuery('.chat-input textarea').val(jQuery(this).text());
        // trigger click on send button
        jQuery('.chat-input span').trigger('click');
        
        // delete parent
        jQuery(this).parent().remove();
    });

    // Initialization
    refreshNonce().then(() => {
        fetchConversationHistory();
    });
    //setInterval(refreshNonce, 10 * 60 * 1000); // Refresh nonce every 10 minutes
});
