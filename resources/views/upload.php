<h1>Enviar Imagen</h1>
<form method="post" enctype="multipart/form-data">

  <div class="mb-3">
    <label class="form-label">Imagen</label>
    <input name="imagen" type="file" class="form-control <?= ifError('email') ? 'is-invalid' : '' ?>" value="<?= old('email') ?>">
    <?php if (ifError('email')) : ?>
      <div class="invalid-feedback">
        <?= error('email') ?>
      </div>
    <?php endif; ?>
  </div>

  <div class="mb-3">
    <label class="form-label">Imagen</label>
    <input name="texto" type="text" class="form-control <?= ifError('email') ? 'is-invalid' : '' ?>" value="<?= old('email') ?>">
    <?php if (ifError('email')) : ?>
      <div class="invalid-feedback">
        <?= error('email') ?>
      </div>
    <?php endif; ?>
  </div>

  <button type="submit" class="btn btn-primary">Login</button>
</form>