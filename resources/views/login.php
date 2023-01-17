@include('layouts.head')
<h1>Iniciar Sesión</h1>
<form method="post">

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
    <label class="form-label">Password</label>
    <input name="password" type="text" class="form-control <?= ifError('password') ? 'is-invalid' : '' ?>" value="<?= old('password') ?>">
    <?php if (ifError('password')) : ?>
      <div class="invalid-feedback">
        <?= error('password') ?>
      </div>
    <?php endif; ?>
  </div>



  <button type="submit" class="btn btn-primary">Login</button>
</form>
@include('layouts.footer')