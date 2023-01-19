@include('layouts.head')
@include('layouts.headdasboard')

<main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
    <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
        <h1 class="h2">Blogs</h1>
        <div class="btn-toolbar mb-2 mb-md-0">
            <a href="<?= route('dashboard.create') ?>" class="btn btn-sm btn-outline-secondary">Crear Blog</a>
        </div>
    </div>

    <div class="table-responsive">
        <table class="table table-striped table-sm">
            <thead>
                <tr>
                    <th scope="col">#</th>
                    <th scope="col">Autor</th>
                    <th scope="col">Titulo</th>
                    <th scope="col">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($blogs as $blog) : ?>
                    <tr>
                        <td><?= $blog->id ?></td>
                        <td><?= $blog->name ?></td>
                        <td><?= $blog->title ?></td>
                        <td>
                            <a href="<?= route('dashboard.show', [$blog->slug]) ?>" class="btn btn-sm btn-outline-success">Ver Post</a>
                            <a href="<?= route('dashboard.edit', [$blog->id]) ?>" class="btn btn-sm btn-outline-primary">Editar</a>
                            <a href="<?= route('dashboard.destroy', [$blog->id]) ?>" class="btn btn-sm btn-outline-danger">Eliminar</a>
                        </td>
                    </tr>
                <?php endforeach ?>
            </tbody>
        </table>
    </div>
</main>

@include('layouts.footerdasboard')
@include('layouts.footer')