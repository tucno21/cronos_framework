@include('dashboard.layouts.head')
<!-- CONTENT -->
<div class="bg-[#F7F7F7] overflow-fix h-[calc(100vh-3rem)]">
    <div class="space-y-2 mx-auto">
        <!-- cabecera -->
        <div class="flex justify-between items-center bg-white py-2 px-3 border-b border-gray-200">
            <h1 class="text-xl font-bold">Blogs</h1>
            <!-- boton -->
            <div class="">
                <button class="btn-sm btn-primary" id="btnCrear">Crear blog</button>
            </div>
        </div>

        <!-- cuerpo -->
        <div class="p-2 lg:p-4">
            <!-- <div class="bg-white p-3 mb-3 shadow"> -->
            <div class="card p-2 bg-white">
                <div id="simpleDatatable"></div>
            </div>
        </div>
    </div>
</div>
<!-- END CONTENT -->
</div>

<!-- MODAL -->
<div class="modal-init opacity-0 pointer-events-none" id="contentModal">
    <!-- <div class="modal-init opacity-0 pointer-events-none" id="contentModal"> -->
    <!-- fondo de modal -->
    <div class="modal-overlay"></div>
    <!-- contenido de modal -->
    <div class="modal-content">
        <div class="p-4 text-left">
            <!--cabecera-->
            <div class="flex justify-between items-center pb-1 border-b-2">
                <p class="text-2xl font-bold">Formulario Crear Blog</p>
                <div class="modal-close cursor-pointer font-bold px-2" title="cerrar modal">X</div>
            </div>
            <form id="formulario">
                <!--Body-->
                <div class="">
                    <div class="mt-3">
                        <label for="title" class="form-label">Titulo</label>
                        <div class="relative">
                            <div class="form-icon">
                                <i class="bi bi-cursor-text"></i>
                            </div>
                            <input type="text" name="title" id="title" class="form-input pl-8" placeholder="Titulo del blog" />
                        </div>
                    </div>
                    <div class="mt-3">
                        <label for="slug" class="form-label">Slug(titulo para link)</label>
                        <div class="relative">
                            <div class="form-icon">
                                <i class="bi bi-code-slash"></i>
                            </div>
                            <input type="text" name="slug" id="slug" class="form-input pl-8" placeholder="Slug" readonly />
                        </div>
                    </div>
                    <div class="mt-3">
                        <label for="content" class="form-label">Contenido</label>
                        <div class="relative">
                            <div class="form-icon">
                                <i class="bi bi-file-text"></i>
                            </div>
                            <textarea name="content" id="content" class="form-input pl-8" placeholder="Contenido del blog"></textarea>
                        </div>
                    </div>
                </div>

                <!--Footer-->
                <div class="flex justify-end pt-2 mt-3">
                    <button type="submit" class="btn btn-indigo mr-2" id="btnSubmit">Crear</button>
                    <button type="button" class="modal-close btn btn-danger">Cerrar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    const baseLink = "<?= base_url ?>";
</script>
<script src="<?= base_url . '/assets/cronos.dashboard.js' ?>"></script>
<script src="<?= base_url . '/assets/blog.js' ?>"></script>

@include('dashboard.layouts.footer')