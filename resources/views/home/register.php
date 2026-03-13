@include('home.layouts.head')

<div class="bg-gradient-to-br from-slate-50 to-blue-50 min-h-screen pt-16 flex items-center justify-center px-4">
    <div class="auth-card">
        <h2 class="auth-title">Crear Cuenta</h2>

        <form id="formulario" method="post">
            @csrf

            <x-input
                name="name"
                type="text"
                label="Nombre"
                placeholder="Ingresa tu nombre"
                required />

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
                placeholder="Crea una contraseña"
                required />

            <x-input
                name="confirm_password"
                type="password"
                label="Confirmar Contraseña"
                placeholder="Confirma tu contraseña"
                required />

            <x-button
                type="submit"
                variant="primary"
                size="lg"
                class="w-full">
                Crear Cuenta
            </x-button>
        </form>

        <p class="mt-8 text-center text-gray-600">
            ¿Ya tienes una cuenta?
            <a href="<?= route('login.index') ?>" class="auth-link">Inicia sesión aquí</a>
        </p>
    </div>
</div>

@include('home.layouts.footer')