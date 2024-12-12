// Listen for messages from the plugin
// Consolidated and async-compatible event listener
window.addEventListener("message", async (event) => {
    const message = event.data;

    // Log the received message for debugging
    console.log("Message from plugin:", message);

    // Handle the "callAI" action
    if (message.type === "callAI") {
        console.warn("Processing AI call...");
        const { service, apiKey, prompt } = message.payload;

        try {
            let aiResponse;
            switch (service) {
                case "openai":
                    aiResponse = await callChatGPT(apiKey, prompt);
                    break;
                case "anthropic":
                    aiResponse = await callAnthropic(apiKey, prompt);
                    break;
                default:
                    console.error(`Unsupported AI service: ${service}`);
                    return;
            }

            if (aiResponse) {
                // Send the generated JSON back to the Penpot plugin
                window.parent.postMessage({ type: "loadJSON", payload: aiResponse }, "*");
            }
        } catch (error) {
            console.error("Error handling AI call:", error);
        }
        return; // Exit early for "callAI" to prevent further processing
    }

    // Handle pretty-printing JSON messages in the UI
    if (message && typeof message === "object") {
        outputElement.textContent = JSON.stringify(message, null, 2);
    } else {
        outputElement.textContent = message;
    }

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
});

const outputElement = document.getElementById("output");

// AI Calls
document.getElementById("send-to-ai").addEventListener("click", () => {
    const prompt = document.getElementById("ai-prompt").value.trim();

    if (!prompt) {
        alert("Please enter a prompt.");
        return;
    }

    // Send the prompt to the plugin
    window.parent.postMessage({ type: "sendToAI", payload: { prompt } }, "*");
    console.log("Prompt sent to AI.");
});

// TESTING
document.getElementById("send-to-ai-directly").addEventListener("click", () => {
    callChatGPT('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', 'Please design me a page that has 4 boxes, all the same width, aligned left. Include 2 circles, one above the boxes and one below. The circles should be smaller and centered with the boxes.')
});

window.addEventListener("message", (event) => {
    if (event.data.type === "loadJSON") {
        const json = event.data.payload;

        // Show the JSON in the textarea (optional, for debugging)
        document.getElementById("json-input").value = JSON.stringify(json, null, 2);
    }
});
// End AI Calls

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
    window.parent.postMessage("getPageJSON", "*");
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

// API Calls
// Call OpenAI via the proxy
async function callChatGPT(apiKey, prompt) {
    try {
        const response = await fetch("https://ramby.ai/proxy/", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
            },
            body: JSON.stringify({
                api_key: apiKey,
                service: "openai",
                messages: [
                    { role: "system", content: "You are an AI assistant generating Penpot design JSON." },
                    { role: "user", content: prompt },
                ],
                model: "gpt-4", // Optional model selection
            }),
        });

        const data = await response.json();
        if (data.choices && data.choices[0] && data.choices[0].message) {
            console.log("ChatGPT response:", data.choices[0].message.content);
            return JSON.parse(data.choices[0].message.content);
        } else {
            console.error("Invalid response from ChatGPT:", data);
            return null;
        }
    } catch (error) {
        console.error("Error communicating with ChatGPT:", error);
        return null;
    }
}

// Call Anthropic via the proxy
async function callAnthropic(apiKey, prompt) {
    try {
        const response = await fetch("http://localhost/proxy/", {
            method: "POST",
            headers: {
                "Content-Type": "application/json",
            },
            body: JSON.stringify({
                api_key: apiKey,
                service: "anthropic",
                messages: [
                    { role: "system", content: "You are an AI assistant generating Penpot design JSON." },
                    { role: "user", content: prompt },
                ],
                model: "claude-v1",
            }),
        });

        const data = await response.json();
        if (data.completion) {
            console.log("Claude response:", data.completion);
            return JSON.parse(data.completion.trim());
        } else {
            console.error("Invalid response from Anthropic:", data);
            return null;
        }
    } catch (error) {
        console.error("Error communicating with Anthropic:", error);
        return null;
    }
}