<!DOCTYPE html>
<html lang="de">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{if isset($title)}{$title|escape} - {/if}Spookhouse Manager</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <style>
        body {
            background-color: #0f0f1a;
            color: #f8f9fa;
        }
        .navbar-brand {
            font-weight: bold;
            letter-spacing: 0.05em;
        }
        .house-canvas {
            position: relative;
            width: 420px;
            height: 320px;
            background-color: #1d1d2f;
            border: 1px solid #343a40;
            margin-bottom: 2rem;
        }
        .house-wall {
            position: absolute;
            background-color: #adb5bd;
        }
        .house-wall.vertical {
            width: 4px;
        }
        .house-wall.horizontal {
            height: 4px;
        }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-dark mb-4">
    <div class="container">
        <a class="navbar-brand" href="/index.php">Spookhouse Manager</a>
        <div class="d-flex">
            {if isset($currentUser)}
                <span class="navbar-text me-3">Eingeloggt als {$currentUser.username|escape}</span>
                <a class="btn btn-outline-light btn-sm" href="/logout.php">Logout</a>
            {/if}
        </div>
    </div>
</nav>
<div class="container mb-5">
