<?php

namespace App\Controller;

use App\Service\OrderPricingService;
use App\Support\ApiResponse;
use App\Support\AppContext;
use App\Support\InputValidator;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Client-side API endpoints for the dashboard, shop, orders, invoices and profile.
 */
final class ClientApiController
{
    private const FACE_MATRIX_LENGTH = 1024;
    private const FACE_PROFILE_LABEL_MAX_LENGTH = 120;

    public function __construct(
        private readonly AppContext $app,
        private readonly InputValidator $validator,
        private readonly OrderPricingService $orderPricing,
    ) {
    }

    private function userId(): ?int
    {
        return $this->app->currentUserId();
    }

    private function requireAuth(): ?JsonResponse
    {
        if (!$this->userId()) {
            return ApiResponse::error('Non authentifie.', 401);
        }

        return null;
    }

    private function normalizeFaceMatrix(mixed $value): array
    {
        if (!is_array($value) || count($value) !== self::FACE_MATRIX_LENGTH) {
            throw new \RuntimeException('Matrice de visage invalide.');
        }

        $matrix = [];
        foreach ($value as $item) {
            if (!is_numeric($item)) {
                throw new \RuntimeException('Matrice de visage invalide.');
            }

            $number = (float) $item;
            if ($number < 0 || $number > 1) {
                throw new \RuntimeException('Matrice de visage hors limites.');
            }

            $matrix[] = round($number, 6);
        }

        return $matrix;
    }

    private function ensureFaceAuthProfilesTable(): void
    {
        $db = $this->app->db();
        $db->exec(
            'CREATE TABLE IF NOT EXISTS face_auth_profiles (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                user_id BIGINT UNSIGNED NOT NULL,
                profile_label VARCHAR(' . self::FACE_PROFILE_LABEL_MAX_LENGTH . ') NULL,
                face_matrix_json LONGTEXT NOT NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (id),
                KEY idx_face_auth_profiles_user_id (user_id),
                CONSTRAINT fk_face_auth_profiles_user
                    FOREIGN KEY (user_id) REFERENCES users(id)
                    ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci'
        );
    }

    private function fetchFaceProfileCount(int $userId): int
    {
        $this->ensureFaceAuthProfilesTable();

        $stmt = $this->app->db()->prepare(
            'SELECT COUNT(*) FROM face_auth_profiles WHERE user_id = :user_id'
        );
        $stmt->execute(['user_id' => $userId]);

        return (int) $stmt->fetchColumn();
    }

    private function userHasLegacyFaceMatrix(int $userId): bool
    {
        $stmt = $this->app->db()->prepare(
            'SELECT COUNT(*)
             FROM users
             WHERE id = :user_id
               AND matrice IS NOT NULL
               AND matrice <> ""'
        );
        $stmt->execute(['user_id' => $userId]);

        return (int) $stmt->fetchColumn() > 0;
    }

    private function faceState(int $userId): array
    {
        $faceProfilesCount = $this->fetchFaceProfileCount($userId);
        $hasLegacyMatrix = $this->userHasLegacyFaceMatrix($userId);
        $hasFace = $faceProfilesCount > 0 || $hasLegacyMatrix;

        return [
            'has_face_profile' => $hasFace,
            'can_add_face_profile' => !$hasFace,
            'can_replace_face_profile' => true,
            'face_profiles_count' => $faceProfilesCount,
        ];
    }

    private function canManageOrder(array $order): bool
    {
        $createdAt = strtotime((string) ($order['created_at'] ?? ''));
        if ($createdAt === false) {
            return false;
        }

        $lockedStatuses = ['EN_PREPARATION', 'EXPEDIEE', 'LIVREE', 'ANNULEE'];
        $status = (string) ($order['status'] ?? '');
        if (in_array($status, $lockedStatuses, true)) {
            return false;
        }

        return (time() - $createdAt) <= 86400;
    }

