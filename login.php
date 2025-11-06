<?php

// Front controller shim to expose the public login entry point when the
// application is served from the project root (e.g. via a subdomain).
// This keeps existing deep links working regardless of the configured
// document root.

require __DIR__ . '/public/login.php';
