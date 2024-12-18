<?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
header("Content-Type: application/javascript; charset=utf-8");
?>

penpot.ui.open("Penpot Rapid Prototyping with AI", "http://localhost:80/index.php", { width: 300, height: 900 });

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

            case "load-json":
                clearBoard();
                const json = message.payload;
                console.log("Received JSON:", json);
                const json_normalized = normalizeAIResponse(json);
                console.log( json_normalized ); 
                buildPageFromJSON(json_normalized);
                break;

            case "get-page-json":
                try {
                    const shapes = penpot.currentPage.findShapes();

                    // Extract minimal shape data
                    const allShapeData = shapes.map((shape) => {
                        let data = {
                            type: shape.type,
                            name: shape.name || "Unnamed",
                            position: { x: shape.x || 0, y: shape.y || 0 }
                        };

                        // Add dimensions for all shapes (rectangles, ellipses, text)
                        if (shape.bounds) {
                            data.dimensions = {
                                width: shape.bounds.width || 0,
                                height: shape.bounds.height || 0
                            };
                        }

                        // Add text properties for text shapes
                        if (shape.type === "text") {
                            data.characters = shape.characters || shape.content || "";
                            data.fontFamily = shape.fontFamily || "sourcesanspro";
                            data.fontSize = parseFloat(shape.fontSize) || 16; // Ensure numeric font size
                            data.color = shape.fills || [{ fillColor: "#000000", fillOpacity: 1 }];
                            data.alignmentHorizontal = shape.align || "left";
                            data.alignmentVertical = shape.verticalAlign || "top";
                        }

                        // Add fills for visual shapes
                        if (shape.fills && shape.fills.length > 0) {
                            data.color = shape.fills;
                        }

                        // Add strokes if available
                        if (shape.strokes && shape.strokes.length > 0) {
                            data.strokes = shape.strokes;
                        }

                        // Add gradients if available
                        if (shape.gradient) {
                            data.gradient = shape.gradient;
                        }

                        return data;
                    });

                    // Sort shapes to ensure rectangles come first, then text, based on position.y
                    const sortedShapes = allShapeData.sort((a, b) => {
                        // Prioritize rectangles over text, then sort by y-position
                        if (a.type === "rectangle" && b.type !== "rectangle") return -1;
                        if (b.type === "rectangle" && a.type !== "rectangle") return 1;
                        return a.position.y - b.position.y;
                    });

                    // Wrap the shapes in a proper training format
                    const trainingFormat = {
                        pages: [
                            {
                                id: penpot.currentPage.id || "page1",
                                name: penpot.currentPage.name || "Page 1",
                                shapes: sortedShapes
                            }
                        ]
                    };

                    // Send the cleaned and formatted JSON
                    penpot.ui.sendMessage({ type: "showPageStructure", payload: trainingFormat });

                } catch (error) {
                    console.error("Error fetching minimal page JSON:", error);
                    penpot.ui.sendMessage("Error fetching minimal page JSON", "*");
                }
                break;

            case "get-page-json-verbose":
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
                            }, 
                        {});
                        // Specific handling for text shapes
                        if (shape.type === "text") {
                            data.textContent = shape.content || "No content available";
                        }
                        return data;
                    });
                    penpot.ui.sendMessage({ type: "showPageStructure", payload: { allShapeData } });

                } catch (error) {
                    console.error("Error fetching page JSON:", error);
                    penpot.ui.sendMessage("Error fetching page JSON", "*");
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

function normalizeAIResponse(aiResponse) {
    let normalized = { pages: [] };

    if (aiResponse.pages) {
        normalized.pages = aiResponse.pages;

    } else if (aiResponse.file && aiResponse.file.pages) {
        normalized.pages = aiResponse.file.pages;

    } else if (aiResponse.artboards) {
        normalized.pages = aiResponse.artboards.map((artboard) => ({
            id: artboard.id || "unknown-artboard",
            name: artboard.name || "Unnamed Artboard",
            shapes: artboard.elements.map(normalizeShape),
        }));

    } else if (aiResponse.elements) {
        normalized.pages = [{
            id: "generated-page",
            name: "Generated Page",
            shapes: aiResponse.elements.map(normalizeShape),
        }];

    } else if (aiResponse.id && aiResponse.name && aiResponse.elements) {
        normalized.pages = [{
            id: aiResponse.id,
            name: aiResponse.name,
            shapes: aiResponse.elements.map(normalizeShape),
        }];

    } else {
        console.error("Unsupported JSON structure. Unable to normalize:", aiResponse);
    }

    return normalized;
}

function normalizeShape(element) {
    let shapeType = element.type;

    if (shapeType === "box") shapeType = "rectangle";
    if (shapeType === "circle") shapeType = "ellipse";

    const shape = {
        id: element.id || "unknown-id",
        type: shapeType,
        position: { x: element.x || 0, y: element.y || 0 },
    };

    if (shapeType === "rectangle" || shapeType === "ellipse") {
        shape.dimensions = { width: element.width || 100, height: element.height || 100 };
    }

    if (shapeType === "ellipse" && element.radius) {
        shape.dimensions = { width: element.radius * 2, height: element.radius * 2 };
    }

    if (element.style && element.style.fill) {
        shape.fills = [{ fillColor: element.style.fill, fillOpacity: 1 }];
    }

    return shape;
}

function buildPageFromJSON(json) {
    try {
        // Check and normalize the JSON structure
        let pages = [];
        if (json.pages) {
            pages = json.pages; // Standard structure with pages
        } else if (json.file && json.file.pages) {
            pages = json.file.pages; // File-based structure
        } else if (json.artboard) {
            pages = [{ id: "artboard", name: "Artboard", shapes: json.artboard }]; // Artboard-based structure
        } else {
            console.error("Invalid JSON structure: No 'pages', 'file.pages', or 'artboard' key found.");
            return;
        }

        // Iterate over pages
        pages.forEach((page) => {
            console.log(`Building page: ${page.name || page.id}`);

            if (page.shapes && Array.isArray(page.shapes)) {
                page.shapes.forEach((shapeData) => {
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
                                createText(shapeData);
                            } catch (error) {
                                console.warn("Failed to create text shape:", shapeData, error);
                            }
                            break;

                        case "board":
                            console.log("Ignoring board shape during rendering.");
                            break;

                        case "artboard":
                            console.log("Ignoring artboard during rendering.");
                            break;

                        default:
                            console.warn(`Unknown shape type: ${shapeData.type}`);
                    }
                });
            } else {
                console.warn("No shapes found for this page.");
            }
        });
    } catch (error) {
        console.error("Error building page from JSON:", error);
    }
}