    private function reserveClientStock(\PDO $db, array $items, int $orderId): ?array
    {
        $selectStock = $db->prepare(
            'SELECT quantity_ml FROM stock WHERE product_id = :product_id LIMIT 1 FOR UPDATE'
        );
        $updateStock = $db->prepare(
            'UPDATE stock SET quantity_ml = quantity_ml - :qty WHERE product_id = :product_id'
        );
        $insertMovement = $db->prepare(
            "INSERT INTO stock_movements (product_id, movement_type, quantity_ml, reason, reference_type, reference_id, created_by)
             VALUES (:product_id, 'OUT', :qty, :reason, 'ORDER', :reference_id, :created_by)"
        );

        foreach ($items as $index => $item) {
            $selectStock->execute([
                'product_id' => $item['product_id'],
            ]);
            $stockQty = $selectStock->fetchColumn();
            if ($stockQty === false) {
                return ['error' => 'Stock introuvable a la ligne ' . ($index + 1) . '.', 'status' => 422];
            }

            if ((float) $stockQty < (float) $item['qty']) {
                return ['error' => 'Stock insuffisant a la ligne ' . ($index + 1) . '.', 'status' => 422];
            }

            $updateStock->execute([
                'product_id' => $item['product_id'],
                'qty' => $item['qty'],
            ]);
            $insertMovement->execute([
                'product_id' => $item['product_id'],
                'qty' => $item['qty'],
                'reason' => 'Sortie commande client',
                'reference_id' => $orderId,
                'created_by' => $this->userId(),
            ]);
        }

        return null;
    }

    private function restoreClientOrderStock(\PDO $db, int $orderId): void
    {
        $itemsStmt = $db->prepare(
            'SELECT product_id, quantity_ml FROM order_items WHERE order_id = :order_id ORDER BY id ASC'
        );
        $itemsStmt->execute([
            'order_id' => $orderId,
        ]);

        $updateStock = $db->prepare(
            'UPDATE stock SET quantity_ml = quantity_ml + :qty WHERE product_id = :product_id'
        );
        $insertMovement = $db->prepare(
            "INSERT INTO stock_movements (product_id, movement_type, quantity_ml, reason, reference_type, reference_id, created_by)
             VALUES (:product_id, 'RETURN', :qty, :reason, 'ORDER', :reference_id, :created_by)"
        );

        foreach ($itemsStmt->fetchAll() as $item) {
            $updateStock->execute([
                'product_id' => (int) $item['product_id'],
                'qty' => (float) $item['quantity_ml'],
            ]);
            $insertMovement->execute([
                'product_id' => (int) $item['product_id'],
                'qty' => (float) $item['quantity_ml'],
                'reason' => 'Restauration stock commande client',
                'reference_id' => $orderId,
                'created_by' => $this->userId(),
            ]);
        }
    }

