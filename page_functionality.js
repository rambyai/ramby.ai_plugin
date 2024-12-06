document.addEventListener("DOMContentLoaded", () => {
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

    window.parent.postMessage({ type: "loadKey" }, "*");

    window.addEventListener("message", (event) => {
        if (event.data.type === "settingsLoaded") {
            const { service, apiKey } = event.data.payload || {};
            if (service && apiKey) {
                console.log("Settings loaded in UI:", service, apiKey);

                // Populate UI fields
                document.getElementById("api-service").value = service;
                document.getElementById("api-key").value = apiKey;
            }
        }
    });
    
});

/* START SAVE SETTING FUNCTIONS */

function encryptData(data) {
    try {
        return btoa(data); // Base64 encode
    } catch (error) {
        console.error("Error encrypting data:", error);
        return null;
    }
}

function decryptData(encryptedData) {
    try {
        return atob(encryptedData); // Base64 decode
    } catch (error) {
        console.error("Error decrypting data:", error);
        return null;
    }
}

/* END SAVE SETTING */