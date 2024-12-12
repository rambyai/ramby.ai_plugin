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
