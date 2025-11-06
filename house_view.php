<?php

// Front controller shim to expose the public house_view entry point when the
// application is served from the project root (e.g. via a subdomain).

require __DIR__ . '/public/house_view.php';
