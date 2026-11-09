<?php
$fields = [
    'name' => ['Nombre de la empresa', 'text', 180],
    'nit' => ['NIT', 'text', 40],
    'address' => ['Dirección', 'text', 255],
    'phone' => ['Teléfono', 'tel', 50],
    'email' => ['Correo', 'email', 190],
];
?>
<div class="page-heading">
    <h1>Configuración</h1>
    <p class="muted">Personaliza la identidad que aparece en el CRM y en tus cotizaciones.</p>
</div>
<?php if ($companySaved): ?>
    <p class="alert success" role="status">Se guardaron los datos de la empresa.</p>
<?php endif; ?>
<section class="card">
    <h2>Datos de la empresa</h2>
    <form method="post" enctype="multipart/form-data">
        <?=csrf()?>
        <input type="hidden" name="action" value="company_save">
        <div class="form-grid">
            <?php foreach ($fields as $field => [$label, $type, $limit]): ?>
                <?php $value = $companyInput[$field] ?? $company[$field]; ?>
                <label><?=e($label)?>
                    <input name="<?=e($field)?>" type="<?=e($type)?>" maxlength="<?=$limit?>"
                           value="<?=e(is_string($value) ? $value : $company[$field])?>" <?=$field === 'name' ? 'required' : ''?>>
                </label>
            <?php endforeach; ?>
        </div>
        <div class="logo-field">
            <label><?=$logo ? 'Reemplazar logo' : 'Cargar logo'?>
                <input type="file" name="logo" accept="image/jpeg,image/png,image/webp" aria-describedby="logo-help">
                <span class="muted" id="logo-help">JPG, PNG o WebP. Hasta 2 MB y 4096 píxeles por lado.</span>
            </label>
            <?php if ($logo): ?>
                <img class="logo-preview" src="<?=e($logo)?>" alt="Logo actual de la empresa">
                <label class="checkbox">
                    <input type="checkbox" name="remove_logo" value="1"> Eliminar el logo al guardar
                </label>
            <?php endif; ?>
        </div>
        <button>Guardar cambios</button>
    </form>
</section>
