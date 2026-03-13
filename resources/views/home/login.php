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
            @csrf

            <x-input
                name="email"
                type="email"
                label="Email"
                placeholder="Ingresa tu correo electrónico"
                required />

            <x-input
                name="password"
                type="password"
                label="Contraseña"
                placeholder="Ingresa tu contraseña"
                required />

            <x-button
                type="submit"
                variant="primary"
                size="lg"
                class="w-full">
                Iniciar Sesión
            </x-button>
        </form>

        <p class="mt-8 text-center text-gray-600">
            ¿No tienes una cuenta?
            <a href="<?= route('register.index') ?>" class="auth-link">Regístrate aquí</a>
        </p>
    </div>
</div>

@include('home.layouts.footer')