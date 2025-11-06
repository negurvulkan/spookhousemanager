{include file='_partials/header.tpl' title=$title currentUser=$currentUser}
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h1 class="h3 mb-0">{$house.name|escape}</h1>
        <p class="text-muted mb-0">Erstellt am {$house.created_at|escape}</p>
    </div>
    <a href="/index.php" class="btn btn-outline-light">Zurück zur Übersicht</a>
</div>
<div class="row">
    <div class="col-md-3 mb-3">
        <div class="list-group">
            {foreach from=$floors item=floor}
                <a class="list-group-item list-group-item-action {if $floor.id == $selectedFloorId}active{/if}" href="/house_view.php?id={$house.id|escape}&floor={$floor.id|escape}">
                    Ebene {$floor.level|escape}
                </a>
            {/foreach}
        </div>
    </div>
    <div class="col-md-9">
        {if $selectedFloorId}
            {if $floorLayout}
                <div class="house-grid-wrapper" role="img" aria-label="Rasterdarstellung Ebene {$selectedFloorId}">
                    <svg class="house-grid-svg" viewBox="0 0 {$floorLayout.svgWidth} {$floorLayout.svgHeight}" xmlns="http://www.w3.org/2000/svg">
                        {foreach from=$floorLayout.walkableCells item=cell}
                            <rect class="grid-walkable" x="{$cell.x}" y="{$cell.y}" width="{$cell.width}" height="{$cell.height}"></rect>
                        {/foreach}
                        {foreach from=$floorLayout.gridLines.horizontal item=line}
                            <line class="grid-line {if $line.structural}structural{/if}" x1="0" y1="{$line.position}" x2="{$floorLayout.svgWidth}" y2="{$line.position}"></line>
                        {/foreach}
                        {foreach from=$floorLayout.gridLines.vertical item=line}
                            <line class="grid-line {if $line.structural}structural{/if}" x1="{$line.position}" y1="0" x2="{$line.position}" y2="{$floorLayout.svgHeight}"></line>
                        {/foreach}
                        {foreach from=$floorLayout.rooms item=room}
                            {if $room.polygonPoints|strlen > 0}
                                <polygon class="room-boundary" points="{$room.polygonPoints}" data-room-id="{$room.id}"></polygon>
                            {/if}
                        {/foreach}
                    </svg>
                </div>
                <div class="mt-3">
                    <div class="mb-3 small text-secondary">
                        <strong>Spaltenlinien:</strong>
                        {foreach from=$floorLayout.columnLabels item=label name=colLabels}
                            <code>{$label|escape}</code>{if not $smarty.foreach.colLabels.last},{/if}
                        {/foreach}
                        <br>
                        <strong>Zeilenlinien:</strong>
                        {foreach from=$floorLayout.rowLabels item=label name=rowLabels}
                            <code>{$label|escape}</code>{if not $smarty.foreach.rowLabels.last},{/if}
                        {/foreach}
                    </div>
                    <div class="row g-3">
                        {foreach from=$floorLayout.rooms item=room}
                            <div class="col-lg-6">
                                <div class="card bg-dark border-secondary h-100">
                                    <div class="card-body">
                                        <h2 class="h5 card-title mb-2">{$room.name|escape}</h2>
                                        <p class="mb-2 small text-secondary">Rastergröße: {$floorLayout.gridWidth} × {$floorLayout.gridHeight} Felder</p>
                                        <p class="mb-0 small">
                                            <span class="text-uppercase text-secondary">Pfad:</span>
                                            {foreach from=$room.pathLabels item=label name=labels}
                                                <code>{$label|escape}</code>{if not $smarty.foreach.labels.last},{/if}
                                            {/foreach}
                                        </p>
                                    </div>
                                </div>
                            </div>
                        {/foreach}
                    </div>
                </div>
            {else}
                <div class="alert alert-info">Für diese Ebene sind noch keine Raumgrenzen hinterlegt.</div>
            {/if}
        {else}
            <div class="alert alert-warning">Keine Ebene ausgewählt.</div>
        {/if}
    </div>
</div>
{include file='_partials/footer.tpl'}
