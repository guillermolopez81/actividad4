<?php
declare(strict_types=1);

require dirname(__DIR__) . '/app/Database.php';
require dirname(__DIR__) . '/app/Auth.php';
require dirname(__DIR__) . '/app/Security.php';
require dirname(__DIR__) . '/app/Input.php';
require dirname(__DIR__) . '/app/Repository.php';
require dirname(__DIR__) . '/app/CompanySettings.php';

ini_set('display_errors', '0');
ini_set('session.use_strict_mode', '1');
ini_set('session.use_only_cookies', '1');
ini_set('session.use_trans_sid', '0');
session_name('CRM');
session_set_cookie_params([
    'httponly' => true,
    'samesite' => 'Lax',
    'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
]);
session_start();

function e($value): string
{
    return Security::e((string)$value);
}

function money($value): string
{
    [$pesos, $cents] = explode('.', (string)$value, 2) + [1 => '00'];
    $pesos = preg_replace('/\B(?=(\d{3})+(?!\d))/', '.', $pesos);
    return '$ ' . $pesos . ($cents === '00' ? '' : ',' . $cents);
}

function csrf(): string
{
    return '<input type="hidden" name="csrf" value="' . e(Security::token()) . '">';
}

function url(string $page, array $args = []): string
{
    return 'index.php?' . http_build_query(['page' => $page] + $args);
}

$page = $_GET['page'] ?? 'dashboard';
$allowed = ['login', 'logout', 'dashboard', 'clients', 'products', 'quotes', 'quote_new', 'quote_view', 'settings', 'company_logo'];
if (!in_array($page, $allowed, true)) {
    $page = 'dashboard';
}

$method = $_SERVER['REQUEST_METHOD'];
if ($page === 'company_logo' && ($method === 'GET' || $method === 'HEAD')) {
    session_write_close();
    try {
        (new CompanySettings(Database::connection()))->sendLogo($method === 'GET');
    } catch (PDOException $exception) {
        error_log($exception->getMessage());
        http_response_code(503);
        exit;
    }
}
if ($page === 'logout' && $method === 'GET') {
    Security::redirect('dashboard');
}

