<?php
// CSRF stub — returns a random token so the frontend does not break.
// The backend does NOT enforce CSRF validation (no sessions used).
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Credentials: true');

echo json_encode(['csrf_token' => bin2hex(random_bytes(16))]);
