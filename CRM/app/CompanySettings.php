<?php
declare(strict_types=1);

final class CompanySettings
{
    public const DEFAULTS = [
        'name' => 'Lujos y Repuestos Lopez',
        'nit' => '',
        'address' => '',
        'phone' => '',
        'email' => '',
        'logo_type' => null,
    ];

    private const LOGO_LIMIT = 2 * 1024 * 1024;
    private const LOGO_TYPES = ['image/jpeg', 'image/png', 'image/webp'];

    public function __construct(private PDO $db)
    {
    }

    public function details(): array
    {
        try {
            $query = $this->db->prepare(
                'SELECT name, nit, address, phone, email, logo_type FROM company_settings WHERE id = ?'
            );
            $query->execute([1]);
            return $query->fetch() ?: self::DEFAULTS;
        } catch (PDOException $error) {
            if ($error->getCode() !== '42S02') {
                throw $error;
            }
            return self::DEFAULTS;
        }
    }

    public function sendLogo(bool $includeBody): never
    {
        try {
            $query = $this->db->prepare('SELECT logo, logo_type FROM company_settings WHERE id = ?');
            $query->execute([1]);
            $image = $query->fetch();
        } catch (PDOException $error) {
            if ($error->getCode() !== '42S02') {
                throw $error;
            }
            $image = false;
        }

        if (!$image || !$image['logo'] || !in_array($image['logo_type'], self::LOGO_TYPES, true)) {
            http_response_code(404);
            exit;
        }

        header('Content-Type: ' . $image['logo_type']);
        header('Content-Length: ' . strlen($image['logo']));
        header('X-Content-Type-Options: nosniff');
        header('Cache-Control: no-store');
        if ($includeBody) {
            echo $image['logo'];
        }
        exit;
    }

    public function save(array $input, array $upload): void
    {
        $limits = ['name' => 180, 'nit' => 40, 'address' => 255, 'phone' => 50, 'email' => 190];
        $details = [];
        foreach ($limits as $field => $limit) {
            $value = Input::text($input[$field] ?? '', $limit);
            if ($value === null) {
                throw new DomainException('Revise la longitud y el contenido de los datos de la empresa.');
            }
            $details[$field] = $value;
        }
        if ($details['name'] === '') {
            throw new DomainException('Ingrese el nombre de la empresa.');
        }
        if ($details['email'] !== '' && !filter_var($details['email'], FILTER_VALIDATE_EMAIL)) {
            throw new DomainException('Ingrese un correo válido para la empresa.');
        }

        [$logo, $type] = $this->readUpload($upload);
        $replaceLogo = $logo !== null || ($input['remove_logo'] ?? '') === '1';

        try {
            $sql = 'INSERT INTO company_settings (id, name, nit, address, phone, email, logo, logo_type)
                    VALUES (1, ?, ?, ?, ?, ?, ?, ?)
                    ON DUPLICATE KEY UPDATE name = VALUES(name), nit = VALUES(nit),
                    address = VALUES(address), phone = VALUES(phone), email = VALUES(email)';
            if ($replaceLogo) {
                $sql .= ', logo = VALUES(logo), logo_type = VALUES(logo_type)';
            }

            $query = $this->db->prepare($sql);
            foreach (array_values($details) as $index => $value) {
                $query->bindValue($index + 1, $value);
            }
            $query->bindValue(6, $logo, $logo === null ? PDO::PARAM_NULL : PDO::PARAM_LOB);
            $query->bindValue(7, $type, $type === null ? PDO::PARAM_NULL : PDO::PARAM_STR);
            $query->execute();
        } catch (PDOException $error) {
            if ($error->getCode() === '42S02') {
                throw new DomainException('Falta la tabla company_settings. Consulte la sección de bases existentes del README.');
            }
            throw $error;
        }
    }

    private function readUpload(array $upload): array
    {
        $error = $upload['error'] ?? UPLOAD_ERR_NO_FILE;
        if ($error === UPLOAD_ERR_NO_FILE) {
            return [null, null];
        }

        $path = $upload['tmp_name'] ?? null;
        if ($error !== UPLOAD_ERR_OK || !is_string($path) || !is_uploaded_file($path)) {
            throw new DomainException('No se pudo cargar el logo. El tamaño máximo es 2 MB.');
        }

        $logo = file_get_contents($path, false, null, 0, self::LOGO_LIMIT + 1);
        if ($logo === false || $logo === '' || strlen($logo) > self::LOGO_LIMIT) {
            throw new DomainException('El logo debe ser una imagen de hasta 2 MB.');
        }

        $type = (new finfo(FILEINFO_MIME_TYPE))->buffer($logo);
        $dimensions = @getimagesizefromstring($logo);
        if (!in_array($type, self::LOGO_TYPES, true) || !$dimensions || $dimensions['mime'] !== $type) {
            throw new DomainException('Seleccione una imagen válida en formato JPG, PNG o WebP.');
        }
        if ($dimensions[0] < 1 || $dimensions[1] < 1 || $dimensions[0] > 4096 || $dimensions[1] > 4096) {
            throw new DomainException('El logo admite hasta 4096 píxeles por lado.');
        }
        if ($type === 'image/webp' && !function_exists('imagecreatefromwebp')) {
            $valid = self::hasWebpImage($logo);
        } else {
            $valid = @imagecreatefromstring($logo) !== false;
        }
        if (!$valid) {
            throw new DomainException('El archivo del logo está dañado o no se puede leer.');
        }

        return [$logo, $type];
    }

    private static function hasWebpImage(string $logo): bool
    {
        $size = strlen($logo);
        if ($size < 20 || substr($logo, 0, 4) !== 'RIFF' || substr($logo, 8, 4) !== 'WEBP'
            || unpack('V', $logo, 4)[1] !== $size - 8) {
            return false;
        }

        // Algunas versiones de GD en XAMPP solo permiten comprobar la estructura de WebP.
        $offset = 12;
        $hasImage = false;
        while ($offset + 8 <= $size) {
            $chunk = substr($logo, $offset, 4);
            $length = unpack('V', $logo, $offset + 4)[1];
            $offset += 8 + $length + ($length % 2);
            $hasImage = $hasImage || match ($chunk) {
                'VP8 ' => $length >= 10,
                'VP8L' => $length >= 5,
                'ANMF' => $length >= 24,
                default => false,
            };
        }

        return $hasImage && $offset === $size;
    }
}
