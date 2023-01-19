@include('layouts.head')
@include('layouts.headhome')

<div class="container">
    <div class="mt-4 d-flex justify-content-center align-items-center">
        <div class="col-md-4 px-5 py-3 shadow-lg border rounded-3">
            <h2 class="text-center mb-3">Registro</h2>
            <form method="post">
                <div class="mb-3">
                    <label for="name" class="form-label">Nombre</label>
                    <input type="text" name="name" class="form-control border border-primary <?= ifError('name') ? 'is-invalid' : '' ?>" id="name" value="<?= old('name') ?>">
                    <?php if (ifError('name')) : ?>
                        <div class="invalid-feedback">
                            <?= error('name') ?>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="mb-3">
                    <label for="email" class="form-label">Email</label>
                    <input type="email" name="email" class="form-control border border-primary <?= ifError('email') ? 'is-invalid' : '' ?>" id="email" value="<?= old('email') ?>">
                    <?php if (ifError('email')) : ?>
                        <div class="invalid-feedback">
                            <?= error('email') ?>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="mb-3">
                    <label for="password" class="form-label">Password</label>
                    <input type="password" name="password" class="form-control border border-primary <?= ifError('password') ? 'is-invalid' : '' ?>" id="password">
                    <?php if (ifError('password')) : ?>
                        <div class="invalid-feedback">
                            <?= error('password') ?>
                        </div>
                    <?php endif; ?>
                </div>
                <div class="mb-3">
                    <label for="repit" class="form-label">Repetir Password</label>
                    <input type="password" name="confirm_password" class="form-control border border-primary <?= ifError('confirm_password') ? 'is-invalid' : '' ?>" id="repit">
                    <?php if (ifError('confirm_password')) : ?>
                        <div class="invalid-feedback">
                            <?= error('confirm_password') ?>
                        </div>
                    <?php endif; ?>
                </div>

                <div class="d-grid">
                    <button class="btn btn-primary" type="submit">Registrarce</button>
                </div>
            </form>
            <div class="mt-3">
                <p class="mb-0  text-center">ya tiene una cuenta? <a href="<?= route('login.index') ?>" class="text-primary fw-bold">Iniciar Sesión</a></p>
            </div>
        </div>
    </div>
</div>
</main>

@include('layouts.footer')