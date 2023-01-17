@include('layouts.head')
<!-- boton para volver a dashboard -->
<div class="container mt-4">
    <a href="<?= route('dashboard.index') ?>" class="btn btn-primary">Volver</a>

    <div class="card mt-5" style="width: 18rem;">
        <div class="card-body">
            <h5 class="card-title">Hola : <?= $user->name ?></h5>
            <p class="card-text">Email : <?= $user->email ?></p>
        </div>
    </div>
</div>
@include('layouts.footer')