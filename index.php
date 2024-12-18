<?php
//header("Access-Control-Allow-Origin: *");
//header("Content-Type: text/html; charset=utf-8");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Penpot Rapid Prototyping with AI</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" rel="stylesheet">

    <link rel="stylesheet" href="index.css">
</head>
<body>

    <div class="tab-bar">
        <div class="tab active" data-tab="page-design"><i class="fas fa-file"></i></div>
        <div class="tab" data-tab="component-design"><i class="fas fa-table-list"></i></div>
        <div class="tab" data-tab="settings"><i class="fas fa-cog"></i></div>
    </div>

    <div id="page-design" class="tab-content active">
      <h2>Document Design</h2>
      <p class="mb-2">Build a page using an AI prompt.</p>

      <div class="ai-prompt-div">
        <label for="ai-prompt">AI Prompt</label>
        <textarea id="ai-prompt" placeholder="Describe your design to AI">Please design me a page that has 4 boxes, all the same width, aligned left. Include 2 circles, one above the boxes and one below. The circles should be smaller and centered with the boxes.</textarea>
      </div>
      <button id="send-to-ai" class="button right">Send to AI</button>
    </div>

    <div id="component-design" class="tab-content">
        <h2>Troubleshooting</h1>
        <p class="mb-2">Review the details of the page, make an adjustment and reload.</p>

        <label for="json-input">Add custom JSON here</label>
        <textarea id="json-input" placeholder="The prompt result here""><?php include('sample_penpot_design.txt'); ?></textarea><br>
        <button id="load-json" class="button">Load this JSON</button>

        <label class="mt-2" for="page-structure">Show existing page structure here</label>
        <div class="textarea-container">
            <textarea id="page-structure" placeholder="" DISABLED></textarea>
            <span id="copy-icon" class="copy-icon" title="Copy to clipboard">&#128203;</span>
        </div>
       <button id="get-page-json" class="button">Show Cleaned Page Structure JSON</button><br>
       <button id="get-page-json-verbose" class="button">Show Verbose Page Structure JSON</button>

    </div>

    <div id="settings" class="tab-content">
      <form>
        <h2>Plugin Settings</h2>
        <p class="mb-2">Choose your AI service and input the key provided from them when you signed up.<br>
        <small>For your protection, your key is stored locally not on any server.</small></p>

        <label for="api-service">AI Service</label>
        <select class="mb-2 form-control" name="api-service" id="api-service">
          <option>Select One</option>
          <option value="openai">OpenAI / ChatGPT</option>
          <option value="anthropic">Anthropic / Claude</option>
        </select>
        
        <label for="api-key">API Key</label>
        <input type="text" class="mb-2 form-control" name="api-key" id="api-key" placeholder="Enter API key" value="" />

        <input type="button" class="button right" id="save-settings" value="Save">
        <p id="save-message"></p>
      </form>

    </div>

    <div id="waiting-dialog" style="display: none; position: fixed; top: 50%; left: 50%; transform: translate(-50%, -50%); background: rgba(0, 0, 0, 0.8); color: #fff; padding: 20px; border-radius: 5px; text-align: center;">
        <p>Generating design from AI...</p>
    </div>

    <script>
    <?php include( 'penpot_comm.js' ); ?>
    </script> 

  </body>
</html>