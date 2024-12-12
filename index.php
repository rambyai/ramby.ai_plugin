<?php
//header("Access-Control-Allow-Origin: *");
//header("Content-Type: text/html; charset=utf-8");
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Design with AI</title>
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
      <button id="send-to-ai-directly" class="button right">Send to AI DIRECTLY</button>

    </div>

    <div id="component-design" class="tab-content">
        <h2>Troubleshooting</h1>
        <p class="mb-2">Review the details of the page, make an adjustment and reload.</p>

        <div class="page-json-div">
          <label id="label-for-json-input" for="json-input">Show Resulting JSON from AI</label>
          <textarea id="json-input" placeholder="The prompt result here" DISABLED style="display:none;"><?php include('sample_penpot_design.txt'); ?></textarea><br>
        </div>

        <button id="load-json" class="button">Load JSON</button>

        <button id="get-page-json">Get Page JSON</button>
        <pre id="output"></pre>

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

<script>

  <?php 
    include( 'page_functionality.js' );
    include( 'penpot_comm.js' );
  ?>
</script> 

</body>
</html>