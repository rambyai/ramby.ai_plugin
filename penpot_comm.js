const outputElement = document.getElementById("output");

// Listen for the "Load JSON" button click
document.getElementById("load-json").addEventListener("click", () => {

console.warn( 'load some stuff' );

    const jsonInput = document.getElementById("json-input").value;

    try {
        // Parse the JSON to ensure it's valid
        const jsonData = JSON.parse(jsonInput);
        console.log("Parsed JSON:", jsonData);

        // Send the JSON to the plugin
        window.parent.postMessage({ type: "load-json", payload: jsonData }, "*");
        console.log("JSON sent to plugin.");
    } catch (e) {
        console.error("Invalid JSON:", e.message);
        alert("Please enter valid JSON!");
    }
});

// Send a "get-page-json" request to the plugin
document.getElementById("get-page-structure").addEventListener("click", () => {
    const message = "get-page-json";
    window.parent.postMessage(message, "*");
    console.log("Request for page structure sent to plugin.");
});

document.getElementById("clear-board").addEventListener("click", () => {
    window.parent.postMessage("clear-board", "*");
    console.log("Clear board request sent to plugin.");
});

// Listen for messages from the plugin
window.addEventListener("message", (event) => {
    console.log("Message from plugin:", event.data);

    if (event.data && typeof event.data === "object") {
        // Pretty-print the JSON if it's a valid object
        outputElement.textContent = JSON.stringify(event.data, null, 2);
    } else {
        outputElement.textContent = event.data;
    }
});

window.addEventListener("message", (event) => {
    if (event.data.type === "update-text-settings") {
        const settings = event.data.payload;
        document.getElementById("font-size").value = settings.fontSize;
        document.getElementById("font-family").value = settings.fontFamily;
        document.getElementById("text-characters").innerText = settings.characters;
    } else if (event.data.type === "clear-settings") {
        // Clear the plugin interface when no element is selected
        document.getElementById("font-size").value = "";
        document.getElementById("font-family").value = "";
        document.getElementById("text-characters").innerText = "No element selected.";
    }
});