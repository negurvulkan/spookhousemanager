{assign var='title' value='Login'}
{include file='_partials/header.tpl' title=$title}
<div class="row justify-content-center">
    <div class="col-md-4">
        <div class="card bg-dark border-secondary">
            <div class="card-body">
                <h1 class="h4 mb-3 text-center">Login</h1>
                {if $error}
                    <div class="alert alert-danger" role="alert">{$error|escape}</div>
                {/if}
                <form method="post" action="/login.php">
                    <div class="mb-3">
                        <label for="username" class="form-label">Benutzername</label>
                        <input type="text" class="form-control" id="username" name="username" required>
                    </div>
                    <div class="mb-3">
                        <label for="password" class="form-label">Passwort</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">Einloggen</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
{include file='_partials/footer.tpl'}
