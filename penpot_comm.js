// UI/UX Setup
const pageStructureElement = document.getElementById("page-structure");

document.addEventListener("DOMContentLoaded", () => {
    // Setup the plugin tabs
    const tabs = document.querySelectorAll(".tab");
    const tabContents = document.querySelectorAll(".tab-content");
    tabs.forEach((tab) => {
        tab.addEventListener("click", () => {
            // Remove active class from all tabs and tab contents
            tabs.forEach((t) => t.classList.remove("active"));
            tabContents.forEach((content) => content.classList.remove("active"));

            // Add active class to the clicked tab and the corresponding tab content
            tab.classList.add("active");
            const target = document.getElementById(tab.getAttribute("data-tab"));
            target.classList.add("active");
        });
    });

    // Load the settings
    window.parent.postMessage({ type: "loadSettings" }, "*");    

    // Copy JSON output for troubleshooting
    document.getElementById('label-for-json-input').addEventListener('click', () => {
        const textarea = document.getElementById('json-input');
        textarea.style.display = 'block';
    });

});

// Listen for messages from the plugin
// Consolidated and async-compatible event listener
window.addEventListener("message", async (event) => {
    const message = event.data;

    // Log the received message for debugging
    console.log("Message from plugin:", message);

    // Handle "settingsLoaded" event
    if (message.type === "settingsLoaded") {
        const { service, apiKey } = message.payload || {};
        if (service && apiKey) {
            document.getElementById("api-service").value = service;
            document.getElementById("api-key").value = apiKey;
        }
    }

    // Handle text settings update
    if (message.type === "update-text-settings") {
        const settings = message.payload;
        document.getElementById("font-size").value = settings.fontSize;
        document.getElementById("font-family").value = settings.fontFamily;
        document.getElementById("text-characters").innerText = settings.characters;
    }

    // Handle clearing text settings
    if (message.type === "clear-settings") {
        document.getElementById("font-size").value = "";
        document.getElementById("font-family").value = "";
        document.getElementById("text-characters").innerText = "No element selected.";
    }

    if (message.type === "callAIAPI") {
        const { service, apiKey } = message.payload;
        const prompt = document.getElementById("ai-prompt").value.trim();

        if (!service || !apiKey || !prompt) {
            console.error("Received incomplete credentials or prompt.");
            alert("Failed to fetch API credentials or prompt. Check the console for details.");
            return;
        }

        try {
            // Send a waiting message to the user
            showWaitingDialog("Generating design from AI...");

            let aiResponse = null;
            switch (service) {
                case "openai":
                    console.warn( 'testing 6' );
                    aiResponse = await callChatGPT(apiKey, prompt);
//                    Testing without hitting API for now. 
//                    aiResponse = "";
                    break;
                default:
                    console.error(`Unsupported AI service: ${service}`);
                    hideWaitingDialog();
                    alert(`Unsupported AI service: ${service}`);
                    return;
            }

            if (aiResponse) {
                console.log("AI response received:", aiResponse);
                // Send the generated JSON back to Penpot
                window.parent.postMessage( { type: "load-json", payload: aiResponse }, "*" );
                hideWaitingDialog();
            } else {
                console.error("Failed to generate design.");
                hideWaitingDialog();
                alert("Failed to generate design. Check the console for details.");
            }
        } catch (error) {
            console.error("Error handling AI call:", error);
            hideWaitingDialog();
            alert("An error occurred while processing the AI call.");
        }
    }

    if (message.type === "waiting") {
        showWaitingDialog(message.payload);
    }

    if (message.type === "error") {
        hideWaitingDialog();
        alert(`Error: ${message.payload}`);
    }

    if (message.type == "showPageStructure") {
        pageStructureElement.textContent = JSON.stringify(event.data.payload, null, 2);
    }

});

