{include file='_partials/header.tpl' title=$title currentUser=$currentUser}
<div class="d-flex justify-content-between align-items-center mb-4">
    <h1 class="h3 mb-0">Meine Spukhäuser</h1>
    <form class="d-flex" method="post" action="/new_house.php">
        <input type="text" class="form-control me-2" name="name" placeholder="Hausname" aria-label="Hausname">
        <button type="submit" class="btn btn-success">Neues Spiel</button>
    </form>
</div>
{if $houses|@count > 0}
    <div class="row g-3">
        {foreach from=$houses item=house}
            <div class="col-md-4">
                <div class="card bg-dark border-secondary h-100">
                    <div class="card-body d-flex flex-column">
                        <h2 class="h5 card-title">{$house.name|escape}</h2>
                        <p class="card-text text-muted mb-4">Erstellt am {$house.created_at|escape}</p>
                        <div class="mt-auto">
                            <a href="/house_view.php?id={$house.id|escape}" class="btn btn-outline-light">Anzeigen</a>
                        </div>
                    </div>
                </div>
            </div>
        {/foreach}
    </div>
{else}
    <div class="alert alert-info">Noch keine Spukhäuser vorhanden. Starte ein neues Spiel!</div>
{/if}
{include file='_partials/footer.tpl'}
