<?php
function icon(string $name): string
{
    $paths = [
        'home' => '<path d="m3 10 9-7 9 7v10H3zM9 20v-7h6v7"/>',
        'clients' => '<circle cx="12" cy="7" r="3"/><path d="M6 21v-3a6 6 0 0 1 12 0v3ZM4 9a3 3 0 0 0 0 5m16-5a3 3 0 0 1 0 5M4 16a4 4 0 0 0-2 4m18-4a4 4 0 0 1 2 4"/>',
        'products' => '<path d="m12 2 9 5v10l-9 5-9-5V7zm0 10 9-5m-9 5L3 7m9 5v10"/>',
        'quotes' => '<path d="M5 2h9l5 5v15H5zm9 0v6h5M8 12h8m-8 4h8"/>',
        'settings' => '<circle cx="12" cy="12" r="3"/><path d="m9 3-1 3-3 1-2 4 2 2v3l3 2 1 3h5l1-3 3-1 2-4-2-2V8l-3-2-1-3z"/>',
        'money' => '<path d="M12 2v20m5-15a4 4 0 0 0-4-3h-2a4 4 0 0 0 0 8h2a4 4 0 0 1 0 8h-2a4 4 0 0 1-4-3"/>',
        'bolt' => '<path d="m13 2-9 12h7l-1 8 10-12h-7z"/>',
        'add' => '<path d="M12 4v16M4 12h16"/>',
        'menu' => '<path d="M3 6h18M3 12h18M3 18h18"/>',
        'chevron' => '<path d="m6 9 6 6 6-6"/>',
        'eye' => '<path d="M2 12s4-7 10-7 10 7 10 7-4 7-10 7-10-7-10-7z"/><circle cx="12" cy="12" r="3"/>',
    ];

    return '<svg class="icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8"'
        . ' stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">' . $paths[$name] . '</svg>';
}

function quoteNumber(int $id): string
{
    return 'COT-' . str_pad((string)$id, 4, '0', STR_PAD_LEFT);
}

function quoteStatus(string $status): string
{
    return '<span class="badge status-' . e($status) . '">' . e(ucfirst($status)) . '</span>';
}

$navigation = [
    'dashboard' => ['Panel principal', 'home'],
    'clients' => ['Clientes', 'clients'],
    'products' => ['Productos', 'products'],
    'quotes' => ['Cotizaciones', 'quotes'],
];
if ($isAdmin) {
    $navigation['settings'] = ['Configuración', 'settings'];
}
$section = in_array($page, ['quote_new', 'quote_view'], true) ? 'quotes' : $page;
$title = $navigation[$section][0] ?? 'Iniciar sesión';
$view = match ($page) {
    'clients', 'products' => 'records',
    'quotes', 'quote_new', 'quote_view' => 'quotes',
    default => $page,
};
$logo = $company['logo_type'] ? url('company_logo') : null;
$brand = ($logo ? '<img src="' . e($logo) . '" alt="">' : '') . '<strong>' . e($company['name']) . '</strong>';
?>
<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?=e($title)?> | <?=e($company['name'])?></title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body class="<?=$user ? 'app' : 'login'?>">
<?php if ($user): ?>
    <aside class="sidebar" id="sidebar">
        <a class="brand" href="<?=url('dashboard')?>"><?=$brand?></a>
        <nav aria-label="Navegación principal">
            <?php foreach ($navigation as $route => [$label, $symbol]): ?>
                <a href="<?=url($route)?>" <?=$section === $route ? 'aria-current="page"' : ''?>>
                    <?=icon($symbol)?> <?=e($label)?>
                </a>
            <?php endforeach; ?>
        </nav>
    </aside>
    <header class="topbar">
        <button class="menu-button" type="button" aria-label="Menú de navegación" aria-controls="sidebar" aria-expanded="true">
            <?=icon('menu')?>
        </button>
        <a class="brand topbar-brand" href="<?=url('dashboard')?>"><?=$brand?></a>
        <details class="account">
            <summary aria-label="Cuenta de <?=e($user['name'])?>">
                <span class="avatar"><?=e(mb_strtoupper(mb_substr($user['name'], 0, 1)))?></span>
                <span class="account-name"><?=e($user['name'])?></span>
                <?=icon('chevron')?>
            </summary>
            <form method="post" action="<?=url('logout')?>">
                <?=csrf()?>
                <input type="hidden" name="action" value="logout">
                <button class="secondary">Cerrar sesión</button>
            </form>
        </details>
    </header>
    <script>
        const menu = document.querySelector('.menu-button');
        menu.addEventListener('click', () => {
            const hidden = document.body.classList.toggle('sidebar-hidden');
            menu.setAttribute('aria-expanded', String(!hidden));
        });
    </script>
<?php endif; ?>
    <main>
        <?php if ($error): ?>
            <p class="alert" role="alert"><?=e($error)?></p>
        <?php endif; ?>
        <?php if ($viewAvailable): ?>
            <?php require __DIR__ . '/' . $view . '.php'; ?>
        <?php endif; ?>
    </main>
</body>
</html>
