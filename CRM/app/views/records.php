<?php
$kind = $page;
$isClient = $kind === 'clients';
?>
<div class="page-heading">
    <h1><?=e($title)?></h1>
    <p class="muted"><?=$isClient ? 'Gestiona los datos de contacto de tus clientes.' : 'Gestiona las referencias, los precios y las existencias.'?></p>
</div>
<section class="card" id="record-form">
    <h2><?=$record ? 'Editar' : 'Nuevo'?> <?=$isClient ? 'cliente' : 'producto'?></h2>
    <form method="post" class="form-grid">
        <?=csrf()?>
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="kind" value="<?=e($kind)?>">
        <input type="hidden" name="id" value="<?=$record['id'] ?? 0?>">
        <?php if ($isClient): ?>
            <label>Identificación <input name="document" maxlength="40" required value="<?=e($record['document'] ?? '')?>"></label>
        <?php else: ?>
            <label>Referencia (SKU) <input name="sku" maxlength="60" required value="<?=e($record['sku'] ?? '')?>"></label>
        <?php endif; ?>
        <label>Nombre <input name="name" maxlength="180" required value="<?=e($record['name'] ?? '')?>"></label>
        <?php if ($isClient): ?>
            <label>Correo <input type="email" name="email" maxlength="190" value="<?=e($record['email'] ?? '')?>"></label>
            <label>Teléfono <input type="tel" name="phone" maxlength="50" value="<?=e($record['phone'] ?? '')?>"></label>
        <?php else: ?>
            <?php $price = preg_replace('/\.00$/', '', $record['price'] ?? '0'); ?>
            <label>Precio COP
                <input type="number" min="0" max="999999999999.99" step="0.01" name="price" required value="<?=e($price)?>">
            </label>
            <label>Existencias
                <input type="number" min="0" max="4294967295" step="1" name="stock" required value="<?=e($record['stock'] ?? '0')?>">
            </label>
        <?php endif; ?>
        <div class="form-actions">
            <button>Guardar</button>
            <?php if ($record): ?>
                <a class="button secondary" href="<?=url($kind)?>">Cancelar</a>
            <?php endif; ?>
        </div>
    </form>
</section>
<section class="card">
    <h2>Registros (<?=count($rows)?>)</h2>
    <?php if (!$rows): ?>
        <p class="empty-hint"><?=$isClient ? 'Aún no hay clientes registrados.' : 'Aún no hay productos registrados.'?></p>
    <?php else: ?>
        <div class="scroll" tabindex="0" role="region" aria-label="<?=e($title)?>">
            <table>
                <thead>
                    <tr><th>ID</th><th><?=$isClient ? 'Documento' : 'Referencia (SKU)'?></th><th>Nombre</th><th><?=$isClient ? 'Correo' : 'Precio'?></th><th>Acciones</th></tr>
                </thead>
                <tbody>
                    <?php foreach ($rows as $row): ?>
                        <tr>
                            <td><?=$row['id']?></td>
                            <td><?=e($isClient ? $row['document'] : $row['sku'])?></td>
                            <td><?=e($row['name'])?></td>
                            <td><?=$isClient ? e($row['email']) : money($row['price'])?></td>
                            <td class="nowrap">
                                <a href="<?=e(url($kind, ['edit' => $row['id']]))?>#record-form">Editar</a>
                                <?php if ($isAdmin): ?>
                                    <form class="inline" method="post" onsubmit="return confirm('¿Eliminar este registro?')">
                                        <?=csrf()?>
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="kind" value="<?=e($kind)?>">
                                        <input type="hidden" name="id" value="<?=$row['id']?>">
                                        <button class="danger">Eliminar</button>
                                    </form>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</section>
