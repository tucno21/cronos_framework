@include('dashboard.layouts.head')

<!-- CONTENT -->
<div class="main-content">
    <div class="max-w-4xl mx-auto">

        <!-- Back link -->
        <a href="<?= route('dashboard.index') ?>"
            class="inline-flex items-center gap-1.5 text-sm text-gray-400 hover:text-blue-600 transition-colors mb-5 no-underline">
            <i class="bi bi-arrow-left"></i>
            Volver al listado
        </a>

        <!-- Post header -->
        <div class="card mb-5">
            <div class="px-8 py-6 border-b border-gray-100">
                <div class="flex items-center gap-2 text-sm text-gray-400 mb-3">
                    <div class="w-6 h-6 rounded-full bg-blue-600 flex items-center justify-center text-[0.6875rem] text-white">
                        <i class="bi bi-person-fill"></i>
                    </div>
                    <span>Por <strong class="text-gray-600"><?= htmlspecialchars($blog->name) ?></strong></span>
                    <span class="text-gray-200">·</span>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[0.6875rem] font-semibold bg-blue-100 text-blue-700">Publicado</span>
                </div>
                <h1 class="text-2xl font-bold text-gray-800 tracking-tight leading-snug">
                    <?= htmlspecialchars($blog->title) ?>
                </h1>
            </div>

            <!-- Post content -->
            <div class="px-8 py-6">
                <p class="text-base text-gray-600 leading-relaxed">
                    <?= nl2br(htmlspecialchars($blog->content)) ?>
                </p>
            </div>

            <!-- Footer -->
            <div class="px-8 py-4 border-t border-gray-100 flex gap-3">
                <a href="<?= route('dashboard.index') ?>" class="btn-secondary">
                    <i class="bi bi-arrow-left"></i>
                    Volver
                </a>
            </div>
        </div>

    </div>
</div>

<script src="<?= base_url . '/assets/cronos.dashboard.js' ?>"></script>

@include('dashboard.layouts.footer')