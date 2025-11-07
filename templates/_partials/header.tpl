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
        .house-grid-wrapper {
            position: relative;
            background-color: #1d1d2f;
            border: 1px solid #343a40;
            border-radius: 0.5rem;
            padding: 1.25rem;
        }
        .house-grid-canvas {
            width: 100%;
            height: auto;
            display: block;
            border-radius: 0.35rem;
            background-color: #060611;
        }
        .house-grid-overlay {
            position: absolute;
            top: 1.25rem;
            left: 1.25rem;
            right: 1.25rem;
            bottom: 1.25rem;
            pointer-events: none;
            border-radius: 0.35rem;
            overflow: hidden;
            z-index: 2;
        }
        .house-grid-svg {
            width: 100%;
            height: 100%;
            display: block;
        }
        .grid-walkable {
            fill: rgba(173, 181, 189, 0.12);
        }
        .grid-line {
            stroke: rgba(173, 181, 189, 0.35);
            stroke-width: 1;
        }
        .grid-line.structural {
            stroke: rgba(233, 236, 239, 0.8);
            stroke-width: 2;
        }
        .room-boundary {
            fill: rgba(0, 168, 255, 0.15);
            stroke: rgba(0, 168, 255, 0.85);
            stroke-width: 3;
        }
        .room-boundary:hover {
            fill: rgba(0, 168, 255, 0.25);
        }
        .room-coordinate-label {
            font-size: 0.65rem;
            fill: #adb5bd;
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
