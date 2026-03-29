<?php

namespace App\Service;

use App\Support\AppContext;

final class OrderPricingService
{
    public function __construct(private readonly AppContext $app)
    {
    }

    public function resolveClientItems(array $items): array
    {
        $normalized = $this->normalizeItems($items, 'perfume_id');
        if (isset($normalized['error'])) {
            return $normalized;
        }

        $stmt = $this->db()->prepare(
            "SELECT
                p.id AS product_id,
                COALESCE(pp.price_dzd, pc.price_dzd, 0) AS unit_price
             FROM perfume_catalog pc
             INNER JOIN products p ON p.perfume_catalog_id = pc.id
             LEFT JOIN product_prices pp
                ON pp.product_id = p.id
               AND pp.sale_type = 'DETAIL'
               AND pp.ends_at IS NULL
             WHERE pc.id = :id
               AND pc.is_active = 1
               AND p.is_active = 1
             ORDER BY pp.id DESC, p.id ASC
             LIMIT 1"
        );

        return $this->resolveItems($normalized['items'], $stmt);
    }

    public function resolveAdminItems(array $items): array
    {
        $normalized = $this->normalizeItems($items, 'product_id');
        if (isset($normalized['error'])) {
            return $normalized;
        }

        $stmt = $this->db()->prepare(
            "SELECT
                p.id AS product_id,
                COALESCE(pp.price_dzd, pc.price_dzd, 0) AS unit_price
             FROM products p
             INNER JOIN perfume_catalog pc ON pc.id = p.perfume_catalog_id
             LEFT JOIN product_prices pp
                ON pp.product_id = p.id
               AND pp.sale_type = 'DETAIL'
               AND pp.ends_at IS NULL
             WHERE p.id = :id
               AND p.is_active = 1
               AND pc.is_active = 1
             ORDER BY pp.id DESC
             LIMIT 1"
        );

        return $this->resolveItems($normalized['items'], $stmt);
    }

    public function total(array $items): float
    {
        $total = 0.0;
        foreach ($items as $item) {
            $total += (float) $item['line_total'];
        }

        return $total;
    }

    private function normalizeItems(array $items, string $idKey): array
    {
        $normalized = [];

        foreach ($items as $index => $line) {
            $itemId = (int) ($line[$idKey] ?? 0);
            $qty = (float) ($line['qty'] ?? 0);

            if ($itemId <= 0) {
                return ['error' => 'Produit invalide a la ligne ' . ($index + 1) . '.', 'status' => 422];
            }
            if ($qty <= 0) {
                return ['error' => 'Quantite invalide a la ligne ' . ($index + 1) . '.', 'status' => 422];
            }

            $normalized[] = [
                'lookup_id' => $itemId,
                'qty' => $qty,
            ];
        }

        return ['items' => $normalized];
    }

    private function resolveItems(array $items, \PDOStatement $stmt): array
    {
        $resolved = [];

        foreach ($items as $index => $line) {
            $stmt->execute(['id' => $line['lookup_id']]);
            $product = $stmt->fetch();
            if (!$product) {
                return ['error' => 'Produit introuvable a la ligne ' . ($index + 1) . '.', 'status' => 422];
            }

            $unitPrice = (float) ($product['unit_price'] ?? 0);
            if ($unitPrice <= 0) {
                return ['error' => 'Prix serveur invalide a la ligne ' . ($index + 1) . '.', 'status' => 422];
            }

            $resolved[] = [
                'product_id' => (int) $product['product_id'],
                'qty' => (float) $line['qty'],
                'unit_price' => $unitPrice,
                'line_total' => (float) $line['qty'] * $unitPrice,
            ];
        }

        return ['items' => $resolved];
    }

    private function db(): \PDO
    {
        return $this->app->db();
    }
}
