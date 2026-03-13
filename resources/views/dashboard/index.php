@include('dashboard.layouts.head')

<!-- MAIN CONTENT -->
<div class="main-content">
    <div class="max-w-7xl mx-auto">

        <!-- Page header -->
        <div class="page-header">
            <div>
                <h1 class="page-header__title">Gestión de Blogs</h1>
                <p class="page-header__subtitle">Administra todos tus blogs y artículos</p>
            </div>
            <button class="btn-primary" id="btnCrear">
                <i class="bi bi-plus-lg"></i>
                Crear Blog
            </button>
        </div>

        <!-- Table card -->
        <div class="card">
            <div id="simpleDatatable"></div>
        </div>

    </div>
</div>

<!-- MODAL -->
<div class="modal-init opacity-0 pointer-events-none" id="contentModal">
    <div class="modal-overlay"></div>
    <div class="modal-content">

        <!-- Header -->
        <div class="modal-header">
            <div>
                <h2 class="modal-title">Crear Nuevo Blog</h2>
                <p class="modal-subtitle">Completa los campos para crear un nuevo blog</p>
            </div>
            <button class="modal-close" title="Cerrar modal">
                <i class="bi bi-x-lg"></i>
            </button>
        </div>

        <!-- Form -->
        <form id="formulario">
            <div class="modal-body">
                <div class="space-y-5">

                    <!-- Title -->
                    <div>
                        <label for="title" class="form-label">Título</label>
                        <div class="relative">
                            <div class="form-icon">
                                <i class="bi bi-type"></i>
                            </div>
                            <input
                                type="text"
                                name="title"
                                id="title"
                                class="form-input"
                                placeholder="Ingresa el título del blog"
                                required />
                        </div>
                    </div>

                    <!-- Slug -->
                    <div>
                        <label for="slug" class="form-label">Slug</label>
                        <div class="relative">
                            <div class="form-icon">
                                <i class="bi bi-link-45deg"></i>
                            </div>
                            <input
                                type="text"
                                name="slug"
                                id="slug"
                                class="form-input"
                                placeholder="se-genera-automaticamente"
                                readonly />
                        </div>
                        <p class="form-hint">El slug se genera automáticamente desde el título</p>
                    </div>

                    <!-- Content -->
                    <div>
                        <label for="content" class="form-label">Contenido</label>
                        <div class="relative">
                            <div class="form-icon top-3">
                                <i class="bi bi-file-text"></i>
                            </div>
                            <textarea
                                name="content"
                                id="content"
                                class="form-input"
                                placeholder="Escribe el contenido de tu blog..."
                                required></textarea>
                        </div>
                    </div>

                </div>
            </div>

            <!-- Footer -->
            <div class="modal-footer">
                <button type="button" class="btn-secondary modal-close">
                    Cancelar
                </button>
                <button type="submit" class="btn-primary" id="btnSubmit">
                    <i class="bi bi-check-lg"></i>
                    Guardar Blog
                </button>
            </div>
        </form>

    </div>
</div>

<script>
    const baseLink = "<?= base_url ?>";
</script>
<script src="<?= base_url . '/assets/js/cronos.dashboard.js' ?>"></script>
<script src="<?= base_url . '/assets/js/blog.js' ?>"></script>

@include('dashboard.layouts.footer')