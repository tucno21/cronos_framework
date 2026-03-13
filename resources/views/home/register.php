@include('home.layouts.head')

<div class="bg-gradient-to-br from-slate-50 to-blue-50 min-h-screen pt-16 flex items-center justify-center px-4">
    <div class="auth-card">
        <h2 class="auth-title">Crear Cuenta</h2>

        <form id="formulario" method="post">
            <div class="mb-5">
                <label for="name" class="relative block">
                    <input
                        type="text"
                        id="name"
                        name="name"
                        class="input-especial <?= ifError('name') ? 'error' : '' ?>"
                        placeholder=" "
                        value="<?= old('name') ?>"
                        required>
                    <span class="input-placeholder">Nombre</span>
                </label>
                <?php if (ifError('name')) : ?>
                    <p class="mt-2 text-red-500 text-sm font-medium flex items-center gap-2">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 16 16">
                            <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z" />
                            <path d="M4.646 4.646a.5.5 0 0 1 .708 0L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 0 1 0-.708z" />
                        </svg>
                        <?= error('name') ?>
                    </p>
                <?php endif; ?>
            </div>

            <div class="mb-5">
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

            <div class="mb-5">
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

            <div class="mb-8">
                <label for="confirm_password" class="relative block">
                    <input
                        type="password"
                        id="confirm_password"
                        name="confirm_password"
                        class="input-especial <?= ifError('confirm_password') ? 'error' : '' ?>"
                        placeholder=" "
                        required>
                    <span class="input-placeholder">Confirmar Contraseña</span>
                </label>
                <?php if (ifError('confirm_password')) : ?>
                    <p class="mt-2 text-red-500 text-sm font-medium flex items-center gap-2">
                        <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 16 16">
                            <path d="M8 15A7 7 0 1 1 8 1a7 7 0 0 1 0 14zm0 1A8 8 0 1 0 8 0a8 8 0 0 0 0 16z" />
                            <path d="M4.646 4.646a.5.5 0 0 1 .708 0L8 7.293l2.646-2.647a.5.5 0 0 1 .708.708L8.707 8l2.647 2.646a.5.5 0 0 1-.708.708L8 8.707l-2.646 2.647a.5.5 0 0 1-.708-.708L7.293 8 4.646 5.354a.5.5 0 0 1 0-.708z" />
                        </svg>
                        <?= error('confirm_password') ?>
                    </p>
                <?php endif; ?>
            </div>

            <button type="submit" class="btn-auth" id="btnSubmit">
                <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 16 16">
                    <path d="M16 8A8 8 0 1 1-8 8 0 0 1 1-8 8 0 0 0 1 8 0 0 0 1 8zm-1 0a1 1 0 0 1-1-1v-2a1 1 0 1 1-1V4a1 1 0 1 1-1V2a1 1 0 0 1-1-1h-1a1 1 0 0 1-1 1v1H8a1 1 0 0 1-1-1V2a1 1 0 0 1-1-1H5a1 1 0 0 1-1 1v1a1 1 0 0 1-1 1h1a1 1 0 0 1-1 1v2a1 1 0 0 1-1 1h4v2a1 1 0 0 1-1 1z" />
                </svg>
                Crear Cuenta
            </button>
        </form>

        <p class="mt-8 text-center text-gray-600">
            ¿Ya tienes una cuenta?
            <a href="<?= route('login.index') ?>" class="auth-link">Inicia sesión aquí</a>
        </p>
    </div>
</div>

@include('home.layouts.footer')