// AI Calls
document.getElementById("send-to-ai").addEventListener("click", async () => {
    const prompt = document.getElementById("ai-prompt").value.trim();

    if (!prompt) {
        alert("Please enter a prompt.");
        return;
    }

    // Send a request to Penpot to fetch the credentials
    try {
        window.parent.postMessage({ type: "getAPICredentials"}, "*" );
        console.log("Request sent to fetch API credentials.");
    } catch (error) {
        console.error("Error sending request to fetch credentials:", error);
        alert("Failed to fetch API credentials. Check the console for details.");
    }
});

// Listen for the "Load JSON" button click
document.getElementById("load-json").addEventListener("click", () => {
    const jsonInput = document.getElementById("json-input").value;
    try {
        // Parse the JSON to ensure it's valid
        const jsonData = JSON.parse(jsonInput);
        // Send the JSON to the plugin
        window.parent.postMessage({ type: "load-json", payload: jsonData }, "*");
    } catch (e) {
        console.error("Invalid JSON:", e.message);
        alert("Please enter valid JSON!");
    }
});

// Send a "get-page-json" request to the plugin
document.getElementById("get-page-json").addEventListener("click", () => {
    window.parent.postMessage("get-page-json", "*");
    console.log("Request for page json sent to plugin.");
});

// Save Settings
document.getElementById("save-settings").addEventListener("click", () => {
    const service = document.getElementById("api-service").value;
    const apiKey = document.getElementById("api-key").value;

    if (!service || !apiKey) {
        displayMessage("Please select a service and enter an API key.", "red");
        return;
    }
    try {
        window.parent.postMessage({ type: "saveSettings", payload: { service, apiKey } }, "*");
        displayMessage("Settings saved successfully!", "green");
    } catch (error) {
        displayMessage("Failed to save settings. Please try again.", "red");
    }
});

document.getElementById("copy-icon").addEventListener("click", () => {
console.warn( 'x' );
    const textarea = document.getElementById("page-structure");
    if (!textarea.value) {
        alert("No content to select.");
        return;
    }
    textarea.removeAttribute("DISABLED"); // Temporarily enable selection
    textarea.select(); // Select the content
    textarea.setSelectionRange(0, textarea.value.length); // For compatibility
    textarea.setAttribute("DISABLED", "true"); // Re-disable the textarea
});

// FUNCTIONS
// Function to display message when save returns
function displayMessage(message, color) {
    const messageElement = document.getElementById("save-message");
    messageElement.textContent = message;
    messageElement.style.color = color;
    messageElement.style.display = "block";

    // Hide the message after 3 seconds
    setTimeout(() => {
        messageElement.style.display = "none";
    }, 3000);
}

// Call OpenAI via the proxy
function callChatGPT(apiKey, prompt) {
    return new Promise((resolve, reject) => {
        const xhr = new XMLHttpRequest();
        xhr.open("POST", "https://api.openai.com/v1/chat/completions");
        xhr.setRequestHeader("Authorization", `Bearer ${apiKey}`);
        xhr.setRequestHeader("Content-Type", "application/json");

        xhr.onreadystatechange = function () {
            if (xhr.readyState === 4) {
                if (xhr.status === 200) {
                    try {
                        const response = JSON.parse(xhr.responseText);
                        resolve(response.choices[0].message.content);
                    } catch (err) {
                        reject("Error parsing response: " + err.message);
                    }
                } else {
                    reject("HTTP Error: " + xhr.statusText);
                }
            }
        };

        const body = JSON.stringify({
            model: "ft:gpt-3.5-turbo-1106:personal:rambyai-penpot:AeeWexkJ",
            messages: [
                { role: "system", content: "You are an AI assistant generating Penpot design JSON." },
                { role: "user", content: prompt },
            ],
        });

        try {
            xhr.send(body);
        } catch (error) {
            reject("Request failed: " + error.message);
        }
    });
}

function showWaitingDialog(message) {
    const waitingDialog = document.getElementById("waiting-dialog");
    waitingDialog.querySelector("p").textContent = message || "Please wait...";
    waitingDialog.style.display = "block";
}

function hideWaitingDialog() {
    const waitingDialog = document.getElementById("waiting-dialog");
    waitingDialog.style.display = "none";
}
