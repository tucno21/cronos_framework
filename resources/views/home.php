<?php if (session()->has('message')) : ?>
    <div class="alert alert-primary" role="alert">
        <h1><?= session()->get('message') ?></h1>
    </div>
<?php endif; ?>

<?php if (session()->hasUser()) : ?>
    <div class="card mt-5" style="width: 18rem;">
        <div class="card-body">
            <h5 class="card-title">Hola : <?= session()->user()->name ?></h5>
            <p class="card-text">Email : <?= session()->user()->email ?></p>
        </div>
    </div>
<?php endif; ?>