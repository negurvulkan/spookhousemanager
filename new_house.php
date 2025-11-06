<?php

// Front controller shim to expose the public new_house entry point when the
// application is served from the project root (e.g. via a subdomain).

require __DIR__ . '/public/new_house.php';
