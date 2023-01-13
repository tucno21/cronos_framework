<?php if (session()->has('message')) : ?>
    <div class="alert alert-primary" role="alert">
        <h1><?= session()->get('message') ?></h1>
    </div>
<?php endif; ?>