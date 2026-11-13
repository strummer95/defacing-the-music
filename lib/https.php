<?php
// Send visitors who arrive over plain http to the https version. The site sits behind Cloudflare (SSL mode
// "Flexible"), so PHP always sees http from the origin; Cloudflare tells us the visitor's real scheme in
// X-Forwarded-Proto / CF-Visitor. Only redirects when Cloudflare explicitly says "http", so local testing
// (no such headers) and direct-to-origin requests are left alone. POSTs are never redirected.
(function () {
  if (PHP_SAPI === 'cli') return;
  $proto = strtolower((string)($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''));
  $visitor = (string)($_SERVER['HTTP_CF_VISITOR'] ?? '');
  $isHttp = $proto === 'http' || strpos($visitor, '"http"') !== false;
  if (!$isHttp) return;
  $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
  if ($method !== 'GET' && $method !== 'HEAD') return;
  $host = strtolower((string)($_SERVER['HTTP_HOST'] ?? ''));
  if (!preg_match('/^(www\.)?defacingthemusic\.com$/', $host)) return;
  header('Location: https://' . $host . ($_SERVER['REQUEST_URI'] ?? '/'), true, 301);
  exit;
})();
