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
            <div class="house-canvas" role="img" aria-label="Grundriss Ebene {$selectedFloorId}">
                {foreach from=$walls item=wall}
                    {assign var='styleString' value=""}
                    {if $wall.orientation == 'horizontal'}
                        {assign var='styleString' value=$styleString|cat:"left:"|cat:$wall.left|cat:"px;top:"|cat:$wall.top|cat:"px;width:"|cat:$wall.length|cat:"px;height:4px;"}
                    {else}
                        {assign var='styleString' value=$styleString|cat:"left:"|cat:$wall.left|cat:"px;top:"|cat:$wall.top|cat:"px;height:"|cat:$wall.length|cat:"px;width:4px;"}
                    {/if}
                    <div class="house-wall {$wall.orientation}" style="{$styleString}">
                        <span class="visually-hidden">Wand {$wall.id} des Raums {$wall.room_name|escape}</span>
                    </div>
                {/foreach}
            </div>
        {else}
            <div class="alert alert-warning">Keine Ebene ausgewählt.</div>
        {/if}
    </div>
</div>
{include file='_partials/footer.tpl'}
