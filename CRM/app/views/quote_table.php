<?php if (!$quotes): ?>
    <p class="empty-hint">Aún no hay cotizaciones registradas.</p>
<?php else: ?>
    <div class="scroll" tabindex="0" role="region" aria-label="Tabla de cotizaciones">
        <table class="quote-table">
            <thead>
                <tr>
                    <th>Número</th><th>Cliente</th><th>Fecha</th><th class="right">Total</th><th>Estado</th><th>Acción</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($quotes as $quote): ?>
                    <?php $detailUrl = url('quote_view', ['id' => $quote['id']]); ?>
                    <tr>
                        <td class="nowrap"><a href="<?=e($detailUrl)?>"><?=quoteNumber((int)$quote['id'])?></a></td>
                        <td><?=e($quote['client_name'])?></td>
                        <td class="nowrap"><?=e(date('d/m/Y', strtotime($quote['created_at'])))?></td>
                        <td class="right nowrap"><?=money($quote['total'])?></td>
                        <td><?=quoteStatus($quote['status'])?></td>
                        <td><a class="icon-link" href="<?=e($detailUrl)?>" aria-label="Ver <?=quoteNumber((int)$quote['id'])?>"><?=icon('eye')?></a></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
