<?php if ($page === 'quotes'): ?>
    <div class="heading page-heading">
        <h1>Cotizaciones</h1>
        <a class="button" href="<?=url('quote_new')?>"><?=icon('add')?> Nueva cotización</a>
    </div>
    <section class="card">
        <?php require __DIR__ . '/quote_table.php'; ?>
    </section>
<?php elseif ($page === 'quote_new'): ?>
    <?php
    $canQuote = $clients && $products;
    ?>
    <h1>Nueva cotización</h1>
    <section class="card">
        <form method="post" id="quote-form">
            <?=csrf()?>
            <input type="hidden" name="action" value="quote">
            <label>Cliente
                <select name="client_id" required>
                    <option value="">Seleccione un cliente</option>
                    <?php foreach ($clients as $client): ?>
                        <option value="<?=$client['id']?>"><?=e($client['name'])?></option>
                    <?php endforeach; ?>
                </select>
            </label>
            <h2>Productos</h2>
            <div id="lines" data-max-lines="<?=Repository::MAX_QUOTE_LINES?>" data-max-cents="<?=Repository::MAX_TOTAL_CENTS?>"></div>
            <button type="button" class="secondary" id="add-line" <?=$canQuote ? '' : 'disabled'?>><?=icon('add')?> Agregar producto</button>
            <p><strong>Total estimado: <span id="estimated"><?=money(0)?></span></strong></p>
            <button type="submit" <?=$canQuote ? '' : 'disabled'?>>Guardar cotización</button>
        </form>
        <?php if (!$canQuote): ?>
            <p class="empty-hint">Primero debe registrar al menos un cliente y un producto.</p>
        <?php endif; ?>
    </section>
    <template id="line-template">
        <div class="quote-line">
            <select name="product[]" required aria-label="Producto">
                <option value="">Seleccione producto</option>
                <?php foreach ($products as $product): ?>
                    <option value="<?=$product['id']?>" data-price="<?=e($product['price'])?>"><?=e($product['name'])?> · <?=money($product['price'])?></option>
                <?php endforeach; ?>
            </select>
            <input type="number" name="qty[]" min="1" max="<?=Repository::MAX_QUANTITY?>" step="1" value="1" required aria-label="Cantidad">
            <button type="button" class="danger remove">Quitar</button>
        </div>
    </template>
    <script src="assets/quotes.js" defer></script>
<?php else: ?>
    <?php if (!$quote): ?>
        <p class="alert" role="alert">Cotización no encontrada.</p>
    <?php else: ?>
        <div class="heading page-heading">
            <h1><?=quoteNumber((int)$quote['id'])?></h1>
            <button type="button" onclick="window.print()">Imprimir / Guardar PDF</button>
        </div>
        <section class="card document">
            <div class="document-heading">
                <div class="brand"><?=$brand?></div>
                <?php if ($company['nit'] !== ''): ?>
                    <p>NIT <?=e($company['nit'])?></p>
                <?php endif; ?>
                <?php foreach (['address', 'phone', 'email'] as $field): ?>
                    <?php if ($company[$field] !== ''): ?>
                        <p><?=e($company[$field])?></p>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
            <p><strong>Cliente</strong> <?=e($quote['client_name'])?></p>
            <p><strong>Fecha</strong> <?=e(date('d/m/Y', strtotime($quote['created_at'])))?> · <?=quoteStatus($quote['status'])?></p>
            <div class="scroll" tabindex="0" role="region" aria-label="Productos cotizados">
                <table>
                    <thead>
                        <tr><th>Producto</th><th class="right">Cantidad</th><th class="right">Precio unitario</th><th class="right">Subtotal</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($items as $item): ?>
                            <tr>
                                <td><?=e($item['product_name'])?></td>
                                <td class="right"><?=$item['quantity']?></td>
                                <td class="right nowrap"><?=money($item['unit_price'])?></td>
                                <td class="right nowrap"><?=money($item['subtotal'])?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <p class="right document-total">Total <?=money($quote['total'])?></p>
            <p class="muted">Este documento no constituye factura electrónica.</p>
        </section>
    <?php endif; ?>
<?php endif; ?>
