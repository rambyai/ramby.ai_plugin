<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/javascript; charset=utf-8");
?>

penpot.ui.open("Design with AI", "http://localhost:80/index.php", { width: 300, height: 900 });

penpot.ui.onMessage(async (message) => {
    try {
        let action = null;
        if (typeof message === "string") {
            action = message; // Directly use string messages as action
        } else if (typeof message === "object" && message !== null && message.type) {
            action = message.type; // Use the `type` property from object messages
        }

        console.log("Determined action:", action);

        switch (action) {
            case "getAPICredentials":
                try {
                    // Fetch the saved settings from Penpot
                    const settings = await penpot.root.getPluginData("aiSettings");
                    if (!settings) {
                        console.error("API settings not found. Please configure your API key and service.");
                        penpot.ui.sendMessage({
                            type: "error",
                            payload: "API settings are not configured.",
                        });
                        return;
                    }

                    const { service, apiKey } = JSON.parse(settings);

                    if (!service || !apiKey ) {
                        console.error("Service or API Key is missing.");
                        penpot.ui.sendMessage({
                            type: "error",
                            payload: "Service or API Key is missing.",
                        });
                        return;
                    }

                    // Send the service, API key, and prompt back to the UI
                    penpot.ui.sendMessage({
                        type: "callAIAPI",
                        payload: { service, apiKey },
                    });
                } catch (error) {
                    console.error("Error fetching AI settings:", error);
                    penpot.ui.sendMessage({
                        type: "error",
                        payload: "An error occurred while fetching AI settings.",
                    });
                }
                break;

            case "saveSettings":
                const { service, apiKey } = message.payload;
                await penpot.root.setPluginData("aiSettings", JSON.stringify({ service, apiKey }));
                console.log(`Settings saved: Service - ${service}, API Key - ${apiKey}`);
                break;

            case "loadSettings":
                const data = await penpot.root.getPluginData("aiSettings");
                if (data) {
                    const { service, apiKey } = JSON.parse(data);
                    penpot.ui.sendMessage({ type: "settingsLoaded", payload: { service, apiKey } });
                } else {
                    console.warn("No settings found.");
                    penpot.ui.sendMessage({ type: "settingsLoaded", payload: null });
                }
                break;

            default:
                console.warn(`Unhandled action: ${action}`);
                break;
        }
    } catch (error) {
        console.error("Error handling message:", error);
    }
});

penpot.on("selectionchange", (selection) => {
    try {
        if (!selection || !selection.length) {
            console.warn("No elements selected.");
            return;
        }

        console.log("Selected node IDs:", selection);

        // Iterate over all selected IDs
        selection.forEach((selectedId) => {
            // Find the shape using the ID
            const selectedShape = penpot.currentPage.getShapeById(selectedId);

            if (!selectedShape) {
                console.error("Shape not found for ID:", selectedId);
                return;
            }
        });

        // Notify the UI or perform additional actions if needed
        penpot.ui.sendMessage({ type: "update", payload: { selection } });

        // Activate the "Component Design" tab
        try {
            activateTab("component-design");
        } catch (error) {
            console.error("Error switching tabs:", error);
        }

    } catch (error) {
        console.error("Error handling selection change:", error);
    }
});

function clearBoard() {
    try {
        // Get all shapes on the current page
        const shapes = penpot.currentPage.findShapes();

        // Check if shapes exist
        if (!shapes || shapes.length === 0) {
            console.warn("The board is already empty!");
            return;
        }

        // Filter out non-removable shapes like the root frame
        const removableShapes = shapes.filter((shape) => shape.type !== "board");

        // Iterate through shapes and remove each
        removableShapes.forEach((shape) => {
            try {
                shape.remove();
            } catch (error) {
                console.error(`Error removing shape (${shape.name || shape.id}):`, error);
            }
        });

        console.log(`Cleared ${removableShapes.length} shapes from the board.`);
    } catch (error) {
        console.error("Error clearing the board:", error);
    }
}

function createRectangle(shape) {
    const rect = penpot.createRectangle();
    rect.name = shape.name || "Rectangle";
    rect.x = shape.position.x;
    rect.y = shape.position.y;
    rect.width = shape.dimensions.width;
    rect.height = shape.dimensions.height;
    rect.fills = shape.color || [];
}

function createText(shape) {
    const characters = shape.characters || "Default Text";
    const fontSize = shape.fontSize || 16; // Default font size
    const fontFamily = shape.fontFamily || "sourcesanspro"; // Default font family

    const text = penpot.createText(characters);
    text.name = shape.name || "Text";
    text.x = shape.position?.x || 0;
    text.y = shape.position?.y || 0;
    text.fontFamily = fontFamily;
    text.fontSize = fontSize;
    text.fills = shape.color || [{ fillColor: "#000000", fillOpacity: 1 }];
}

function createEllipse(shape) {
    const ellipse = penpot.createEllipse();
    ellipse.name = shape.name || "Ellipse";
    ellipse.x = shape.position.x;
    ellipse.y = shape.position.y;
    ellipse.width = shape.dimensions.width;
    ellipse.height = shape.dimensions.height;
    ellipse.fills = shape.color || [];
}

function validateFont(fontFamily) {
    const availableFonts = ["sourcesanspro", "inter", "roboto", "opensans"]; // Add Penpot-supported fonts
    if (availableFonts.includes(fontFamily.toLowerCase())) {
        return fontFamily.toLowerCase();
    } else {
        console.warn(`Font "${fontFamily}" not supported. Defaulting to "sourcesanspro".`);
        return "sourcesanspro"; // Default fallback font
    }
}

function buildPageFromJSON(json) {
    try {
        // Iterate over each shape in the JSON
        json.shapes.forEach((shapeData) => {
            let newShape;

            // Validate shapeData structure
            if (!shapeData || !shapeData.type || !shapeData.position) {
                console.warn("Invalid shape data:", shapeData);
                return;
            }

            // Create the shape based on its type
            switch (shapeData.type) {
                case "rectangle":
                    newShape = penpot.createRectangle();
                    newShape.name = shapeData.name || "Unnamed Rectangle";
                    newShape.resize(shapeData.dimensions.width, shapeData.dimensions.height);
                    newShape.x = shapeData.position.x;
                    newShape.y = shapeData.position.y;
                    if (shapeData.color) newShape.fills = shapeData.color;
                    break;

                case "ellipse":
                    newShape = penpot.createEllipse();
                    newShape.name = shapeData.name || "Unnamed Ellipse";
                    newShape.resize(shapeData.dimensions.width, shapeData.dimensions.height);
                    newShape.x = shapeData.position.x;
                    newShape.y = shapeData.position.y;
                    if (shapeData.color) newShape.fills = shapeData.color;
                    break;

                case "text":
                    try {
                        // Use the dedicated `createText` function for validation and creation
                        createText(shapeData);
                    } catch (error) {
                        console.warn("Failed to create text shape:", shapeData, error);
                    }
                    break;

                default:
                    console.warn(`Unknown shape type: ${shapeData.type}`);
            }
        });
    } catch (error) {
        console.error("Error building page from JSON:", error);
    }
}
