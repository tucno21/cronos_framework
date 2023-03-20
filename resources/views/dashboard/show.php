@include('dashboard.layouts.head')
<!-- CONTENT -->
<div class="bg-[#F7F7F7] overflow-fix h-[calc(100vh-3rem)]">
    <div class="space-y-2 mx-auto">
        <!-- cabecera -->
        <div class="flex justify-between items-center bg-white py-2 px-3 border-b border-gray-200">
            <h1 class="text-xl font-bold">Resultado</h1>
            <!-- boton -->
            <div class=""></div>
        </div>

        <!-- cuerpo -->
        <div class="p-2 lg:p-4">
            <!-- post de blog -->
            <div class="container mx-auto p-4 bg-white drop-shadow">
                <div class="bg-cover bg-center flex items-center">
                    <div class="bg-black bg-opacity-50 w-full h-full flex flex-col justify-center items-center p-4">
                        <h1 class="text-4xl text-white font-bold mb-4"><?= $blog->title ?></h1>
                        <p class="text-gray-300 text-lg">Por <span class="font-bold"><?= $blog->name ?></span></p>
                    </div>
                </div>
                <div class="container mx-auto p-4">
                    <p class="text-xl text-justify leading-8 mb-8">
                        <?= $blog->content ?>
                    </p>
                    <a href="<?= route('dashboard.index') ?>" class="bg-blue-500 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                        Volver
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
<!-- END CONTENT -->
</div>
<script src="<?= base_url . '/assets/cronos.dashboard.js' ?>"></script>

@include('dashboard.layouts.footer')