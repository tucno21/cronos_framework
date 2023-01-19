@include('layouts.head')
@include('layouts.headdasboard')

<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Crear Blog</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <a href="<?= route('dashboard.index') ?>" class="btn btn-sm btn-outline-secondary">Volver</a>
        </div>
    </div>

    <div class="px-5">
        <form method="POST">
            @include('dashboard.imputs')
            <button type="submit" class="btn btn-primary">Actualizar blog</button>
        </form>
    </div>
</main>

@include('layouts.footerdasboard')
@include('layouts.footer')