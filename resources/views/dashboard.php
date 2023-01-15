<div class="container mt-4">
    <h1>Dashboard</h1>

    <!-- crear lista de usuarios con bootstrap -->
    <div class="row">
        <div class="col-12">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th scope="col">ID</th>
                        <th scope="col">Nombre</th>
                        <th scope="col">Email</th>
                        <th scope="col">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user) : ?>
                        <tr>
                            <th scope="row"><?= $user->id ?></th>
                            <td><?= $user->name ?></td>
                            <td><?= $user->email ?></td>
                            <td>
                                <a href="<?= route('dashboard.user', ['id' => $user->id]) ?>" class="btn btn-primary">Ver</a>
                            </td>
                        </tr>
                    <?php endforeach ?>
                </tbody>
            </table>
        </div>
    </div>
</div>