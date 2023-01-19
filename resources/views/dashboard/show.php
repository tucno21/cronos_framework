@include('layouts.head')
@include('layouts.headdasboard')

<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
    <div class="container mt-3">
        <div class="p-4 p-md-5 mb-4 rounded text-bg-dark">
            <div class="col-md-6 px-0">
                <h1 class="display-5 fst-italic"><?= $blog->title ?></h1>
                <p class="lead my-3"><?= $blog->content ?></p>
                <!-- fecha -->
                <p class="text-primary">Autor: <?= $blog->name ?></p>

                <p class="lead mb-0"><a href="<?= route('dashboard.index') ?>" class="text-white fw-bold">Volver</a></p>
            </div>
        </div>
    </div>
</main>

@include('layouts.footerdasboard')
@include('layouts.footer')