<div class="mb-3">
    <label for="textTitle" class="form-label">Titulo</label>
    <input type="text" class="form-control <?= ifError('title') ? 'is-invalid' : 'border-primary' ?>" id="textTitle" name="title" value="<?= old('title') ?? ($blog->title ?? '') ?>">
    <?php if (ifError('title')) : ?>
        <div class="invalid-feedback">
            <?= error('title') ?>
        </div>
    <?php endif; ?>
</div>
<div class="mb-3">
    <label for="textSlug" class="form-label">Slug(titulo para link)</label>
    <input type="text" class="form-control <?= ifError('slug') ? 'is-invalid' : 'border-primary' ?>" id="textSlug" name="slug" readonly value="<?= old('slug') ?? ($blog->slug ?? '') ?>">
    <?php if (ifError('slug')) : ?>
        <div class="invalid-feedback">
            <?= error('slug') ?>
        </div>
    <?php endif; ?>
</div>
<div class="mb-3">
    <label for="content" class="form-label">Contenido</label>
    <textarea class="form-control <?= ifError('content') ? 'is-invalid' : 'border-primary' ?>" id="content" name="content" rows="3"><?= old('content') ?? ($blog->content ?? '') ?></textarea>
    <?php if (ifError('content')) : ?>
        <div class="invalid-feedback">
            <?= error('content') ?>
        </div>
    <?php endif; ?>
</div>