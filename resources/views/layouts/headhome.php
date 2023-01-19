<header class="p-3 text-bg-dark">
    <div class="container">
        <div class="d-flex flex-wrap align-items-center justify-content-center justify-content-lg-start">
            <a href="/" class="d-flex align-items-center mb-2 mb-lg-0 text-white text-decoration-none">
                Cronos
            </a>

            <ul class="nav col-12 col-lg-auto me-lg-auto mb-2 justify-content-center mb-md-0">
                <li><a href="<?= route('home.index') ?>" class="nav-link px-2 text-secondary">Home</a></li>
            </ul>

            <div class="text-end">
                <a href="<?= route('login.index') ?>" class="btn btn-outline-light me-2">Iniciar Session</a>
                <a href="<?= route('register.index') ?>" class="btn btn-warning">Registrar</a>
            </div>
        </div>
    </div>
</header>
<main>