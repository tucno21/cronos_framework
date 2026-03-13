@include('home.layouts.head')

<div class="bg-gradient-to-br from-slate-50 to-blue-50 min-h-screen pt-16 flex items-center justify-center px-4">
    <div class="auth-card">
        <?php if (session()->has('message')) : ?>
            <div class="alert-success mb-6">
                <?= session()->get('message') ?>
            </div>
        <?php endif; ?>

        <h2 class="auth-title">Iniciar Sesión</h2>

        <form id="formulario" method="post">
            <div class="mb-6">
                <label for="email" class="relative block">
                    <input
                        type="email"
                        id="email"
                        name="email"
                        class="input-especial <?= ifError('email') ? 'error' : '' ?>"
                        placeholder=" "
                        value="<?= old('email') ?>"
                        required>
                    <span class="input-placeholder">Email</span>
                </label>
                <?php if (ifError('email')) : ?>
                    <p class="mt-2 text-red-500 text-sm font-medium flex items-center gap-2">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 16 16">
                            <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z" />
                            <path d="M4.646 4.646a.5.5 0 0 1 .708 0L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 0 1 0-.708z" />
                        </svg>
                        <?= error('email') ?>
                    </p>
                <?php endif; ?>
            </div>

            <div class="mb-8">
                <label for="password" class="relative block">
                    <input
                        type="password"
                        id="password"
                        name="password"
                        class="input-especial <?= ifError('password') ? 'error' : '' ?>"
                        placeholder=" "
                        required>
                    <span class="input-placeholder">Contraseña</span>
                </label>
                <?php if (ifError('password')) : ?>
                    <p class="mt-2 text-red-500 text-sm font-medium flex items-center gap-2">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 16 16">
                            <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z" />
                            <path d="M4.646 4.646a.5.5 0 0 1 .708 0L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 0 1 0-.708z" />
                        </svg>
                        <?= error('password') ?>
                    </p>
                <?php endif; ?>
            </div>

            <button type="submit" class="btn-auth" id="btnSubmit">
                <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 16 16">
                    <path d="M8 1a2 2 0 0 1 2 2v4H6a2 2 0 0 0-2 2v6a2 2 0 0 0 2 2h6a2 2 0 0 0 2-2V9a2 2 0 0 0-2-2h-1V3a2 2 0 0 1-2-2zm0 1a1 1 0 0 1 1 1v4h6V6h-1a1 1 0 0 0-1-1z" />
                </svg>
                Iniciar Sesión
            </button>
        </form>

        <p class="mt-8 text-center text-gray-600">
            ¿No tienes una cuenta?
            <a href="<?= route('register.index') ?>" class="auth-link">Regístrate aquí</a>
        </p>
    </div>
</div>

@include('home.layouts.footer')