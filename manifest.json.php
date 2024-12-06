<?php
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=utf-8");

echo json_encode([
  "name" => "Design with AI",
  "host" => "http://localhost",
  "description" => "Use AI to help create from scratch or alter existing designs.",
  "author" => "Jason Deegan",
  "code" => "plugin.js",
  "icon" => "logo.png",
  "permissions" => [
    "content:write",
    "comment:read",
    "comment:write",
    "library:read",
    "library:write",
    "allow:downloads",
    "user:read"
  ]
  ]);
?>
