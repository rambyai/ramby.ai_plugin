<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/javascript; charset=utf-8");
?>

penpot.ui.open("Design with AI", "http://localhost:80/index.php", { width: 300, height: 900 });


// Listen for messages from the iframe
penpot.ui.onMessage(async (message) => {
    
    if( message ==="clear-board") {
      clearBoard();
    }else if (message.type === "load-json") {

      clearBoard();
      const json = message.payload;
      console.log("Received JSON:", json);
      buildPageFromJSON(json);

    } else if (message === "get-page-json") {
        try {

          const shapes = penpot.currentPage.findShapes();
          const allShapeData = shapes.map((shape) => {
            const data = Object.keys(shape).reduce((acc, key) => {
              try {
                acc[key] = shape[key];
              } catch (error) {
                console.warn(`Error accessing property '${key}' on shape`, error);
              }
              return acc;
            }, {});

            // Specific handling for text shapes
            if (shape.type === "text") {
              data.textContent = shape.content || "No content available";
            }
            return data;
          });

          console.log("Full JSON with dynamic properties:", JSON.stringify(allShapeData, null, 2));

        } catch (error) {
            console.error("Error fetching page JSON:", error);
            penpot.ui.sendMessage("Error fetching page JSON", "*");
        }
    } else if (message === "update-text-settings") {
        const settings = message.payload;
        document.getElementById("font-size").value = settings.fontSize;
        document.getElementById("font-family").value = settings.fontFamily;
        document.getElementById("text-characters").innerText = settings.characters;
    } else if (message === "clear-settings") {
        // Clear the plugin interface when no element is selected
        document.getElementById("font-size").value = "";
        document.getElementById("font-family").value = "";
        document.getElementById("text-characters").innerText = "No element selected.";
    } else if (message.type === "saveKey") {
        // Save API key and service
        const { service, apiKey } = message.payload;

        // Encrypt the API key (optional, can be skipped)
        const encryptedKey = encryptData(apiKey);

        await penpot.root.setPluginData('aiSettings', JSON.stringify({ service, apiKey: encryptedKey }));
        console.log(`Settings saved: Service - ${service}, API Key - ${encryptedKey}`);
    } else if (message.type === "loadKey") {
        // Retrieve API key and service
        const data = await penpot.root.getPluginData('aiSettings');
        if (data) {
            const { service, apiKey: encryptedKey } = JSON.parse(data);

            // Decrypt the API key (optional, can be skipped)
            const decryptedKey = decryptData(encryptedKey);

            console.log(`Settings loaded: Service - ${service}, API Key - ${decryptedKey}`);
            penpot.ui.sendMessage({ type: "settingsLoaded", payload: { service, apiKey: decryptedKey } });
        } else {
            console.warn("No settings found.");
            penpot.ui.sendMessage({ type: "settingsLoaded", payload: null });
        }
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

            console.log("Selected shape:", selectedShape);

            // Update the background color (fills property)
            /*
            selectedShape.fills = [
                {
                    fillColor: "#ff00b3",
                    fillOpacity: 1
                }
            ];
            */

            console.log(`Updated shape with ID ${selectedId} to pink background.`);
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
            console.log("The board is already empty!");
            return;
        }

        // Filter out non-removable shapes like the root frame
        const removableShapes = shapes.filter((shape) => shape.type !== "board");

        // Iterate through shapes and remove each
        removableShapes.forEach((shape) => {
            try {
                shape.remove();
                console.log(`Removed shape: ${shape.name || shape.id}`);
            } catch (error) {
                console.error(`Error removing shape (${shape.name || shape.id}):`, error);
            }
        });

        console.log(`Cleared ${removableShapes.length} shapes from the board.`);
    } catch (error) {
        console.error("Error clearing the board:", error);
    }
}

// Function to create a rectangle
function createRectangle(shape) {
    const rect = penpot.createRectangle();
    rect.name = shape.name || "Rectangle";
    rect.x = shape.position.x;
    rect.y = shape.position.y;
    rect.width = shape.dimensions.width;
    rect.height = shape.dimensions.height;
    rect.fills = shape.color || [];
    console.log("Rectangle added:", rect);
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
    console.log("Text added:", text);
}

// Function to create an ellipse
function createEllipse(shape) {
    const ellipse = penpot.createEllipse();
    ellipse.name = shape.name || "Ellipse";
    ellipse.x = shape.position.x;
    ellipse.y = shape.position.y;
    ellipse.width = shape.dimensions.width;
    ellipse.height = shape.dimensions.height;
    ellipse.fills = shape.color || [];
    console.log("Ellipse added:", ellipse);
}

// Helper function to validate the font family
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
                    console.log("Added rectangle:", newShape);
                    break;

                case "ellipse":
                    newShape = penpot.createEllipse();
                    newShape.name = shapeData.name || "Unnamed Ellipse";
                    newShape.resize(shapeData.dimensions.width, shapeData.dimensions.height);
                    newShape.x = shapeData.position.x;
                    newShape.y = shapeData.position.y;
                    if (shapeData.color) newShape.fills = shapeData.color;
                    console.log("Added ellipse:", newShape);
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

        console.log("Page construction completed.");
    } catch (error) {
        console.error("Error building page from JSON:", error);
    }
}