$error = '';
$companyInput = null;
try {
    if ($method === 'POST') {
        $postLimit = ini_parse_quantity(ini_get('post_max_size'));
        if ($postLimit > 0 && (int)($_SERVER['CONTENT_LENGTH'] ?? 0) > $postLimit) {
            http_response_code(413);
            exit('La solicitud supera el tamaño máximo permitido por PHP.');
        }
        Security::verify();
        $action = $_POST['action'] ?? '';
        if ($action === 'login') {
            if (Auth::user()) {
                Security::redirect('dashboard');
            }
            $page = 'login';
            $email = Input::text($_POST['email'] ?? '', 190);
            $password = $_POST['password'] ?? '';
            if ($email !== null && filter_var($email, FILTER_VALIDATE_EMAIL)
                && is_string($password) && $password !== '' && Auth::attempt($email, $password)) {
                Security::redirect('dashboard');
            }
            $error = 'Credenciales incorrectas.';
        } else {
            Auth::requireLogin();
            $user = Auth::user();

            switch ($action) {
                case 'logout':
                    Auth::logout();
                    Security::redirect('login');

                case 'company_save':
                    if ($user['role'] !== 'admin') {
                        http_response_code(403);
                        exit('Esta sección solo está disponible para administración.');
                    }
                    $page = 'settings';
                    $companyInput = $_POST;
                    (new CompanySettings(Database::connection()))->save($_POST, $_FILES['logo'] ?? []);
                    $_SESSION['company_saved'] = true;
                    Security::redirect('settings');

                case 'save':
                    $kind = $_POST['kind'] ?? '';
                    if (!in_array($kind, ['clients', 'products'], true)) {
                        throw new DomainException('Módulo inválido.');
                    }
                    $page = $kind;
                    $id = Input::integer($_POST['id'] ?? 0, 0);
                    if ($id === null) {
                        throw new DomainException('Identificador de registro inválido.');
                    }
                    $name = Input::text($_POST['name'] ?? '', 180);
                    if ($name === null || $name === '') {
                        throw new DomainException('Nombre inválido.');
                    }
                    if ($kind === 'clients') {
                        $document = Input::text($_POST['document'] ?? '', 40);
                        $email = Input::text($_POST['email'] ?? '', 190);
                        $phone = Input::text($_POST['phone'] ?? '', 50);
                        if ($document === null || $document === '' || $phone === null || $email === null
                            || ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL))) {
                            throw new DomainException('Documento, teléfono o correo inválido.');
                        }
                        $data = ['document' => $document, 'name' => $name, 'email' => $email, 'phone' => $phone];
                    } else {
                        $sku = Input::text($_POST['sku'] ?? '', 60);
                        $price = $_POST['price'] ?? '';
                        $stock = Input::integer($_POST['stock'] ?? null, 0, 4294967295);
                        if ($sku === null || $sku === '' || !is_string($price)
                            || !preg_match('/\A[0-9]{1,12}(\.[0-9]{1,2})?\z/', $price) || $stock === null) {
                            throw new DomainException('Revisa la referencia del producto, el precio y las existencias.');
                        }
                        $data = ['sku' => $sku, 'name' => $name, 'price' => $price, 'stock' => $stock];
                    }
                    (new Repository())->save($kind, $data, $id);
                    Security::redirect($kind);

                case 'delete':
                    if ($user['role'] !== 'admin') {
                        http_response_code(403);
                        exit('Solo administración puede eliminar.');
                    }
                    $kind = $_POST['kind'] ?? '';
                    $id = Input::integer($_POST['id'] ?? null);
                    if (!in_array($kind, ['clients', 'products'], true) || $id === null) {
                        throw new DomainException('Módulo o identificador inválido.');
                    }
                    $page = $kind;
                    (new Repository())->delete($kind, $id);
                    Security::redirect($kind);

                case 'quote':
                    $page = 'quote_new';
                    $client = Input::integer($_POST['client_id'] ?? null);
                    if ($client === null) {
                        throw new DomainException('Seleccione un cliente válido.');
                    }
                    $products = $_POST['product'] ?? [];
                    $quantities = $_POST['qty'] ?? [];
                    if (!is_array($products) || !array_is_list($products) || !is_array($quantities)
                        || !array_is_list($quantities) || count($products) !== count($quantities)) {
                        throw new DomainException('Líneas de cotización inválidas.');
                    }
                    $items = [];
                    foreach ($products as $index => $product) {
                        $items[] = ['product' => $product, 'qty' => $quantities[$index]];
                    }
                    $id = (new Repository())->createQuote($client, (int)$user['id'], $items);
                    Security::redirect('quote_view', ['id' => $id]);

                default:
                    throw new DomainException('Acción inválida.');
            }
        }
    }
} catch (PDOException $exception) {
    $error = match ($exception->errorInfo[1] ?? null) {
        1062 => 'Ya existe un registro con ese documento o referencia.',
        1451 => 'El registro está siendo utilizado por otro módulo.',
        1452 => 'Un registro relacionado ya no está disponible.',
        default => 'Error de base de datos. Revise la configuración y los registros del servidor.',
    };
    if ($exception->getCode() === '23000') {
        http_response_code(409);
    } else {
        http_response_code(500);
        error_log($exception->getMessage());
    }
} catch (DomainException $exception) {
    $error = $exception->getMessage();
    http_response_code(422);
} catch (Throwable $exception) {
    $error = 'No se pudo completar la operación.';
    http_response_code(500);
    error_log($exception->getMessage());
}

if ($page !== 'login') {
    Auth::requireLogin();
} elseif (Auth::user()) {
    Security::redirect('dashboard');
}

$user = Auth::user();
$isAdmin = ($user['role'] ?? '') === 'admin';
if ($page === 'settings' && !$isAdmin) {
    http_response_code(403);
    exit('Esta sección solo está disponible para administración.');
}
if ($page === 'company_logo' || $page === 'logout') {
    http_response_code(405);
    exit('Método no permitido.');
}

$viewAvailable = true;
try {
    if (in_array($page, ['dashboard', 'clients', 'products', 'quotes', 'quote_new', 'quote_view'], true)) {
        $repo = new Repository();
        switch ($page) {
            case 'dashboard':
                $stats = $repo->dashboard();
                $quotes = $repo->quotes(5);
                break;
            case 'clients':
            case 'products':
                $editId = Input::integer($_GET['edit'] ?? null);
                $record = $editId === null ? null : $repo->one($page, $editId);
                $rows = $repo->all($page);
                break;
            case 'quotes':
                $quotes = $repo->quotes();
                break;
            case 'quote_new':
                $clients = $repo->all('clients');
                $products = $repo->all('products');
                break;
            case 'quote_view':
                $quoteId = Input::integer($_GET['id'] ?? null);
                $quote = $quoteId === null ? null : $repo->quote($quoteId);
                if ($quote) {
                    $items = $repo->items($quoteId);
                } else {
                    http_response_code(404);
                }
                break;
        }
    }
} catch (PDOException $exception) {
    error_log($exception->getMessage());
    http_response_code(500);
    $error = 'No se pudieron consultar los datos. Revise la conexión con la base de datos.';
    $viewAvailable = false;
}

$company = CompanySettings::DEFAULTS;
try {
    $company = (new CompanySettings(Database::connection()))->details();
} catch (PDOException $exception) {
    error_log($exception->getMessage());
}
$companySaved = $page === 'settings' && !empty($_SESSION['company_saved']);
if ($companySaved) {
    unset($_SESSION['company_saved']);
}

require dirname(__DIR__) . '/app/views/layout.php';
