@include('home.layouts.head')

<div class="bg-slate-100 mt-16 min-h-[calc(100vh-4rem)] flex justify-center items-center">

    <div class="border px-8 py-6 bg-white drop-shadow-lg rounded ">
        <?php if (session()->has('message')) : ?>
            <div class="text-center text-xs font-bold px-2 py-1 bg-blue-200">
                <p><?= session()->get('message') ?></p>
            </div>
        <?php endif; ?>
        <h2 class="text-2xl font-bold text-center">Iniciar Sesión</h2>
        <form id="formulario" class="mb-4 flex flex-col" method="post">
            <div class="mt-5">
                <label for="email" class="relative">
                    <input type="text" id="email" name="email" class="input-especial <?= ifError('email') ? 'border-red-500' : '' ?>" placeholder="Email" value="<?= old('email') ?>">
                    <span class="input-placeholder absolute top-[50%] left-4 text-sm translate-y-[-50%] transition duration-200">Email</span>
                </label>
                <?php if (ifError('email')) : ?>
                    <p class="text-red-500 text-xs px-1"><?= error('email') ?></p>
                <?php endif; ?>
            </div>
            <div class="mt-5">
                <label for="password" class="relative">
                    <input type="password" id="password" name="password" class="input-especial <?= ifError('password') ? 'border-red-500' : '' ?>" placeholder="Password" value="<?= old('password') ?>">
                    <span class="input-placeholder absolute top-[50%] left-4 text-sm translate-y-[-50%] transition duration-200">Password</span>
                </label>
                <?php if (ifError('password')) : ?>
                    <p class="text-red-500 text-xs px-1"><?= error('password') ?></p>
                <?php endif; ?>
            </div>
            <div class="mt-6">
                <button type="submit" class="bg-blue-700 py-2 text-white rounded hover:bg-blue-800 w-full" id="btnSubmit">Iniciar</button>
            </div>
        </form>
        <p class="text-sm">No tiene una cuenta? <a href="<?= route('register.index') ?>" class="text-blue-800 border-b border-blue-800">Registrace</a></p>
    </div>
</div>

@include('home.layouts.footer')