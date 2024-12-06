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
        <h2>Document Design</h4>
        <textarea id="json-input" placeholder="Paste your JSON here..."><?php include('sample_penpot_design.txt'); ?></textarea><br>
        <button id="load-json">Load JSON</button>
        <button id="clear-board">Clear Board</button>
    </div>

    <div id="component-design" class="tab-content">
        <h2>Component Design</h1>
        <p>Start using your AI-powered plugin here!</p>
        <button class="button">Get Started</button>
        <button id="get-page-structure">Get Page JSON</button>
        <pre id="output"></pre>
    </div>

    <div id="settings" class="tab-content">
      <form>
        <h2>Plugin Settings</h2>
        <p class="mb-2">Choose your AI service and input the key provided from them on signup.</p>

        <label for="api-key">Service</label>
        <select class="mb-2 form-control" name="api-service" id="api-service">
          <option>Select One</option>
          <option value="openai" selected>OpenAI / ChatGPT</option>
          <option value="anthropic">Anthropic / Claude</option>
        </select>
        
        <label for="api-key">API Key</label>
        <input type="text" class="mb-2 form-control" name="api-key" id="api-key" placeholder="Enter API key" value="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" />
        <br><br>
        <button class="button">Reset</button>
        <button class="button">Save</button>
      </form>

    </div>

  <!--
    <div class="content">
      <h1>Welcome to the Plugin</h1>
      <p>Start using your AI-powered plugin here!</p>
      <button class="button">Get Started</button>

      <h4>Penpot JSON Loader</h4>
      <textarea id="json-input" placeholder="Paste your JSON here..."><?php // include( 'sample_penpot_design.txt' ); ?></textarea><br>
      <button id="load-json">Load JSON</button><br><br>

      <button id="get-page-structure">Get Page JSON</button>
      <button id="clear-board">Clear Board</button>

      <pre id="output"></pre>

      </div>
      <div id="settings" class="settings-container">
          <h4>Settings</h4>
          <label for="api-key">API Key:</label><br>
          <input type="text" id="api-key" placeholder="Enter API key" /><br><br>
          <button class="button" onclick="saveSettings()">Save</button>
      </div>

  <script src="page_functionality.js"></script>
  <script src="penpot_comm.jsa"></script>
-->


<script>

  <?php 
    include( 'page_functionality.js' );
    include( 'penpot_comm.js' );
  ?>
</script> 

</body>
</html>