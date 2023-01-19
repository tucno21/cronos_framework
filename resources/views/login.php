@include('layouts.head')
@include('layouts.headhome')


<div class="container">
    <?php if (session()->has('message')) : ?>
        <div class="alert alert-success" role="alert">
            <p><?= session()->get('message') ?></p>
        </div>
    <?php endif; ?>
    <div class="mt-4 d-flex justify-content-center align-items-center">
        <div class="col-md-4 px-5 py-3 shadow-lg border rounded-3">
            <h2 class="text-center mb-3">Iniciar Sesión</h2>
            <form method="post">
                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" name="email" class="form-control <?= ifError('email') ? 'is-invalid' : 'border-primary' ?>" id="email" value="<?= old('email') ?>">
                    <?php if (ifError('email')) : ?>
                        <div class="invalid-feedback">
                            <?= error('email') ?>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" name="password" class="form-control <?= ifError('password') ? 'is-invalid' : 'border-primary' ?>" id="password" value="<?= old('password') ?>">
                    <?php if (ifError('password')) : ?>
                        <div class="invalid-feedback">
                            <?= error('password') ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="d-grid">
                    <button class="btn btn-primary" type="submit">Iniciar</button>
                </div>
            </form>
            <div class="mt-3">
                <p class="mb-0  text-center">No tiene una cuenta? <a href="<?= route('register.index') ?>" class="text-primary fw-bold">Registrace</a></p>
            </div>
        </div>
    </div>
</div>
</main>


@include('layouts.footer')