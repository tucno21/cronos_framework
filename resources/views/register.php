<h1>Registrarce</h1>
<form method="post">
  <div class="mb-3">
    <label class="form-label">Nombre</label>
    <input name="name" type="text" class="form-control <?= ifError('name') ? 'is-invalid' : '' ?>" value="<?= old('name') ?>">
    <?php if (ifError('name')) : ?>
      <div class="invalid-feedback">
        <?= error('name') ?>
      </div>
    <?php endif; ?>
  </div>

  <div class="mb-3">
    <label class="form-label">Correo</label>
    <input name="email" type="text" class="form-control <?= ifError('email') ? 'is-invalid' : '' ?>" value="<?= old('email') ?>">
    <?php if (ifError('email')) : ?>
      <div class="invalid-feedback">
        <?= error('email') ?>
      </div>
    <?php endif; ?>
  </div>

  <div class="mb-3">
    <label class="form-label">Contraseña</label>
    <input name="password" type="password" class="form-control <?= ifError('password') ? 'is-invalid' : '' ?>" value="">
    <?php if (ifError('password')) : ?>
      <div class="invalid-feedback">
        <?= error('password') ?>
      </div>
    <?php endif; ?>
  </div>

  <div class="mb-3">
    <label class="form-label">Repetir Contraseña</label>
    <input name="repetir_password" type="password" class="form-control <?= ifError('repetir_password') ? 'is-invalid' : '' ?>" value="">
    <?php if (ifError('repetir_password')) : ?>
      <div class="invalid-feedback">
        <?= error('repetir_password') ?>
      </div>
    <?php endif; ?>
  </div>

  <button type="submit" class="btn btn-primary">Registrar</button>
</form>