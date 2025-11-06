{include file='_partials/header.tpl' title=$title currentUser=$currentUser}
<div class="container my-4">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card bg-dark border-secondary shadow-sm">
                <div class="card-header border-secondary d-flex justify-content-between align-items-center">
                    <h1 class="h4 mb-0">Wand-Canvas – Ebene {$floor.level|escape}</h1>
                    <a class="btn btn-outline-light btn-sm" href="/house_view.php?id={$floor.house_id|escape}">Zurück zur Hausansicht</a>
                </div>
                <div class="card-body">
                    <p class="text-muted">Die Wanddaten werden als Sprites dargestellt. Seite A entspricht der oberen bzw. linken Seite, Seite B der unteren bzw. rechten Seite.</p>
                    <div class="text-center">
                        <canvas
                            id="houseCanvas"
                            class="border border-secondary rounded bg-black"
                            width="640"
                            height="480"
                            data-floor-id="{$floorId|escape}"
                            data-api-endpoint="/api/get_floor_walls.php"
                            data-cell-size="32"
                        ></canvas>
                    </div>
                </div>
                <div class="card-footer border-secondary">
                    <small class="text-secondary">Sprites werden nach Bedarf geladen. Seite A liegt bei horizontalen Wänden oberhalb der Linie, Seite B darunter. Bei vertikalen Wänden liegt Seite A links und Seite B rechts.</small>
                </div>
            </div>
        </div>
    </div>
</div>
<script src="/js/floor_walls_renderer.js" defer></script>
{include file='_partials/footer.tpl'}
