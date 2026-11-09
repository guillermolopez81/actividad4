<?php

declare(strict_types=1);

final class Repository
{
    public const MAX_QUOTE_LINES = 30;
    public const MAX_QUANTITY = 100000;
    public const MAX_TOTAL_CENTS = 99999999999999;

    private PDO $db;

    public function __construct()
    {
        $this->db = Database::connection();
    }

    private function table(string $kind): string
    {
        return match ($kind) {
            'clients' => 'clients',
            'products' => 'products',
            default => throw new InvalidArgumentException('Entidad inválida'),
        };
    }

    public function all(string $kind): array
    {
        $table = $this->table($kind);
        return $this->db->query("SELECT * FROM $table ORDER BY id DESC")->fetchAll();
    }

    public function one(string $kind, int $id): ?array
    {
        $table = $this->table($kind);
        $query = $this->db->prepare("SELECT * FROM $table WHERE id = ?");
        $query->execute([$id]);

        return $query->fetch() ?: null;
    }

    public function save(string $kind, array $data, int $id = 0): void
    {
        $table = $this->table($kind);
        $columns = $kind === 'clients'
            ? ['document', 'name', 'email', 'phone']
            : ['sku', 'name', 'price', 'stock'];
        $values = array_map(fn ($column) => $data[$column], $columns);

        if ($id) {
            $assignments = implode(', ', array_map(fn ($column) => $column . ' = ?', $columns));
            $query = $this->db->prepare("UPDATE $table SET $assignments WHERE id = ?");
            $query->execute([...$values, $id]);
            if ($query->rowCount() === 0) {
                throw new DomainException('El registro que intenta editar ya no existe.');
            }
        } else {
            $names = implode(', ', $columns);
            $placeholders = implode(', ', array_fill(0, count($columns), '?'));
            $query = $this->db->prepare("INSERT INTO $table ($names) VALUES ($placeholders)");
            $query->execute($values);
        }
    }

    public function delete(string $kind, int $id): void
    {
        $table = $this->table($kind);
        $query = $this->db->prepare("DELETE FROM $table WHERE id = ?");
        $query->execute([$id]);
    }

    public function dashboard(): array
    {
        return $this->db->query(
            'SELECT (SELECT COUNT(*) FROM clients) AS clients,
                    (SELECT COUNT(*) FROM products) AS products,
                    COUNT(*) AS quotes, COALESCE(SUM(total), 0) AS quoted_total
             FROM quotes'
        )->fetch();
    }

    public function quotes(?int $limit = null): array
    {
        $sql = 'SELECT q.*, c.name AS client_name, u.name AS author
                FROM quotes q
                JOIN clients c ON c.id = q.client_id
                JOIN users u ON u.id = q.user_id
                ORDER BY q.id DESC';

        if ($limit === null) {
            return $this->db->query($sql)->fetchAll();
        }

        $query = $this->db->prepare($sql . ' LIMIT ?');
        $query->bindValue(1, $limit, PDO::PARAM_INT);
        $query->execute();

        return $query->fetchAll();
    }

    public function quote(int $id): ?array
    {
        $query = $this->db->prepare(
            'SELECT q.*, c.name AS client_name
             FROM quotes q JOIN clients c ON c.id = q.client_id
             WHERE q.id = ?'
        );
        $query->execute([$id]);

        return $query->fetch() ?: null;
    }

    public function items(int $id): array
    {
        $query = $this->db->prepare(
            'SELECT qi.*, p.name AS product_name
             FROM quote_items qi JOIN products p ON p.id = qi.product_id
             WHERE qi.quote_id = ? ORDER BY qi.id'
        );
        $query->execute([$id]);

        return $query->fetchAll();
    }

    public function createQuote(int $client, int $user, array $items): int
    {
        if (!$items || count($items) > self::MAX_QUOTE_LINES) {
            throw new DomainException('La cotización debe tener entre 1 y ' . self::MAX_QUOTE_LINES . ' productos.');
        }
        if (!$this->one('clients', $client)) {
            throw new DomainException('Seleccione un cliente válido.');
        }

        $db = $this->db;
        $db->beginTransaction();

        try {
            $totalCents = 0;
            $lines = [];
            $productQuery = $db->prepare('SELECT id, price FROM products WHERE id = ?');

            foreach ($items as $line) {
                $productId = Input::integer($line['product'] ?? null);
                $quantity = Input::integer($line['qty'] ?? null, 1, self::MAX_QUANTITY);

                if ($productId === null || $quantity === null) {
                    throw new DomainException('Producto o cantidad inválida.');
                }

                $productQuery->execute([$productId]);
                $product = $productQuery->fetch();

                if (!$product) {
                    throw new DomainException('Producto no encontrado.');
                }

                // PDO devuelve DECIMAL como texto. Los centavos evitan redondeos binarios.
                $price = $product['price'];
                $priceCents = (int)str_replace('.', '', $price);
                if ($priceCents < 0) {
                    throw new DomainException('El producto tiene un precio inválido.');
                }
                if ($priceCents > intdiv(self::MAX_TOTAL_CENTS - $totalCents, $quantity)) {
                    throw new DomainException('El total de la cotización está fuera de rango.');
                }

                $subtotalCents = $priceCents * $quantity;
                $totalCents += $subtotalCents;
                $lines[] = [$productId, $quantity, $price, self::decimal($subtotalCents)];
            }

            $quoteQuery = $db->prepare('INSERT INTO quotes (client_id, user_id, total) VALUES (?, ?, ?)');
            $quoteQuery->execute([$client, $user, self::decimal($totalCents)]);
            $id = (int)$db->lastInsertId();

            $itemQuery = $db->prepare(
                'INSERT INTO quote_items (quote_id, product_id, quantity, unit_price, subtotal)
                 VALUES (?, ?, ?, ?, ?)'
            );

            foreach ($lines as $line) {
                $itemQuery->execute([$id, ...$line]);
            }

            $db->commit();
            return $id;
        } catch (Throwable $error) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $error;
        }
    }

    private static function decimal(int $cents): string
    {
        return sprintf('%d.%02d', intdiv($cents, 100), $cents % 100);
    }
}
