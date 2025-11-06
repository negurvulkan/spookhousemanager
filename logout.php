<?php

// Front controller shim to expose the public logout entry point when the
// application is served from the project root (e.g. via a subdomain).

require __DIR__ . '/public/logout.php';
