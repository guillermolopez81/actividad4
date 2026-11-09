<?php
$metrics = [
    ['Clientes', 'clients', $stats['clients']],
    ['Productos', 'products', $stats['products']],
    ['Cotizaciones', 'quotes', $stats['quotes']],
    ['Valor cotizado', 'money', money($stats['quoted_total'])],
];
?>
<div class="page-heading">
    <h1>Panel principal</h1>
    <p class="muted">Gestión comercial para importadoras</p>
</div>
<div class="stats">
    <?php foreach ($metrics as [$label, $symbol, $value]): ?>
        <article class="card metric metric-<?=e($symbol)?>">
            <?=icon($symbol)?>
            <div><span><?=e($label)?></span><strong><?=e($value)?></strong></div>
        </article>
    <?php endforeach; ?>
</div>
<div class="dashboard-grid">
    <section class="card">
        <div class="heading">
            <h2><?=icon('quotes')?> Últimas cotizaciones</h2>
            <a href="<?=url('quotes')?>">Ver todas</a>
        </div>
        <?php require __DIR__ . '/quote_table.php'; ?>
    </section>
    <section class="card">
        <h2 class="with-icon"><?=icon('bolt')?> Accesos rápidos</h2>
        <div class="quick-links">
            <a class="button" href="<?=url('quote_new')?>"><?=icon('add')?> Nueva cotización</a>
            <a class="button secondary" href="<?=url('clients')?>#record-form"><?=icon('clients')?> Nuevo cliente</a>
            <a class="button secondary" href="<?=url('products')?>#record-form"><?=icon('products')?> Nuevo producto</a>
        </div>
    </section>
</div>