    #[Route('/api/client/dashboard', name: 'api_client_dashboard', methods: ['GET'])]
    public function dashboard(): JsonResponse
    {
        if ($err = $this->requireAuth()) return $err;
        $db = $this->app->db();
        $uid = $this->userId();

        $stmt = $db->prepare("SELECT COUNT(*) AS cnt, COALESCE(SUM(total_dzd),0) AS total FROM orders WHERE customer_user_id = :uid");
        $stmt->execute(['uid' => $uid]);
        $orderStats = $stmt->fetch();

        $stmt2 = $db->prepare("SELECT COALESCE(SUM(i.total_dzd - COALESCE(paid.paid,0)),0) AS unpaid
            FROM invoices i
            INNER JOIN orders o ON o.id = i.order_id
            LEFT JOIN (SELECT invoice_id, SUM(CASE WHEN status='VALIDE' THEN amount_dzd ELSE 0 END) AS paid FROM payments GROUP BY invoice_id) paid ON paid.invoice_id = i.id
            WHERE o.customer_user_id = :uid AND i.status IN ('NON_PAYE','PARTIEL')");
        $stmt2->execute(['uid' => $uid]);
        $unpaid = (float) $stmt2->fetchColumn();

        $stmt3 = $db->prepare("SELECT COUNT(*) FROM invoices i INNER JOIN orders o ON o.id = i.order_id WHERE o.customer_user_id = :uid");
        $stmt3->execute(['uid' => $uid]);
        $totalInvoices = (int) $stmt3->fetchColumn();

        $stmt4 = $db->prepare("SELECT COALESCE(SUM(total_dzd),0) FROM orders WHERE customer_user_id = :uid AND YEAR(created_at)=YEAR(CURRENT_DATE) AND MONTH(created_at)=MONTH(CURRENT_DATE)");
        $stmt4->execute(['uid' => $uid]);
        $monthAmount = (float) $stmt4->fetchColumn();

        $stmt5 = $db->prepare("SELECT order_number, created_at FROM orders WHERE customer_user_id = :uid ORDER BY id DESC LIMIT 1");
        $stmt5->execute(['uid' => $uid]);
        $lastOrder = $stmt5->fetch();

        $stmt6 = $db->prepare("SELECT id, order_number, status, total_dzd, created_at FROM orders WHERE customer_user_id = :uid ORDER BY id DESC LIMIT 5");
        $stmt6->execute(['uid' => $uid]);
        $recentOrders = $stmt6->fetchAll();

        $stmt7 = $db->query(
            "SELECT
                pc.id,
                pc.name,
                pc.segment,
                pc.catalog_group,
                COALESCE(SUM(oi.quantity_ml), 0) AS sold_qty,
                COUNT(DISTINCT oi.order_id) AS orders_count
             FROM order_items oi
             INNER JOIN products p ON p.id = oi.product_id
             INNER JOIN perfume_catalog pc ON pc.id = p.perfume_catalog_id
             INNER JOIN orders o ON o.id = oi.order_id
             WHERE pc.is_active = 1
               AND p.is_active = 1
               AND o.status IN ('CONFIRMEE', 'EN_PREPARATION', 'EXPEDIEE', 'LIVREE')
             GROUP BY pc.id, pc.name, pc.segment, pc.catalog_group
             ORDER BY sold_qty DESC, orders_count DESC, pc.name ASC
             LIMIT 10"
        );
        $topPerfumes = $stmt7->fetchAll();

        return new JsonResponse([
            'success' => true,
            'stats' => [
                'total_orders' => (int) $orderStats['cnt'],
                'total_amount' => (float) $orderStats['total'],
                'unpaid_amount' => $unpaid,
                'total_invoices' => $totalInvoices,
                'month_amount' => $monthAmount,
                'last_order_number' => $lastOrder['order_number'] ?? null,
                'last_order_date' => $lastOrder['created_at'] ?? null,
            ],
            'recent_orders' => $recentOrders,
            'top_perfumes' => $topPerfumes,
        ]);
    }

    #[Route('/api/shop/products', name: 'api_shop_products', methods: ['GET'])]
    public function shopProducts(): JsonResponse
    {
        if ($err = $this->requireAuth()) return $err;
        $db = $this->app->db();

        $stmt = $db->query("SELECT
            pc.id,
            pc.catalog_group,
            pc.segment,
            pc.code,
            pc.name,
            p.id AS product_id,
            COALESCE(pp.price_dzd, pc.price_dzd, 0) AS price,
            COALESCE(s.quantity_ml, 0) AS stock_ml,
            COALESCE(s.min_alert_ml, 0) AS min_alert_ml
        FROM perfume_catalog pc
        INNER JOIN products p ON p.perfume_catalog_id = pc.id
        LEFT JOIN product_prices pp ON pp.product_id = p.id AND pp.sale_type = 'DETAIL' AND pp.ends_at IS NULL
        LEFT JOIN stock s ON s.product_id = p.id
        WHERE pc.is_active = 1 AND p.is_active = 1
        ORDER BY pc.catalog_group, pc.segment, pc.name");

        $products = [];
        foreach ($stmt->fetchAll() as $row) {
            $stockMl = (float) $row['stock_ml'];
            $alertMl = (float) $row['min_alert_ml'];
            $stockStatus = 'in_stock';
            if ($stockMl <= 0) {
                $stockStatus = 'out_of_stock';
            } elseif ($alertMl > 0 && $stockMl <= $alertMl) {
                $stockStatus = 'limited';
            }

            $products[] = [
                'id' => (int) $row['id'],
                'product_id' => (int) $row['product_id'],
                'name' => $row['name'],
                'code' => $row['code'],
                'catalog_group' => $row['catalog_group'],
                'segment' => $row['segment'],
                'price' => (float) $row['price'],
                'stock_ml' => $stockMl,
                'stock_status' => $stockStatus,
            ];
        }

        return new JsonResponse(['success' => true, 'products' => $products]);
    }

    #[Route('/api/client/orders', name: 'api_client_orders', methods: ['GET'])]
    public function clientOrders(): JsonResponse
    {
        if ($err = $this->requireAuth()) return $err;
        $db = $this->app->db();
        $uid = $this->userId();

        $stmt = $db->prepare("SELECT o.id, o.order_number, o.status, o.total_dzd, o.created_at,
            (SELECT COUNT(*) FROM order_items oi WHERE oi.order_id = o.id) AS items_count,
            i.id AS invoice_id
            FROM orders o
            LEFT JOIN invoices i ON i.order_id = o.id
            WHERE o.customer_user_id = :uid
            ORDER BY o.id DESC");
        $stmt->execute(['uid' => $uid]);

        $orders = array_map(function (array $row): array {
            $row['can_manage'] = $this->canManageOrder($row);
            $row['can_manage_until'] = date('Y-m-d H:i:s', strtotime((string) $row['created_at'] . ' +24 hours'));
            return $row;
        }, $stmt->fetchAll());

        return new JsonResponse(['success' => true, 'orders' => $orders]);
    }

    #[Route('/api/client/orders', name: 'api_client_orders_create', methods: ['POST'])]
    public function createClientOrder(Request $request): JsonResponse
    {
        if ($err = $this->requireAuth()) return $err;
        $db = $this->app->db();
        $uid = $this->userId();

        $payload = json_decode((string) $request->getContent(), true) ?: [];
        $items = $payload['items'] ?? [];
        $shipping = is_array($payload['shipping_address'] ?? null) ? $payload['shipping_address'] : [];

        if (!is_array($items) || count($items) === 0) {
            return ApiResponse::error('Ajoutez au moins un produit.', 422);
        }

        $shipLine1 = trim((string) ($shipping['line1'] ?? ''));
        $shipCity = trim((string) ($shipping['city'] ?? ''));
        $shipRegion = trim((string) ($shipping['region'] ?? ''));
        $shipCountry = trim((string) ($shipping['country'] ?? ''));
        $shipPhone = trim((string) ($shipping['phone'] ?? $shipping['line2'] ?? ''));
        $shipDeliveryAddress = trim((string) ($shipping['delivery_address'] ?? ''));

        $shipErrors = array_values(array_filter([
            $this->validator->required($shipLine1, 'Adresse', 180),
            $this->validator->required($shipCity, 'Ville', 120),
        ]));
        if ($shipErrors) {
            return ApiResponse::validation($shipErrors);
        }

        $normalizedItems = [];
        foreach ($items as $line) {
            if (!is_array($line)) {
                continue;
            }

            $qty = (float) ($line['qty'] ?? $line['quantity'] ?? 0);
            if ($qty <= 0 || floor($qty) !== $qty) {
                return ApiResponse::error('La quantite doit etre un nombre entier de bouteilles.', 422);
            }

            if (array_key_exists('perfume_id', $line)) {
                $normalizedItems[] = [
                    'perfume_id' => (int) $line['perfume_id'],
                    'qty' => $qty,
                ];
                continue;
            }

            if (array_key_exists('product_id', $line)) {
                $normalizedItems[] = [
                    'product_id' => (int) $line['product_id'],
                    'qty' => $qty,
                ];
            }
        }

        if ($normalizedItems === []) {
            return ApiResponse::error('Produit invalide a la ligne 1.', 422);
        }

        $useProductId = $normalizedItems !== [] && array_key_exists('product_id', $normalizedItems[0]);
        $resolvedItems = $useProductId
            ? $this->orderPricing->resolveAdminItems($normalizedItems)
            : $this->orderPricing->resolveClientItems($normalizedItems);
        if (isset($resolvedItems['error'])) {
            return ApiResponse::error($resolvedItems['error'], (int) ($resolvedItems['status'] ?? 422));
        }

        $db->beginTransaction();
        try {
            $orderNumber = sprintf('CMD-%s-%04d', date('Y'), random_int(1000, 9999));
            $invoiceNumber = sprintf('FAC-%s-%04d', date('Y'), random_int(1000, 9999));
            $total = $this->orderPricing->total($resolvedItems['items']);

            $user = $this->app->fetchUserWithRoleById((int) $uid);

            $notes = json_encode([
                'last_name' => (string) ($user['last_name'] ?? ''),
                'first_name' => (string) ($user['first_name'] ?? ''),
                'phone' => (string) ($user['phone'] ?? ''),
                'shop' => (string) ($user['perfume_shop_name'] ?? ''),
                'created_by_admin' => false,
                'shipping_address' => [
                    'line1' => $shipLine1,
                    'phone' => $shipPhone,
                    'city' => $shipCity,
                    'region' => $shipRegion,
                    'country' => $shipCountry,
                    'delivery_address' => $shipDeliveryAddress,
                ],
            ], JSON_UNESCAPED_UNICODE);

            $stmtOrder = $db->prepare(
                "INSERT INTO orders (order_number, customer_user_id, sale_type, status, notes, subtotal_dzd, total_dzd, created_by)
                 VALUES (:n, :uid, 'DETAIL', 'CONFIRMEE', :notes, :sub, :total, :created_by)"
            );
            $stmtOrder->execute([
                'n' => $orderNumber,
                'uid' => $uid,
                'notes' => $notes,
                'sub' => $total,
                'total' => $total,
                'created_by' => $uid,
            ]);
            $orderId = (int) $db->lastInsertId();

            $stmtItem = $db->prepare(
                "INSERT INTO order_items (order_id, product_id, quantity_ml, unit_price_dzd, line_total_dzd)
                 VALUES (:oid, :prid, :qty, :price, :line)"
            );
            foreach ($resolvedItems['items'] as $line) {
                $stmtItem->execute([
                    'oid' => $orderId,
                    'prid' => $line['product_id'],
                    'qty' => $line['qty'],
                    'price' => $line['unit_price'],
                    'line' => $line['line_total'],
                ]);
            }

            $stockError = $this->reserveClientStock($db, $resolvedItems['items'], $orderId);
            if ($stockError !== null) {
                $db->rollBack();

                return ApiResponse::error($stockError['error'], $stockError['status']);
            }

            $stmtInvoice = $db->prepare(
                "INSERT INTO invoices (invoice_number, order_id, status, subtotal_dzd, total_dzd)
                 VALUES (:inv, :oid, 'NON_PAYE', :sub, :total)"
            );
            $stmtInvoice->execute([
                'inv' => $invoiceNumber,
                'oid' => $orderId,
                'sub' => $total,
                'total' => $total,
            ]);

            $db->commit();

            return new JsonResponse([
                'success' => true,
                'order_id' => $orderId,
                'order_number' => $orderNumber,
                'invoice_number' => $invoiceNumber,
            ], 201);
        } catch (\Throwable) {
            $db->rollBack();
            return ApiResponse::error('Impossible de valider la commande.', 500);
        }
    }

    #[Route('/api/client/orders/{id}', name: 'api_client_order_detail', methods: ['GET'], requirements: ['id' => '\d+'])]
    public function clientOrderDetail(int $id): JsonResponse
    {
        if ($err = $this->requireAuth()) return $err;
        $db = $this->app->db();
        $uid = $this->userId();

        $stmt = $db->prepare("SELECT * FROM orders WHERE id = :id AND customer_user_id = :uid");
        $stmt->execute(['id' => $id, 'uid' => $uid]);
        $order = $stmt->fetch();
        if (!$order) return ApiResponse::error('Commande introuvable.', 404);

        $itemsStmt = $db->prepare("SELECT oi.*, pc.id AS perfume_id, pc.name AS product_name, pc.segment, pc.catalog_group
            FROM order_items oi
            INNER JOIN products p ON p.id = oi.product_id
            INNER JOIN perfume_catalog pc ON pc.id = p.perfume_catalog_id
            WHERE oi.order_id = :oid ORDER BY oi.id");
        $itemsStmt->execute(['oid' => $id]);

        $notes = json_decode((string) ($order['notes'] ?? ''), true);

        return new JsonResponse([
            'success' => true,
            'order' => $order,
            'can_manage' => $this->canManageOrder($order),
            'shipping_address' => is_array($notes['shipping_address'] ?? null) ? $notes['shipping_address'] : null,
            'items' => $itemsStmt->fetchAll(),
        ]);
    }

    #[Route('/api/client/orders/{id}', name: 'api_client_order_update', methods: ['PATCH'], requirements: ['id' => '\d+'])]
    public function updateClientOrder(int $id, Request $request): JsonResponse
    {
        if ($err = $this->requireAuth()) return $err;
        $db = $this->app->db();
        $uid = $this->userId();

        $orderStmt = $db->prepare("SELECT * FROM orders WHERE id = :id AND customer_user_id = :uid");
        $orderStmt->execute(['id' => $id, 'uid' => $uid]);
        $order = $orderStmt->fetch();
        if (!$order) {
            return ApiResponse::error('Commande introuvable.', 404);
        }
        if (!$this->canManageOrder($order)) {
            return ApiResponse::error('Cette commande ne peut plus etre modifiee apres 24h ou une fois validee.', 403);
        }

        $payload = json_decode((string) $request->getContent(), true) ?: [];
        $items = $payload['items'] ?? [];
        $shipping = is_array($payload['shipping_address'] ?? null) ? $payload['shipping_address'] : [];

        if (!is_array($items) || count($items) === 0) {
            return ApiResponse::error('Ajoutez au moins un produit.', 422);
        }

        $shipLine1 = trim((string) ($shipping['line1'] ?? ''));
        $shipCity = trim((string) ($shipping['city'] ?? ''));
        $shipRegion = trim((string) ($shipping['region'] ?? ''));
        $shipCountry = trim((string) ($shipping['country'] ?? ''));
        $shipPhone = trim((string) ($shipping['phone'] ?? $shipping['line2'] ?? ''));
        $shipDeliveryAddress = trim((string) ($shipping['delivery_address'] ?? ''));

        $shipErrors = array_values(array_filter([
            $this->validator->required($shipLine1, 'Adresse', 180),
            $this->validator->required($shipCity, 'Ville', 120),
        ]));
        if ($shipErrors) {
            return ApiResponse::validation($shipErrors);
        }

        $normalizedItems = [];
        foreach ($items as $line) {
            if (!is_array($line)) {
                continue;
            }

            $qty = (float) ($line['qty'] ?? $line['quantity'] ?? 0);
            if ($qty <= 0 || floor($qty) !== $qty) {
                return ApiResponse::error('La quantite doit etre un nombre entier de bouteilles.', 422);
            }

            if (array_key_exists('product_id', $line)) {
                $normalizedItems[] = [
                    'product_id' => (int) $line['product_id'],
                    'qty' => $qty,
                ];
                continue;
            }

            if (array_key_exists('perfume_id', $line)) {
                $normalizedItems[] = [
                    'perfume_id' => (int) $line['perfume_id'],
                    'qty' => $qty,
                ];
            }
        }

        if ($normalizedItems === []) {
            return ApiResponse::error('Produit invalide a la ligne 1.', 422);
        }

        $useProductId = $normalizedItems !== [] && array_key_exists('product_id', $normalizedItems[0]);
        $resolvedItems = $useProductId
            ? $this->orderPricing->resolveAdminItems($normalizedItems)
            : $this->orderPricing->resolveClientItems($normalizedItems);
        if (isset($resolvedItems['error'])) {
            return ApiResponse::error($resolvedItems['error'], (int) ($resolvedItems['status'] ?? 422));
        }

        $total = $this->orderPricing->total($resolvedItems['items']);
        $notes = json_decode((string) ($order['notes'] ?? ''), true);
        if (!is_array($notes)) {
            $notes = [];
        }
        $notes['shipping_address'] = [
            'line1' => $shipLine1,
            'phone' => $shipPhone,
            'city' => $shipCity,
            'region' => $shipRegion,
            'country' => $shipCountry,
            'delivery_address' => $shipDeliveryAddress,
        ];

        $db->beginTransaction();
        try {
            $this->restoreClientOrderStock($db, $id);
            $db->prepare("DELETE FROM order_items WHERE order_id = :oid")->execute(['oid' => $id]);

            $stmtItem = $db->prepare(
                "INSERT INTO order_items (order_id, product_id, quantity_ml, unit_price_dzd, line_total_dzd)
                 VALUES (:oid, :prid, :qty, :price, :line)"
            );
            foreach ($resolvedItems['items'] as $line) {
                $stmtItem->execute([
                    'oid' => $id,
                    'prid' => $line['product_id'],
                    'qty' => $line['qty'],
                    'price' => $line['unit_price'],
                    'line' => $line['line_total'],
                ]);
            }

            $stockError = $this->reserveClientStock($db, $resolvedItems['items'], $id);
            if ($stockError !== null) {
                $db->rollBack();

                return ApiResponse::error($stockError['error'], $stockError['status']);
            }

            $db->prepare(
                "UPDATE orders SET notes = :notes, subtotal_dzd = :sub, total_dzd = :total
                 WHERE id = :id AND customer_user_id = :uid"
            )->execute([
                'notes' => json_encode($notes, JSON_UNESCAPED_UNICODE),
                'sub' => $total,
                'total' => $total,
                'id' => $id,
                'uid' => $uid,
            ]);

            $db->prepare(
                "UPDATE invoices SET subtotal_dzd = :sub, total_dzd = :total WHERE order_id = :oid"
            )->execute([
                'sub' => $total,
                'total' => $total,
                'oid' => $id,
            ]);

            $db->commit();
            return ApiResponse::ok(['message' => 'Commande modifiee avec succes.']);
        } catch (\Throwable) {
            $db->rollBack();
            return ApiResponse::error('Impossible de modifier la commande.', 500);
        }
    }

    #[Route('/api/client/orders/{id}', name: 'api_client_order_delete', methods: ['DELETE'], requirements: ['id' => '\d+'])]
    public function deleteClientOrder(int $id): JsonResponse
    {
        if ($err = $this->requireAuth()) return $err;
        $db = $this->app->db();
        $uid = $this->userId();

        $orderStmt = $db->prepare("SELECT * FROM orders WHERE id = :id AND customer_user_id = :uid");
        $orderStmt->execute(['id' => $id, 'uid' => $uid]);
        $order = $orderStmt->fetch();
        if (!$order) {
            return ApiResponse::error('Commande introuvable.', 404);
        }
        if (!$this->canManageOrder($order)) {
            return ApiResponse::error('Cette commande ne peut plus etre supprimee apres 24h ou une fois validee.', 403);
        }

        $db->beginTransaction();
        try {
            $this->restoreClientOrderStock($db, $id);
            $invoiceIdsStmt = $db->prepare("SELECT id FROM invoices WHERE order_id = :oid");
            $invoiceIdsStmt->execute(['oid' => $id]);
            $invoiceIds = array_map(static fn(array $row): int => (int) $row['id'], $invoiceIdsStmt->fetchAll());

            foreach ($invoiceIds as $invoiceId) {
                $db->prepare("DELETE FROM payments WHERE invoice_id = :iid")->execute(['iid' => $invoiceId]);
            }

            $db->prepare("DELETE FROM invoices WHERE order_id = :oid")->execute(['oid' => $id]);
            $db->prepare("DELETE FROM order_items WHERE order_id = :oid")->execute(['oid' => $id]);
            $db->prepare("DELETE FROM orders WHERE id = :id AND customer_user_id = :uid")->execute(['id' => $id, 'uid' => $uid]);

            $db->commit();
            return ApiResponse::ok(['message' => 'Commande supprimee avec succes.']);
        } catch (\Throwable) {
            $db->rollBack();
            return ApiResponse::error('Impossible de supprimer la commande.', 500);
        }
    }

    #[Route('/api/client/invoices', name: 'api_client_invoices', methods: ['GET'])]
    public function clientInvoices(): JsonResponse
    {
        if ($err = $this->requireAuth()) return $err;
        $db = $this->app->db();
        $uid = $this->userId();

        $stmt = $db->prepare("SELECT i.id, i.invoice_number, i.status, i.subtotal_dzd, i.tax_dzd, i.total_dzd,
            i.due_date, i.issued_at, o.order_number,
            TRIM(CONCAT(COALESCE(u.first_name, ''), ' ', COALESCE(u.last_name, ''))) AS customer_name,
            COALESCE(NULLIF(u.perfume_shop_name, ''), TRIM(CONCAT(COALESCE(u.first_name, ''), ' ', COALESCE(u.last_name, '')))) AS customer_display_name,
            COALESCE(SUM(CASE WHEN p.status = 'VALIDE' THEN p.amount_dzd ELSE 0 END), 0) AS paid_amount
            FROM invoices i
            INNER JOIN orders o ON o.id = i.order_id
            INNER JOIN users u ON u.id = o.customer_user_id
            LEFT JOIN payments p ON p.invoice_id = i.id
            WHERE o.customer_user_id = :uid
            GROUP BY i.id, i.invoice_number, i.status, i.subtotal_dzd, i.tax_dzd, i.total_dzd,
                     i.due_date, i.issued_at, o.order_number, u.first_name, u.last_name, u.perfume_shop_name
            ORDER BY i.id DESC");
        $stmt->execute(['uid' => $uid]);

        $invoices = $stmt->fetchAll();
        foreach ($invoices as &$invoice) {
            $invoice['remaining_amount'] = max(0, (float) $invoice['total_dzd'] - (float) $invoice['paid_amount']);
            $invoice['payment_status'] = $invoice['status'];
        }
        unset($invoice);

        return new JsonResponse(['success' => true, 'invoices' => $invoices]);
    }

    #[Route('/api/client/profile', name: 'api_client_profile_update', methods: ['PATCH'])]
    public function updateProfile(Request $request): JsonResponse
    {
        if ($err = $this->requireAuth()) return $err;
        $db = $this->app->db();
        $uid = $this->userId();

        $data = json_decode($request->getContent(), true) ?? [];
        $firstName = trim($data['first_name'] ?? '');
        $lastName = trim($data['last_name'] ?? '');
        $shopName = trim($data['shop_name'] ?? '');
        $phone = trim($data['phone'] ?? '');
        $email = mb_strtolower(trim((string) ($data['email'] ?? '')));
        $location = trim((string) ($data['location'] ?? ''));

        $errors = [
            $this->validator->name($firstName, 'Prenom'),
            $this->validator->name($lastName, 'Nom'),
            $this->validator->phone($phone, false),
            $this->validator->email($email),
            $location === '' ? 'Localisation obligatoire.' : null,
        ];
        $errors = array_values(array_filter($errors));
        if ($errors) return ApiResponse::validation($errors);

        $check = $db->prepare(
            'SELECT id FROM users
             WHERE (email = :email OR phone = :phone) AND id <> :id
             LIMIT 1'
        );
        $check->execute(['email' => $email, 'phone' => $phone, 'id' => $uid]);
        if ($check->fetch()) {
            return ApiResponse::error('Email ou telephone deja utilise.', 409);
        }

        $stmt = $db->prepare("UPDATE users SET first_name = :fn, last_name = :ln, perfume_shop_name = :sn, phone = :ph, email = :email, location = :location WHERE id = :uid");
        $stmt->execute(['fn' => $firstName, 'ln' => $lastName, 'sn' => $shopName, 'ph' => $phone, 'email' => $email, 'location' => $location, 'uid' => $uid]);

        return ApiResponse::ok(['message' => 'Profil mis a jour.']);
    }

    #[Route('/api/client/account/face-status', name: 'api_client_face_status', methods: ['GET'])]
    public function faceStatus(): JsonResponse
    {
        if ($err = $this->requireAuth()) return $err;

        return ApiResponse::ok($this->faceState((int) $this->userId()));
    }

    #[Route('/api/client/account/face', name: 'api_client_face_create', methods: ['POST'])]
    public function createFaceProfile(Request $request): JsonResponse
    {
        if ($err = $this->requireAuth()) return $err;

        $userId = (int) $this->userId();
        $payload = json_decode((string) $request->getContent(), true) ?: [];
        $password = (string) ($payload['password'] ?? '');

        if ($password === '') {
            return ApiResponse::error('Mot de passe obligatoire.', 422);
        }

        try {
            $matrix = $this->normalizeFaceMatrix($payload['matrix'] ?? null);
        } catch (\RuntimeException $e) {
            return ApiResponse::error($e->getMessage(), 422);
        }

        $stmt = $this->app->db()->prepare(
            'SELECT password_hash
             FROM users
             WHERE id = :user_id
             LIMIT 1'
        );
        $stmt->execute(['user_id' => $userId]);
        $passwordHash = $stmt->fetchColumn();

        if (!$passwordHash || !password_verify($password, (string) $passwordHash)) {
            return ApiResponse::error('Mot de passe incorrect.', 422);
        }

        $db = $this->app->db();
        $this->ensureFaceAuthProfilesTable();

        $db->beginTransaction();
        try {
            $deleteProfiles = $db->prepare(
                'DELETE FROM face_auth_profiles
                 WHERE user_id = :user_id'
            );
            $deleteProfiles->execute([
                'user_id' => $userId,
            ]);

            $insert = $db->prepare(
                'INSERT INTO face_auth_profiles (user_id, profile_label, face_matrix_json)
                 VALUES (:user_id, :profile_label, :face_matrix_json)'
            );
            $insert->execute([
                'user_id' => $userId,
                'profile_label' => 'Profil principal',
                'face_matrix_json' => json_encode($matrix, JSON_THROW_ON_ERROR),
            ]);

            $updateLegacy = $db->prepare(
                'UPDATE users
                 SET matrice = :matrice
                 WHERE id = :user_id'
            );
            $updateLegacy->execute([
                'matrice' => json_encode($matrix, JSON_THROW_ON_ERROR),
                'user_id' => $userId,
            ]);

            $db->commit();
        } catch (\Throwable) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }

            return ApiResponse::error('Impossible d enregistrer le visage.', 500);
        }

        return ApiResponse::ok([
            'message' => 'Visage ajoute avec succes.',
        ] + $this->faceState($userId));
    }

    #[Route('/api/client/password', name: 'api_client_password', methods: ['PATCH'])]
    public function changePassword(Request $request): JsonResponse
    {
        if ($err = $this->requireAuth()) return $err;
        $db = $this->app->db();
        $uid = $this->userId();

        $data = json_decode($request->getContent(), true) ?? [];
        $current = $data['current_password'] ?? '';
        $newPwd = $data['new_password'] ?? '';

        if ($current === '' || $newPwd === '') {
            return ApiResponse::error('Tous les champs sont obligatoires.', 422);
        }
        if (mb_strlen($newPwd) < 8) {
            return ApiResponse::error('Le mot de passe doit contenir au moins 8 caracteres.', 422);
        }

        $stmt = $db->prepare("SELECT password_hash FROM users WHERE id = :uid");
        $stmt->execute(['uid' => $uid]);
        $hash = $stmt->fetchColumn();

        if (!$hash || !password_verify($current, $hash)) {
            return ApiResponse::error('Mot de passe actuel incorrect.', 422);
        }

        $newHash = password_hash($newPwd, PASSWORD_BCRYPT);
        $db->prepare("UPDATE users SET password_hash = :hash WHERE id = :uid")->execute(['hash' => $newHash, 'uid' => $uid]);

        return ApiResponse::ok(['message' => 'Mot de passe mis a jour.']);
    }
}
