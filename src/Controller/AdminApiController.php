<?php

namespace App\Controller;

use App\Service\OrderPricingService;
use App\Support\AppContext;
use App\Support\InputValidator;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class AdminApiController
{
    private const ALLOWED_PRODUCT_GROUPS = ['PRINCIPAL', 'SMART', 'ENFANT', 'LUXE', 'MIXTE', 'AUTRE'];
    private const ALLOWED_PRODUCT_SEGMENTS = ['HOMME', 'FEMME', 'UNISEX', 'ENFANT', 'MIXTE', 'AUTRE'];
    private const FACE_MATRIX_LENGTH = 1024;
    private const FACE_PROFILE_LABEL_MAX_LENGTH = 120;

    public function __construct(
        private readonly AppContext $app,
        private readonly InputValidator $validator,
        private readonly OrderPricingService $orderPricing,
    )
    {
    }

    private function denyUnlessAdmin(): ?JsonResponse
    {
        if (!$this->app->currentUserId()) {
            return new JsonResponse(['error' => 'Non authentifie.'], 401);
        }

        if (!$this->app->isAdminSession()) {
            return new JsonResponse(['error' => 'Acces admin requis.'], 403);
        }

        return null;
    }

    private function validateRequired(string $value, string $label, int $max = 255): ?string
    {
        return $this->validator->required($value, $label, $max);
    }

    private function validatePhone(string $value): ?string
    {
        return $this->validator->phone($value, false);
    }

    private function validateEmail(string $value): ?string
    {
        return $this->validator->email($value, false);
    }

    private function validateClientField(string $value, string $label, int $max = 150): ?string
    {
        if ($value === '') {
            return $label . ' obligatoire.';
        }
        if (mb_strlen($value) > $max) {
            return $label . ' trop long.';
        }

        return null;
    }

    private function validateDate(string $value, string $label): ?string
    {
        return $this->validator->date($value, $label);
    }

    private function validateAllowedValue(string $value, array $allowed, string $message): ?string
    {
        return $this->validator->allowedValue($value, $allowed, $message);
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

        $schema = (string) $db->query('SELECT DATABASE()')->fetchColumn();
        if ($schema === '') {
            return;
        }

        $columnCheck = $db->prepare(
            'SELECT COUNT(*)
             FROM INFORMATION_SCHEMA.COLUMNS
             WHERE TABLE_SCHEMA = :schema
               AND TABLE_NAME = "face_auth_profiles"
               AND COLUMN_NAME = "profile_label"'
        );
        $columnCheck->execute(['schema' => $schema]);
        if ((int) $columnCheck->fetchColumn() === 0) {
            $db->exec(
                'ALTER TABLE face_auth_profiles
                 ADD COLUMN profile_label VARCHAR(' . self::FACE_PROFILE_LABEL_MAX_LENGTH . ') NULL AFTER user_id'
            );
        }

        $legacyUniqueCheck = $db->prepare(
            'SELECT COUNT(*)
             FROM INFORMATION_SCHEMA.STATISTICS
             WHERE TABLE_SCHEMA = :schema
               AND TABLE_NAME = "face_auth_profiles"
               AND INDEX_NAME = "uniq_face_auth_user_id"'
        );
        $legacyUniqueCheck->execute(['schema' => $schema]);
        if ((int) $legacyUniqueCheck->fetchColumn() > 0) {
            $db->exec('ALTER TABLE face_auth_profiles DROP INDEX uniq_face_auth_user_id');
        }

        $indexCheck = $db->prepare(
            'SELECT COUNT(*)
             FROM INFORMATION_SCHEMA.STATISTICS
             WHERE TABLE_SCHEMA = :schema
               AND TABLE_NAME = "face_auth_profiles"
               AND INDEX_NAME = "idx_face_auth_profiles_user_id"'
        );
        $indexCheck->execute(['schema' => $schema]);
        if ((int) $indexCheck->fetchColumn() === 0) {
            $db->exec('ALTER TABLE face_auth_profiles ADD INDEX idx_face_auth_profiles_user_id (user_id)');
        }
    }

    private function migrateLegacyMatrixToFaceProfiles(int $userId): void
    {
        $this->ensureFaceAuthProfilesTable();

        $db = $this->app->db();
        $countStmt = $db->prepare(
            'SELECT COUNT(*)
             FROM face_auth_profiles
             WHERE user_id = :user_id'
        );
        $countStmt->execute(['user_id' => $userId]);
        if ((int) $countStmt->fetchColumn() > 0) {
            return;
        }

        $legacyStmt = $db->prepare(
            'SELECT matrice
             FROM users
             WHERE id = :user_id
               AND matrice IS NOT NULL
               AND matrice <> ""
             LIMIT 1'
        );
        $legacyStmt->execute(['user_id' => $userId]);
        $legacyMatrix = $legacyStmt->fetchColumn();
        if ($legacyMatrix === false || $legacyMatrix === null || $legacyMatrix === '') {
            return;
        }

        $insert = $db->prepare(
            'INSERT INTO face_auth_profiles (user_id, profile_label, face_matrix_json)
             VALUES (:user_id, :profile_label, :face_matrix_json)'
        );
        $insert->execute([
            'user_id' => $userId,
            'profile_label' => 'Profil principal',
            'face_matrix_json' => (string) $legacyMatrix,
        ]);
    }

    private function syncLegacyMatrixFromFaceProfiles(int $userId): void
    {
        $this->ensureFaceAuthProfilesTable();

        $db = $this->app->db();
        $stmt = $db->prepare(
            'SELECT face_matrix_json
             FROM face_auth_profiles
             WHERE user_id = :user_id
             ORDER BY id ASC
             LIMIT 1'
        );
        $stmt->execute(['user_id' => $userId]);
        $matrixJson = $stmt->fetchColumn();

        $update = $db->prepare(
            'UPDATE users
             SET matrice = :matrice
             WHERE id = :user_id'
        );
        $update->execute([
            'matrice' => $matrixJson !== false ? (string) $matrixJson : null,
            'user_id' => $userId,
        ]);
    }

    private function fetchAccountFaceProfiles(int $userId): array
    {
        $this->migrateLegacyMatrixToFaceProfiles($userId);

        $stmt = $this->app->db()->prepare(
            'SELECT id, profile_label, created_at, updated_at
             FROM face_auth_profiles
             WHERE user_id = :user_id
             ORDER BY id DESC'
        );
        $stmt->execute(['user_id' => $userId]);

        return $stmt->fetchAll();
    }

    private function faceProfilesResponse(int $userId): array
    {
        $user = $this->app->fetchUserWithRoleById($userId);

        return [
            'ok' => true,
            'user' => $user ? [
                'id' => (int) $user['id'],
                'first_name' => $user['first_name'],
                'last_name' => $user['last_name'],
                'perfume_shop_name' => $user['perfume_shop_name'],
                'phone' => $user['phone'],
                'location' => $user['location'],
                'email' => $user['email'],
                'role_name' => $user['role_name'],
            ] : null,
            'face_profiles' => $this->fetchAccountFaceProfiles($userId),
        ];
    }

    private function reserveStock(\PDO $db, array $items, int $orderId): ?array
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
                return ['error' => 'Stock introuvable a la ligne ' . ($index + 1) . '.'];
            }

            if ((float) $stockQty < (float) $item['qty']) {
                return ['error' => 'Stock insuffisant a la ligne ' . ($index + 1) . '.'];
            }

            $updateStock->execute([
                'product_id' => $item['product_id'],
                'qty' => $item['qty'],
            ]);
            $insertMovement->execute([
                'product_id' => $item['product_id'],
                'qty' => $item['qty'],
                'reason' => 'Sortie commande admin',
                'reference_id' => $orderId,
                'created_by' => $this->app->currentUserId(),
            ]);
        }

        return null;
    }

    private function restoreOrderStock(\PDO $db, int $orderId): void
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
                'reason' => 'Restauration stock suppression commande admin',
                'reference_id' => $orderId,
                'created_by' => $this->app->currentUserId(),
            ]);
        }
    }

    private function syncInvoiceStatus(\PDO $db, int $invoiceId): string
    {
        $stmt = $db->prepare(
            "SELECT
                i.total_dzd,
                COALESCE(SUM(CASE WHEN p.status = 'VALIDE' THEN p.amount_dzd ELSE 0 END), 0) AS paid_amount
             FROM invoices i
             LEFT JOIN payments p ON p.invoice_id = i.id
             WHERE i.id = :id
             GROUP BY i.id, i.total_dzd
             LIMIT 1"
        );
        $stmt->execute([
            'id' => $invoiceId,
        ]);
        $invoice = $stmt->fetch();
        if (!$invoice) {
            throw new \RuntimeException('Facture introuvable.');
        }

        $total = (float) $invoice['total_dzd'];
        $paid = (float) $invoice['paid_amount'];
        $status = 'NON_PAYE';

        if ($paid > 0 && $paid < $total) {
            $status = 'PARTIEL';
        } elseif ($paid >= $total) {
            $status = 'PAYE';
        }

        $db->prepare('UPDATE invoices SET status = :status WHERE id = :id')->execute([
            'status' => $status,
            'id' => $invoiceId,
        ]);

        return $status;
    }

    private function invoicePaymentSnapshot(\PDO $db, int $invoiceId): array
    {
        $stmt = $db->prepare(
            "SELECT
                i.total_dzd,
                COALESCE(SUM(CASE WHEN p.status = 'VALIDE' THEN p.amount_dzd ELSE 0 END), 0) AS paid_amount
             FROM invoices i
             LEFT JOIN payments p ON p.invoice_id = i.id
             WHERE i.id = :id
             GROUP BY i.id, i.total_dzd
             LIMIT 1"
        );
        $stmt->execute(['id' => $invoiceId]);
        $invoice = $stmt->fetch();
        if (!$invoice) {
            throw new \RuntimeException('Facture introuvable.');
        }

        $total = (float) $invoice['total_dzd'];
        $paid = (float) $invoice['paid_amount'];

        return [
            'total_amount' => $total,
            'paid_amount' => $paid,
            'remaining_amount' => max(0, $total - $paid),
        ];
    }

    #[Route('/api/admin/summary', name: 'api_admin_summary', methods: ['GET'])]
    public function summary(): JsonResponse
    {
        if ($deny = $this->denyUnlessAdmin()) {
            return $deny;
        }

        $db = $this->app->db();
        $todayRevenue = (float) $db->query(
            "SELECT COALESCE(SUM(amount_dzd), 0) FROM payments
             WHERE status = 'VALIDE' AND DATE(paid_at) = CURDATE()"
        )->fetchColumn();

        $monthRevenue = (float) $db->query(
            "SELECT COALESCE(SUM(amount_dzd), 0) FROM payments
             WHERE status = 'VALIDE'
               AND YEAR(paid_at) = YEAR(CURDATE())
               AND MONTH(paid_at) = MONTH(CURDATE())"
        )->fetchColumn();

        $monthBusinessExpenses = (float) $db->query(
            "SELECT COALESCE(SUM(amount_dzd), 0) FROM business_expenses
             WHERE YEAR(expense_date) = YEAR(CURDATE())
               AND MONTH(expense_date) = MONTH(CURDATE())"
        )->fetchColumn();

        $monthRawMaterialExpenses = (float) $db->query(
            "SELECT COALESCE(SUM(quantity_in_stock * unit_cost_dzd), 0) FROM raw_material_inventory
             WHERE YEAR(purchase_date) = YEAR(CURDATE())
               AND MONTH(purchase_date) = MONTH(CURDATE())"
        )->fetchColumn();

        $monthPayroll = (float) $db->query(
            "SELECT COALESCE(SUM(salary_dzd), 0) FROM employees
             WHERE employment_status = 'ACTIF'"
        )->fetchColumn();

        $stockValue = (float) $db->query(
            "SELECT COALESCE(SUM(s.quantity_ml * COALESCE(pc.price_dzd, 0)), 0)
             FROM stock s
             INNER JOIN products p ON p.id = s.product_id
             INNER JOIN perfume_catalog pc ON pc.id = p.perfume_catalog_id
             WHERE p.is_active = 1 AND pc.is_active = 1"
        )->fetchColumn();

        $rawMaterialsStockValue = (float) $db->query(
            "SELECT COALESCE(SUM(quantity_in_stock * unit_cost_dzd), 0)
             FROM raw_material_inventory"
        )->fetchColumn();

        $productsCount = (int) $db->query("SELECT COUNT(*) FROM products WHERE is_active = 1")->fetchColumn();
        $outOfStockCount = (int) $db->query(
            "SELECT COUNT(*) FROM stock s
             INNER JOIN products p ON p.id = s.product_id
             WHERE p.is_active = 1 AND s.quantity_ml <= 0"
        )->fetchColumn();
        $employeesCount = (int) $db->query(
            "SELECT COUNT(*) FROM employees WHERE employment_status = 'ACTIF'"
        )->fetchColumn();
        $pendingOrders = (int) $db->query(
            "SELECT COUNT(*) FROM orders
             WHERE status IN ('CONFIRMEE', 'EN_PREPARATION', 'EXPEDIEE')"
        )->fetchColumn();
        $rawMaterialsCount = (int) $db->query("SELECT COUNT(*) FROM raw_material_inventory")->fetchColumn();
        $rawMaterialsAlertCount = (int) $db->query(
            "SELECT COUNT(*)
             FROM raw_material_inventory
             WHERE quantity_in_stock <= min_alert_quantity"
        )->fetchColumn();

        $monthExpenses = $monthBusinessExpenses + $monthRawMaterialExpenses;
        $monthProfit = $monthRevenue - $monthExpenses - $monthPayroll;

        $recentOrders = $db->query(
            "SELECT
                o.id,
                o.order_number,
                o.status,
                o.total_dzd,
                o.created_at,
                i.invoice_number,
                i.status AS invoice_status,
                u.perfume_shop_name
             FROM orders o
             INNER JOIN users u ON u.id = o.customer_user_id
             LEFT JOIN invoices i ON i.order_id = o.id
             ORDER BY o.id DESC
             LIMIT 8"
        )->fetchAll();

        $recentExpenses = $db->query(
            "SELECT *
             FROM (
                SELECT
                    id,
                    expense_type,
                    label,
                    amount_dzd,
                    expense_date
                FROM business_expenses
                UNION ALL
                SELECT
                    id,
                    'RAW_MATERIAL_STOCK' AS expense_type,
                    CONCAT(item_name, ' (', material_category, ')') AS label,
                    quantity_in_stock * unit_cost_dzd AS amount_dzd,
                    purchase_date AS expense_date
                FROM raw_material_inventory
             ) expense_rows
             ORDER BY expense_date DESC, id DESC
             LIMIT 8"
        )->fetchAll();

        $activityRows = $db->query(
            "SELECT
                DATE(o.created_at) AS activity_day,
                COUNT(*) AS orders_count,
                COALESCE(SUM(o.total_dzd), 0) AS orders_total
             FROM orders o
             WHERE o.created_at >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
             GROUP BY DATE(o.created_at)
             ORDER BY activity_day ASC"
        )->fetchAll();

        return new JsonResponse([
            'cards' => [
                'today_revenue' => $todayRevenue,
                'month_revenue' => $monthRevenue,
                'month_expenses' => $monthExpenses,
                'month_raw_material_expenses' => $monthRawMaterialExpenses,
                'month_payroll' => $monthPayroll,
                'month_profit' => $monthProfit,
                'stock_value' => $stockValue,
                'raw_materials_stock_value' => $rawMaterialsStockValue,
                'products_count' => $productsCount,
                'out_of_stock_count' => $outOfStockCount,
                'employees_count' => $employeesCount,
                'pending_orders' => $pendingOrders,
                'raw_materials_count' => $rawMaterialsCount,
                'raw_materials_alert_count' => $rawMaterialsAlertCount,
            ],
            'recent_orders' => $recentOrders,
            'recent_expenses' => $recentExpenses,
            'activity' => $activityRows,
        ]);
    }

    #[Route('/api/admin/products', name: 'api_admin_products', methods: ['GET'])]
    public function products(): JsonResponse
    {
        if ($deny = $this->denyUnlessAdmin()) {
            return $deny;
        }

        $rows = $this->app->db()->query(
            "SELECT
                p.id,
                pc.catalog_group,
                pc.segment,
                pc.code,
                pc.name,
                pc.price_dzd,
                p.sku,
                p.barcode,
                p.is_active,
                COALESCE(s.quantity_ml, 0) AS stock_bottles,
                COALESCE(s.min_alert_ml, 0) AS min_alert_bottles,
                COALESCE(s.raw_material_quantity_ml, 0) AS raw_material_stock_ml,
                COALESCE(s.raw_material_min_alert_ml, 0) AS raw_material_alert_ml
             FROM products p
             INNER JOIN perfume_catalog pc ON pc.id = p.perfume_catalog_id
             LEFT JOIN stock s ON s.product_id = p.id
             ORDER BY p.id DESC"
        )->fetchAll();

        return new JsonResponse(['items' => $rows]);
    }

    #[Route('/api/admin/products', name: 'api_admin_products_create', methods: ['POST'])]
    public function createProduct(Request $request): JsonResponse
    {
        if ($deny = $this->denyUnlessAdmin()) {
            return $deny;
        }

        $payload = json_decode((string) $request->getContent(), true) ?: [];
        $data = [
            'catalog_group' => trim((string) ($payload['catalog_group'] ?? '')),
            'segment' => trim((string) ($payload['segment'] ?? '')),
            'code' => trim((string) ($payload['code'] ?? '')),
            'name' => trim((string) ($payload['name'] ?? '')),
            'price_dzd' => (float) ($payload['price_dzd'] ?? 0),
            'stock_bottles' => (float) ($payload['stock_bottles'] ?? 0),
            'min_alert_bottles' => (float) ($payload['min_alert_bottles'] ?? 0),
            'raw_material_stock_ml' => (float) ($payload['raw_material_stock_ml'] ?? 0),
            'raw_material_alert_ml' => (float) ($payload['raw_material_alert_ml'] ?? 0),
            'sku' => trim((string) ($payload['sku'] ?? '')),
            'barcode' => trim((string) ($payload['barcode'] ?? '')),
        ];

        $errors = array_filter([
            $this->validateAllowedValue($data['catalog_group'], self::ALLOWED_PRODUCT_GROUPS, 'Categorie invalide.'),
            $this->validateAllowedValue($data['segment'], self::ALLOWED_PRODUCT_SEGMENTS, 'Segment invalide.'),
            $this->validateRequired($data['name'], 'Nom du parfum', 120),
            $data['price_dzd'] <= 0 ? 'Prix invalide.' : null,
            $data['stock_bottles'] < 0 ? 'Stock bouteilles invalide.' : null,
            $data['min_alert_bottles'] < 0 ? 'Seuil alerte bouteilles invalide.' : null,
            $data['raw_material_stock_ml'] < 0 ? 'Stock matieres premieres invalide.' : null,
            $data['raw_material_alert_ml'] < 0 ? 'Seuil alerte matieres premieres invalide.' : null,
        ]);
        if ($errors !== []) {
            return new JsonResponse(['error' => array_values($errors)[0]], 422);
        }

        $db = $this->app->db();
        $db->beginTransaction();
        try {
            $catalogStmt = $db->prepare(
                "INSERT INTO perfume_catalog (catalog_group, segment, code, name, source_label, price_dzd, is_active)
                 VALUES (:catalog_group, :segment, :code, :name, :source_label, :price_dzd, 1)"
            );
            $catalogStmt->execute([
                'catalog_group' => $data['catalog_group'],
                'segment' => $data['segment'],
                'code' => $data['code'] !== '' ? $data['code'] : null,
                'name' => $data['name'],
                'source_label' => $data['name'],
                'price_dzd' => $data['price_dzd'],
            ]);
            $catalogId = (int) $db->lastInsertId();
            $sku = $data['sku'] !== '' ? $data['sku'] : strtoupper($data['catalog_group'] . '-' . $data['segment'] . '-' . $catalogId);

            $productStmt = $db->prepare(
                "INSERT INTO products (perfume_catalog_id, sku, barcode, is_active)
                 VALUES (:catalog_id, :sku, :barcode, 1)"
            );
            $productStmt->execute([
                'catalog_id' => $catalogId,
                'sku' => $sku,
                'barcode' => $data['barcode'] !== '' ? $data['barcode'] : null,
            ]);
            $productId = (int) $db->lastInsertId();

            $db->prepare(
                "INSERT INTO product_prices (product_id, sale_type, price_dzd, created_by)
                 VALUES (:product_id, 'DETAIL', :price_dzd, :created_by)"
            )->execute([
                'product_id' => $productId,
                'price_dzd' => $data['price_dzd'],
                'created_by' => $this->app->currentUserId(),
            ]);

            $db->prepare(
                "INSERT INTO stock (product_id, quantity_ml, min_alert_ml, raw_material_quantity_ml, raw_material_min_alert_ml)
                 VALUES (:product_id, :quantity_ml, :min_alert_ml, :raw_material_quantity_ml, :raw_material_min_alert_ml)"
            )->execute([
                'product_id' => $productId,
                'quantity_ml' => $data['stock_bottles'],
                'min_alert_ml' => $data['min_alert_bottles'],
                'raw_material_quantity_ml' => $data['raw_material_stock_ml'],
                'raw_material_min_alert_ml' => $data['raw_material_alert_ml'],
            ]);

            $db->commit();

            return new JsonResponse(['ok' => true, 'product_id' => $productId], 201);
        } catch (\Throwable $e) {
            $db->rollBack();
            return new JsonResponse(['error' => 'Creation produit impossible.'], 500);
        }
    }

    #[Route('/api/admin/products/{id}', name: 'api_admin_products_update', methods: ['PATCH'])]
    public function updateProduct(int $id, Request $request): JsonResponse
    {
        if ($deny = $this->denyUnlessAdmin()) {
            return $deny;
        }

        $payload = json_decode((string) $request->getContent(), true) ?: [];
        $data = [
            'catalog_group' => trim((string) ($payload['catalog_group'] ?? '')),
            'segment' => trim((string) ($payload['segment'] ?? '')),
            'code' => trim((string) ($payload['code'] ?? '')),
            'name' => trim((string) ($payload['name'] ?? '')),
            'price_dzd' => (float) ($payload['price_dzd'] ?? 0),
            'stock_bottles' => (float) ($payload['stock_bottles'] ?? 0),
            'min_alert_bottles' => (float) ($payload['min_alert_bottles'] ?? 0),
            'raw_material_stock_ml' => (float) ($payload['raw_material_stock_ml'] ?? 0),
            'raw_material_alert_ml' => (float) ($payload['raw_material_alert_ml'] ?? 0),
            'sku' => trim((string) ($payload['sku'] ?? '')),
            'barcode' => trim((string) ($payload['barcode'] ?? '')),
            'is_active' => (int) ($payload['is_active'] ?? 1),
        ];

        $errors = array_filter([
            $this->validateAllowedValue($data['catalog_group'], self::ALLOWED_PRODUCT_GROUPS, 'Categorie invalide.'),
            $this->validateAllowedValue($data['segment'], self::ALLOWED_PRODUCT_SEGMENTS, 'Segment invalide.'),
            $this->validateRequired($data['name'], 'Nom du parfum', 120),
            $data['price_dzd'] <= 0 ? 'Prix invalide.' : null,
            $data['stock_bottles'] < 0 ? 'Stock bouteilles invalide.' : null,
            $data['min_alert_bottles'] < 0 ? 'Seuil alerte bouteilles invalide.' : null,
            $data['raw_material_stock_ml'] < 0 ? 'Stock matieres premieres invalide.' : null,
            $data['raw_material_alert_ml'] < 0 ? 'Seuil alerte matieres premieres invalide.' : null,
        ]);
        if ($errors !== []) {
            return new JsonResponse(['error' => array_values($errors)[0]], 422);
        }

        $db = $this->app->db();
        $productStmt = $db->prepare('SELECT perfume_catalog_id FROM products WHERE id = :id LIMIT 1');
        $productStmt->execute(['id' => $id]);
        $product = $productStmt->fetch();
        if (!$product) {
            return new JsonResponse(['error' => 'Produit introuvable.'], 404);
        }

        $db->beginTransaction();
        try {
            $db->prepare(
                "UPDATE perfume_catalog
                 SET catalog_group = :catalog_group, segment = :segment, code = :code, name = :name,
                     source_label = :source_label, price_dzd = :price_dzd, is_active = :is_active
                 WHERE id = :catalog_id"
            )->execute([
                'catalog_group' => $data['catalog_group'],
                'segment' => $data['segment'],
                'code' => $data['code'] !== '' ? $data['code'] : null,
                'name' => $data['name'],
                'source_label' => $data['name'],
                'price_dzd' => $data['price_dzd'],
                'is_active' => $data['is_active'] ? 1 : 0,
                'catalog_id' => (int) $product['perfume_catalog_id'],
            ]);

            $db->prepare(
                "UPDATE products SET sku = :sku, barcode = :barcode, is_active = :is_active WHERE id = :id"
            )->execute([
                'sku' => $data['sku'] !== '' ? $data['sku'] : null,
                'barcode' => $data['barcode'] !== '' ? $data['barcode'] : null,
                'is_active' => $data['is_active'] ? 1 : 0,
                'id' => $id,
            ]);

            $db->prepare(
                "UPDATE product_prices
                 SET price_dzd = :price_dzd
                 WHERE product_id = :product_id AND sale_type = 'DETAIL' AND ends_at IS NULL"
            )->execute([
                'price_dzd' => $data['price_dzd'],
                'product_id' => $id,
            ]);

            $db->prepare(
                "UPDATE stock
                 SET quantity_ml = :quantity_ml,
                     min_alert_ml = :min_alert_ml,
                     raw_material_quantity_ml = :raw_material_quantity_ml,
                     raw_material_min_alert_ml = :raw_material_min_alert_ml
                 WHERE product_id = :product_id"
            )->execute([
                'quantity_ml' => $data['stock_bottles'],
                'min_alert_ml' => $data['min_alert_bottles'],
                'raw_material_quantity_ml' => $data['raw_material_stock_ml'],
                'raw_material_min_alert_ml' => $data['raw_material_alert_ml'],
                'product_id' => $id,
            ]);

            $db->commit();

            return new JsonResponse(['ok' => true]);
        } catch (\Throwable $e) {
            $db->rollBack();
            return new JsonResponse(['error' => 'Mise a jour produit impossible.'], 500);
        }
    }

    #[Route('/api/admin/products/{id}', name: 'api_admin_products_delete', methods: ['DELETE'])]
    public function deleteProduct(int $id): JsonResponse
    {
        if ($deny = $this->denyUnlessAdmin()) {
            return $deny;
        }

        $db = $this->app->db();
        $productStmt = $db->prepare('SELECT perfume_catalog_id FROM products WHERE id = :id LIMIT 1');
        $productStmt->execute(['id' => $id]);
        $product = $productStmt->fetch();
        if (!$product) {
            return new JsonResponse(['error' => 'Produit introuvable.'], 404);
        }

        $db->beginTransaction();
        try {
            $db->prepare('UPDATE products SET is_active = 0 WHERE id = :id')->execute(['id' => $id]);
            $db->prepare('UPDATE perfume_catalog SET is_active = 0 WHERE id = :id')->execute(['id' => (int) $product['perfume_catalog_id']]);
            $db->commit();

            return new JsonResponse(['ok' => true]);
        } catch (\Throwable $e) {
            $db->rollBack();
            return new JsonResponse(['error' => 'Suppression produit impossible.'], 500);
        }
    }

    #[Route('/api/admin/orders', name: 'api_admin_orders', methods: ['GET'])]
    public function orders(): JsonResponse
    {
        if ($deny = $this->denyUnlessAdmin()) {
            return $deny;
        }

        $db = $this->app->db();
        $rows = $db->query(
            "SELECT
                o.id,
                o.order_number,
                o.status AS order_status,
                o.total_dzd,
                o.created_at,
                u.first_name,
                u.last_name,
                u.perfume_shop_name,
                i.id AS invoice_id,
                i.invoice_number,
                i.status AS invoice_status,
                COALESCE(i.total_dzd, 0) AS invoice_total,
                COALESCE(SUM(CASE WHEN p.status = 'VALIDE' THEN p.amount_dzd ELSE 0 END), 0) AS paid_amount
             FROM orders o
             INNER JOIN users u ON u.id = o.customer_user_id
             LEFT JOIN invoices i ON i.order_id = o.id
             LEFT JOIN payments p ON p.invoice_id = i.id
             GROUP BY o.id, o.order_number, o.status, o.total_dzd, o.created_at,
                      u.first_name, u.last_name, u.perfume_shop_name,
                      i.id, i.invoice_number, i.status, i.total_dzd
             ORDER BY o.id DESC"
        )->fetchAll();

        foreach ($rows as &$row) {
            $invoiceTotal = (float) ($row['invoice_total'] ?? 0);
            $paidAmount = (float) ($row['paid_amount'] ?? 0);
            $row['remaining_amount'] = max(0, $invoiceTotal - $paidAmount);
        }
        unset($row);

        $paidOrdersTotal = (float) $db->query(
            "SELECT COALESCE(SUM(i.total_dzd), 0)
             FROM invoices i
             WHERE i.status = 'PAYE'"
        )->fetchColumn();

        $todayRevenue = (float) $db->query(
            "SELECT COALESCE(SUM(p.amount_dzd), 0)
             FROM payments p
             WHERE p.status = 'VALIDE'
               AND DATE(p.paid_at) = CURDATE()"
        )->fetchColumn();

        $paidOrdersCount = (int) $db->query(
            "SELECT COUNT(*)
             FROM invoices i
             WHERE i.status = 'PAYE'"
        )->fetchColumn();

        return new JsonResponse([
            'items' => $rows,
            'summary' => [
                'paid_orders_total' => $paidOrdersTotal,
                'today_revenue' => $todayRevenue,
                'paid_orders_count' => $paidOrdersCount,
                'orders_count' => count($rows),
            ],
        ]);
    }

    #[Route('/api/admin/orders', name: 'api_admin_orders_create', methods: ['POST'])]
    public function createOrder(Request $request): JsonResponse
    {
        if ($deny = $this->denyUnlessAdmin()) {
            return $deny;
        }

        $payload = json_decode((string) $request->getContent(), true) ?: [];
        $userId = (int) ($payload['user_id'] ?? 0);
        $client = $payload['client'] ?? [];
        $items = $payload['items'] ?? [];

        if ($userId <= 0) {
            return new JsonResponse(['error' => 'Parfumerie invalide.'], 422);
        }

        if (!is_array($items) || count($items) === 0) {
            return new JsonResponse(['error' => 'Ajoutez au moins un produit.'], 422);
        }

        $clientErrors = array_filter([
            $this->validateClientField(trim((string) ($client['last_name'] ?? '')), 'Nom', 100),
            $this->validateClientField(trim((string) ($client['first_name'] ?? '')), 'Prenom', 100),
            $this->validatePhone(trim((string) ($client['phone'] ?? ''))),
            $this->validateClientField(trim((string) ($client['shop'] ?? '')), 'Nom de la parfumerie', 150),
        ]);
        if ($clientErrors !== []) {
            return new JsonResponse(['error' => array_values($clientErrors)[0]], 422);
        }

        $resolvedItems = $this->orderPricing->resolveAdminItems($items);
        if (isset($resolvedItems['error'])) {
            return new JsonResponse(['error' => $resolvedItems['error']], $resolvedItems['status']);
        }

        $db = $this->app->db();
        $userStmt = $db->prepare('SELECT id FROM users WHERE id = :id AND is_active = 1 LIMIT 1');
        $userStmt->execute(['id' => $userId]);
        if (!$userStmt->fetch()) {
            return new JsonResponse(['error' => 'Parfumerie introuvable.'], 404);
        }

        $db->beginTransaction();
        try {
            $orderNumber = sprintf('CMD-%s-%04d', date('Y'), random_int(1000, 9999));
            $invoiceNumber = sprintf('FAC-%s-%04d', date('Y'), random_int(1000, 9999));

            $total = $this->orderPricing->total($resolvedItems['items']);

            $notes = json_encode([
                'last_name' => (string) ($client['last_name'] ?? ''),
                'first_name' => (string) ($client['first_name'] ?? ''),
                'phone' => (string) ($client['phone'] ?? ''),
                'shop' => (string) ($client['shop'] ?? ''),
                'created_by_admin' => true,
            ], JSON_UNESCAPED_UNICODE);

            $stmtOrder = $db->prepare(
                "INSERT INTO orders (order_number, customer_user_id, sale_type, status, notes, subtotal_dzd, total_dzd, created_by)
                 VALUES (:n, :uid, 'DETAIL', 'CONFIRMEE', :notes, :sub, :total, :created_by)"
            );
            $stmtOrder->execute([
                'n' => $orderNumber,
                'uid' => $userId,
                'notes' => $notes,
                'sub' => $total,
                'total' => $total,
                'created_by' => $this->app->currentUserId(),
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

            $stockError = $this->reserveStock($db, $resolvedItems['items'], $orderId);
            if ($stockError !== null) {
                $db->rollBack();

                return new JsonResponse($stockError, 422);
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
                'ok' => true,
                'order_id' => $orderId,
                'order_number' => $orderNumber,
                'invoice_number' => $invoiceNumber,
            ], 201);
        } catch (\Throwable $e) {
            $db->rollBack();
            return new JsonResponse(['error' => 'Creation commande admin impossible.'], 500);
        }
    }

    #[Route('/api/admin/orders/{id}', name: 'api_admin_order_detail', methods: ['GET'])]
    public function orderDetail(int $id): JsonResponse
    {
        if ($deny = $this->denyUnlessAdmin()) {
            return $deny;
        }

        $db = $this->app->db();
        $stmt = $db->prepare(
            "SELECT
                o.id,
                o.order_number,
                o.status AS order_status,
                o.total_dzd,
                o.subtotal_dzd,
                o.notes,
                o.created_at,
                u.first_name,
                u.last_name,
                u.phone,
                u.perfume_shop_name,
                i.id AS invoice_id,
                i.invoice_number,
                i.status AS invoice_status,
                i.total_dzd AS invoice_total,
                i.issued_at,
                COALESCE(SUM(CASE WHEN p.status = 'VALIDE' THEN p.amount_dzd ELSE 0 END), 0) AS paid_amount
             FROM orders o
             INNER JOIN users u ON u.id = o.customer_user_id
             LEFT JOIN invoices i ON i.order_id = o.id
             LEFT JOIN payments p ON p.invoice_id = i.id
             WHERE o.id = :id
             GROUP BY o.id, o.order_number, o.status, o.total_dzd, o.subtotal_dzd, o.notes, o.created_at,
                      u.first_name, u.last_name, u.phone, u.perfume_shop_name,
                      i.id, i.invoice_number, i.status, i.total_dzd, i.issued_at
             LIMIT 1"
        );
        $stmt->execute(['id' => $id]);
        $order = $stmt->fetch();
        if (!$order) {
            return new JsonResponse(['error' => 'Commande introuvable.'], 404);
        }
        $order['remaining_amount'] = max(0, (float) ($order['invoice_total'] ?? 0) - (float) ($order['paid_amount'] ?? 0));

        $itemsStmt = $db->prepare(
            "SELECT
                oi.id,
                pc.name,
                pc.catalog_group,
                pc.segment,
                oi.quantity_ml AS quantity_bottles,
                oi.unit_price_dzd,
                oi.line_total_dzd
             FROM order_items oi
             INNER JOIN products p ON p.id = oi.product_id
             INNER JOIN perfume_catalog pc ON pc.id = p.perfume_catalog_id
             WHERE oi.order_id = :order_id
             ORDER BY oi.id ASC"
        );
        $itemsStmt->execute(['order_id' => $id]);

        return new JsonResponse([
            'order' => $order,
            'items' => $itemsStmt->fetchAll(),
        ]);
    }

    #[Route('/api/admin/orders/{id}', name: 'api_admin_order_update', methods: ['PATCH'])]
    public function updateOrder(int $id, Request $request): JsonResponse
    {
        if ($deny = $this->denyUnlessAdmin()) {
            return $deny;
        }

        $payload = json_decode((string) $request->getContent(), true) ?: [];
        $lastName = trim((string) ($payload['last_name'] ?? ''));
        $firstName = trim((string) ($payload['first_name'] ?? ''));
        $phone = trim((string) ($payload['phone'] ?? ''));
        $shop = trim((string) ($payload['shop'] ?? ''));

        $errors = array_filter([
            $this->validateRequired($lastName, 'Nom client', 100),
            $this->validateRequired($firstName, 'Prenom client', 100),
            $this->validatePhone($phone),
            $this->validateRequired($shop, 'Parfumerie', 150),
        ]);
        if ($errors !== []) {
            return new JsonResponse(['error' => array_values($errors)[0]], 422);
        }

        $notes = json_encode([
            'last_name' => $lastName,
            'first_name' => $firstName,
            'phone' => $phone,
            'shop' => $shop,
        ], JSON_UNESCAPED_UNICODE);

        $stmt = $this->app->db()->prepare('UPDATE orders SET notes = :notes WHERE id = :id');
        $stmt->execute([
            'notes' => $notes,
            'id' => $id,
        ]);

        if ($stmt->rowCount() === 0) {
            return new JsonResponse(['error' => 'Commande introuvable.'], 404);
        }

        return new JsonResponse(['ok' => true]);
    }

    #[Route('/api/admin/orders/{id}', name: 'api_admin_order_delete', methods: ['DELETE'])]
    public function deleteOrder(int $id): JsonResponse
    {
        if ($deny = $this->denyUnlessAdmin()) {
            return $deny;
        }

        $db = $this->app->db();
        $db->beginTransaction();
        try {
            $this->restoreOrderStock($db, $id);

            $stmtInv = $db->prepare("SELECT id FROM invoices WHERE order_id = :oid");
            $stmtInv->execute(['oid' => $id]);
            $invoiceIds = array_map(static fn(array $r) => (int) $r['id'], $stmtInv->fetchAll());
            if (count($invoiceIds) > 0) {
                $in = implode(',', $invoiceIds);
                $db->exec("DELETE FROM payments WHERE invoice_id IN ($in)");
                $db->exec("DELETE FROM invoices WHERE id IN ($in)");
            }

            $db->prepare('DELETE FROM order_status_history WHERE order_id = :id')->execute(['id' => $id]);
            $db->prepare('DELETE FROM order_items WHERE order_id = :id')->execute(['id' => $id]);
            $orderStmt = $db->prepare('DELETE FROM orders WHERE id = :id');
            $orderStmt->execute(['id' => $id]);

            if ($orderStmt->rowCount() === 0) {
                $db->rollBack();
                return new JsonResponse(['error' => 'Commande introuvable.'], 404);
            }

            $db->commit();
            return new JsonResponse(['ok' => true]);
        } catch (\Throwable $e) {
            $db->rollBack();
            return new JsonResponse(['error' => 'Suppression commande impossible.'], 500);
        }
    }

    #[Route('/api/admin/orders/{id}/status', name: 'api_admin_order_status', methods: ['PATCH'])]
    public function updateOrderStatus(int $id, Request $request): JsonResponse
    {
        if ($deny = $this->denyUnlessAdmin()) {
            return $deny;
        }

        $payload = json_decode((string) $request->getContent(), true) ?: [];
        $status = strtoupper(trim((string) ($payload['status'] ?? '')));
        $allowed = ['CONFIRMEE', 'EN_PREPARATION', 'EXPEDIEE', 'LIVREE', 'ANNULEE'];
        if (!in_array($status, $allowed, true)) {
            return new JsonResponse(['error' => 'Statut commande invalide.'], 422);
        }

        $db = $this->app->db();
        $currentStmt = $db->prepare('SELECT status FROM orders WHERE id = :id LIMIT 1');
        $currentStmt->execute(['id' => $id]);
        $currentStatus = $currentStmt->fetchColumn();
        if (!$currentStatus) {
            return new JsonResponse(['error' => 'Commande introuvable.'], 404);
        }

        $db->beginTransaction();
        try {
            $db->prepare('UPDATE orders SET status = :status WHERE id = :id')->execute([
                'status' => $status,
                'id' => $id,
            ]);

            $db->prepare(
                "INSERT INTO order_status_history (order_id, old_status, new_status, changed_by)
                 VALUES (:order_id, :old_status, :new_status, :changed_by)"
            )->execute([
                'order_id' => $id,
                'old_status' => $currentStatus,
                'new_status' => $status,
                'changed_by' => $this->app->currentUserId(),
            ]);

            $db->commit();
            return new JsonResponse(['ok' => true]);
        } catch (\Throwable $e) {
            $db->rollBack();
            return new JsonResponse(['error' => 'Mise a jour statut commande impossible.'], 500);
        }
    }

    #[Route('/api/admin/invoices/{id}/status', name: 'api_admin_invoice_status', methods: ['PATCH'])]
    public function updateInvoiceStatus(int $id, Request $request): JsonResponse
    {
        if ($deny = $this->denyUnlessAdmin()) {
            return $deny;
        }

        $payload = json_decode((string) $request->getContent(), true) ?: [];
        $status = strtoupper(trim((string) ($payload['status'] ?? '')));
        $amountPaid = isset($payload['amount_paid']) ? (float) $payload['amount_paid'] : 0.0;
        $allowed = ['NON_PAYE', 'PARTIEL', 'PAYE'];
        if (!in_array($status, $allowed, true)) {
            return new JsonResponse(['error' => 'Statut paiement invalide.'], 422);
        }

        $db = $this->app->db();
        try {
            $invoice = $this->invoicePaymentSnapshot($db, $id);
        } catch (\RuntimeException) {
            return new JsonResponse(['error' => 'Facture introuvable.'], 404);
        }

        $db->beginTransaction();
        try {
            if ($status === 'PAYE') {
                $remaining = (float) $invoice['remaining_amount'];
                if ($remaining <= 0) {
                    $this->syncInvoiceStatus($db, $id);
                    $db->commit();

                    return new JsonResponse(['ok' => true] + $this->invoicePaymentSnapshot($db, $id));
                }

                $db->prepare(
                    "INSERT INTO payments (invoice_id, method, amount_dzd, status, received_by)
                     VALUES (:invoice_id, 'ESPECES', :amount_dzd, 'VALIDE', :received_by)"
                )->execute([
                    'invoice_id' => $id,
                    'amount_dzd' => $remaining,
                    'received_by' => $this->app->currentUserId(),
                ]);
            } elseif ($status === 'NON_PAYE' && (float) $invoice['paid_amount'] > 0) {
                $db->rollBack();

                return new JsonResponse(['error' => 'Des paiements existent deja pour cette facture.'], 409);
            } elseif ($status === 'PARTIEL') {
                $remaining = (float) $invoice['remaining_amount'];
                if ($remaining <= 0) {
                    $db->rollBack();

                    return new JsonResponse(['error' => 'Cette facture est deja payee.'], 409);
                }
                if ($amountPaid <= 0) {
                    $db->rollBack();

                    return new JsonResponse(['error' => 'Saisissez le montant paye partiellement.'], 422);
                }
                if ($amountPaid >= $remaining) {
                    $db->rollBack();

                    return new JsonResponse(['error' => 'Le montant partiel doit etre inferieur au reste a payer. Utilisez PAYE pour solder la facture.'], 422);
                }

                $db->prepare(
                    "INSERT INTO payments (invoice_id, method, amount_dzd, status, received_by)
                     VALUES (:invoice_id, 'ESPECES', :amount_dzd, 'VALIDE', :received_by)"
                )->execute([
                    'invoice_id' => $id,
                    'amount_dzd' => $amountPaid,
                    'received_by' => $this->app->currentUserId(),
                ]);
            }

            $this->syncInvoiceStatus($db, $id);
            $db->commit();
            return new JsonResponse(['ok' => true] + $this->invoicePaymentSnapshot($db, $id));
        } catch (\Throwable $e) {
            $db->rollBack();
            return new JsonResponse(['error' => 'Mise a jour paiement impossible.'], 500);
        }
    }

    #[Route('/api/admin/employees', name: 'api_admin_employees', methods: ['GET'])]
    public function employees(): JsonResponse
    {
        if ($deny = $this->denyUnlessAdmin()) {
            return $deny;
        }

        $rows = $this->app->db()->query(
            "SELECT
                e.id,
                e.employee_code,
                e.job_title,
                e.salary_dzd,
                e.hire_date,
                e.employment_status,
                u.first_name,
                u.last_name,
                u.email,
                u.phone
             FROM employees e
             INNER JOIN users u ON u.id = e.user_id
             ORDER BY e.id DESC"
        )->fetchAll();

        return new JsonResponse(['items' => $rows]);
    }

    #[Route('/api/admin/users', name: 'api_admin_users', methods: ['GET'])]
    public function users(): JsonResponse
    {
        if ($deny = $this->denyUnlessAdmin()) {
            return $deny;
        }

        $rows = $this->app->db()->query(
            "SELECT
                u.id,
                u.first_name,
                u.last_name,
                u.perfume_shop_name,
                u.phone,
                u.location,
                u.email,
                u.is_active,
                COALESCE((
                    SELECT r.role_name
                    FROM user_roles ur
                    INNER JOIN roles r ON r.id = ur.role_id
                    WHERE ur.user_id = u.id
                    ORDER BY FIELD(r.role_name, 'ADMIN', 'DIRECTEUR', 'MANAGER', 'EMPLOYE', 'CLIENT')
                    LIMIT 1
                ), 'CLIENT') AS role_name
             FROM users u
             ORDER BY u.id DESC"
        )->fetchAll();

        return new JsonResponse(['items' => $rows]);
    }

    #[Route('/api/admin/users/{id}', name: 'api_admin_users_update', methods: ['PATCH'])]
    public function updateUser(int $id, Request $request): JsonResponse
    {
        if ($deny = $this->denyUnlessAdmin()) {
            return $deny;
        }

        $payload = json_decode((string) $request->getContent(), true) ?: [];
        $data = [
            'first_name' => trim((string) ($payload['first_name'] ?? '')),
            'last_name' => trim((string) ($payload['last_name'] ?? '')),
            'perfume_shop_name' => trim((string) ($payload['perfume_shop_name'] ?? '')),
            'phone' => trim((string) ($payload['phone'] ?? '')),
            'location' => trim((string) ($payload['location'] ?? '')),
            'email' => mb_strtolower(trim((string) ($payload['email'] ?? ''))),
            'is_active' => (int) ($payload['is_active'] ?? 1),
        ];

        $errors = array_filter([
            $this->validateRequired($data['first_name'], 'Prenom', 100),
            $this->validateRequired($data['last_name'], 'Nom', 100),
            $this->validateRequired($data['perfume_shop_name'], 'Parfumerie', 150),
            $this->validatePhone($data['phone']),
            $this->validateRequired($data['location'], 'Localisation', 150),
            $this->validateEmail($data['email']),
        ]);
        if ($errors !== []) {
            return new JsonResponse(['error' => array_values($errors)[0]], 422);
        }

        $db = $this->app->db();
        $check = $db->prepare('SELECT id FROM users WHERE (email = :email OR phone = :phone) AND id <> :id LIMIT 1');
        $check->execute([
            'email' => $data['email'],
            'phone' => $data['phone'],
            'id' => $id,
        ]);
        if ($check->fetch()) {
            return new JsonResponse(['error' => 'Email ou telephone deja utilise.'], 409);
        }

        $stmt = $db->prepare(
            "UPDATE users
             SET first_name = :first_name,
                 last_name = :last_name,
                 perfume_shop_name = :perfume_shop_name,
                 phone = :phone,
                 location = :location,
                 email = :email,
                 is_active = :is_active
             WHERE id = :id"
        );
        $stmt->execute([
            ...$data,
            'id' => $id,
        ]);

        if ($stmt->rowCount() === 0) {
            return new JsonResponse(['error' => 'Utilisateur introuvable.'], 404);
        }

        return new JsonResponse(['ok' => true]);
    }

    #[Route('/api/admin/users/{id}', name: 'api_admin_users_delete', methods: ['DELETE'])]
    public function deleteUser(int $id): JsonResponse
    {
        if ($deny = $this->denyUnlessAdmin()) {
            return $deny;
        }

        $db = $this->app->db();
        $db->beginTransaction();
        try {
            $db->prepare('UPDATE users SET is_active = 0 WHERE id = :id')->execute(['id' => $id]);
            $db->prepare("UPDATE employees SET employment_status = 'INACTIF' WHERE user_id = :id")->execute(['id' => $id]);
            $db->commit();

            return new JsonResponse(['ok' => true]);
        } catch (\Throwable $e) {
            $db->rollBack();
            return new JsonResponse(['error' => 'Suppression utilisateur impossible.'], 500);
        }
    }

    #[Route('/api/admin/employees', name: 'api_admin_employees_create', methods: ['POST'])]
    public function createEmployee(Request $request): JsonResponse
    {
        if ($deny = $this->denyUnlessAdmin()) {
            return $deny;
        }

        $payload = json_decode((string) $request->getContent(), true) ?: [];
        $data = [
            'first_name' => trim((string) ($payload['first_name'] ?? '')),
            'last_name' => trim((string) ($payload['last_name'] ?? '')),
            'email' => mb_strtolower(trim((string) ($payload['email'] ?? ''))),
            'phone' => trim((string) ($payload['phone'] ?? '')),
            'employee_code' => trim((string) ($payload['employee_code'] ?? '')),
            'job_title' => trim((string) ($payload['job_title'] ?? '')),
            'salary_dzd' => (float) ($payload['salary_dzd'] ?? 0),
            'hire_date' => trim((string) ($payload['hire_date'] ?? '')),
            'password' => (string) ($payload['password'] ?? ''),
        ];

        $errors = array_filter([
            $this->validateRequired($data['first_name'], 'Prenom', 100),
            $this->validateRequired($data['last_name'], 'Nom', 100),
            $this->validateEmail($data['email']),
            $this->validatePhone($data['phone']),
            $this->validateRequired($data['employee_code'], 'Code employe', 50),
            $this->validateRequired($data['job_title'], 'Poste', 120),
            $data['salary_dzd'] < 0 ? 'Salaire invalide.' : null,
            $this->validateDate($data['hire_date'], 'Date embauche'),
            mb_strlen($data['password']) < 8 ? 'Mot de passe employe trop court.' : null,
        ]);
        if ($errors !== []) {
            return new JsonResponse(['error' => array_values($errors)[0]], 422);
        }

        $db = $this->app->db();
        $check = $db->prepare(
            'SELECT id FROM users WHERE email = :email OR phone = :phone
             UNION
             SELECT id FROM employees WHERE employee_code = :employee_code
             LIMIT 1'
        );
        $check->execute([
            'email' => $data['email'],
            'phone' => $data['phone'],
            'employee_code' => $data['employee_code'],
        ]);
        if ($check->fetch()) {
            return new JsonResponse(['error' => 'Email, telephone ou code employe deja utilise.'], 409);
        }

        $db->beginTransaction();
        try {
            $db->prepare(
                "INSERT INTO users (first_name, last_name, perfume_shop_name, phone, location, email, password_hash, is_active)
                 VALUES (:first_name, :last_name, 'IDENE PARFUM', :phone, 'Tunis', :email, :password_hash, 1)"
            )->execute([
                'first_name' => $data['first_name'],
                'last_name' => $data['last_name'],
                'phone' => $data['phone'],
                'email' => $data['email'],
                'password_hash' => password_hash($data['password'], PASSWORD_DEFAULT),
            ]);
            $userId = (int) $db->lastInsertId();

            $roleId = (int) $db->query("SELECT id FROM roles WHERE role_name = 'EMPLOYE' LIMIT 1")->fetchColumn();
            if ($roleId > 0) {
                $db->prepare(
                    'INSERT INTO user_roles (user_id, role_id, assigned_by) VALUES (:user_id, :role_id, :assigned_by)'
                )->execute([
                    'user_id' => $userId,
                    'role_id' => $roleId,
                    'assigned_by' => $this->app->currentUserId(),
                ]);
            }

            $db->prepare(
                "INSERT INTO employees (user_id, employee_code, job_title, salary_dzd, hire_date, employment_status, manager_user_id)
                 VALUES (:user_id, :employee_code, :job_title, :salary_dzd, :hire_date, 'ACTIF', :manager_user_id)"
            )->execute([
                'user_id' => $userId,
                'employee_code' => $data['employee_code'],
                'job_title' => $data['job_title'],
                'salary_dzd' => $data['salary_dzd'],
                'hire_date' => $data['hire_date'],
                'manager_user_id' => $this->app->currentUserId(),
            ]);

            $db->commit();
            return new JsonResponse(['ok' => true], 201);
        } catch (\Throwable $e) {
            $db->rollBack();
            return new JsonResponse(['error' => 'Creation employe impossible.'], 500);
        }
    }

    #[Route('/api/admin/employees/{id}', name: 'api_admin_employees_update', methods: ['PATCH'])]
    public function updateEmployee(int $id, Request $request): JsonResponse
    {
        if ($deny = $this->denyUnlessAdmin()) {
            return $deny;
        }

        $payload = json_decode((string) $request->getContent(), true) ?: [];
        $data = [
            'job_title' => trim((string) ($payload['job_title'] ?? '')),
            'salary_dzd' => (float) ($payload['salary_dzd'] ?? 0),
            'employment_status' => strtoupper(trim((string) ($payload['employment_status'] ?? 'ACTIF'))),
        ];
        $allowed = ['ACTIF', 'INACTIF', 'SUSPENDU'];
        $errors = array_filter([
            $this->validateRequired($data['job_title'], 'Poste', 120),
            $data['salary_dzd'] < 0 ? 'Salaire invalide.' : null,
            !in_array($data['employment_status'], $allowed, true) ? 'Statut employe invalide.' : null,
        ]);
        if ($errors !== []) {
            return new JsonResponse(['error' => array_values($errors)[0]], 422);
        }

        $check = $this->app->db()->prepare('SELECT id FROM employees WHERE id = :id LIMIT 1');
        $check->execute(['id' => $id]);
        if (!$check->fetch()) {
            return new JsonResponse(['error' => 'Employe introuvable.'], 404);
        }

        $stmt = $this->app->db()->prepare(
            'UPDATE employees
             SET job_title = :job_title, salary_dzd = :salary_dzd, employment_status = :employment_status
             WHERE id = :id'
        );
        $stmt->execute([
            'job_title' => $data['job_title'],
            'salary_dzd' => $data['salary_dzd'],
            'employment_status' => $data['employment_status'],
            'id' => $id,
        ]);

        return new JsonResponse(['ok' => true]);
    }

    #[Route('/api/admin/employees/{id}', name: 'api_admin_employees_delete', methods: ['DELETE'])]
    public function deleteEmployee(int $id): JsonResponse
    {
        if ($deny = $this->denyUnlessAdmin()) {
            return $deny;
        }

        $db = $this->app->db();
        $stmt = $db->prepare('SELECT user_id FROM employees WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $id]);
        $employee = $stmt->fetch();
        if (!$employee) {
            return new JsonResponse(['error' => 'Employe introuvable.'], 404);
        }

        $db->beginTransaction();
        try {
            $db->prepare("UPDATE employees SET employment_status = 'INACTIF' WHERE id = :id")->execute(['id' => $id]);
            $db->prepare('UPDATE users SET is_active = 0 WHERE id = :id')->execute(['id' => (int) $employee['user_id']]);
            $db->commit();

            return new JsonResponse(['ok' => true]);
        } catch (\Throwable $e) {
            $db->rollBack();
            return new JsonResponse(['error' => 'Suppression employe impossible.'], 500);
        }
    }

    #[Route('/api/admin/expenses', name: 'api_admin_expenses', methods: ['GET'])]
    public function expenses(): JsonResponse
    {
        if ($deny = $this->denyUnlessAdmin()) {
            return $deny;
        }

        $rows = $this->app->db()->query(
            'SELECT id, expense_type, label, amount_dzd, expense_date, note
             FROM business_expenses
             ORDER BY expense_date DESC, id DESC'
        )->fetchAll();

        return new JsonResponse(['items' => $rows]);
    }

    #[Route('/api/admin/raw-materials', name: 'api_admin_raw_materials', methods: ['GET'])]
    public function rawMaterials(): JsonResponse
    {
        if ($deny = $this->denyUnlessAdmin()) {
            return $deny;
        }

        $rows = $this->app->db()->query(
            'SELECT
                id,
                material_category,
                item_name,
                unit_label,
                quantity_in_stock,
                min_alert_quantity,
                unit_cost_dzd,
                (quantity_in_stock * unit_cost_dzd) AS total_cost_dzd,
                purchase_date,
                supplier_name,
                note
             FROM raw_material_inventory
             ORDER BY purchase_date DESC, id DESC'
        )->fetchAll();

        return new JsonResponse(['items' => $rows]);
    }

    #[Route('/api/admin/raw-materials', name: 'api_admin_raw_materials_create', methods: ['POST'])]
    public function createRawMaterial(Request $request): JsonResponse
    {
        if ($deny = $this->denyUnlessAdmin()) {
            return $deny;
        }

        $payload = json_decode((string) $request->getContent(), true) ?: [];
        $data = [
            'material_category' => strtoupper(trim((string) ($payload['material_category'] ?? ''))),
            'item_name' => trim((string) ($payload['item_name'] ?? '')),
            'unit_label' => trim((string) ($payload['unit_label'] ?? 'piece')),
            'quantity_in_stock' => (float) ($payload['quantity_in_stock'] ?? 0),
            'min_alert_quantity' => (float) ($payload['min_alert_quantity'] ?? 0),
            'unit_cost_dzd' => (float) ($payload['unit_cost_dzd'] ?? 0),
            'purchase_date' => trim((string) ($payload['purchase_date'] ?? '')),
            'supplier_name' => trim((string) ($payload['supplier_name'] ?? '')),
            'note' => trim((string) ($payload['note'] ?? '')),
        ];
        $allowed = ['BASE', 'ALCOOL', 'COLORANT', 'BOUTEILLE', 'TICKET', 'BOUCHON', 'AUTRE'];
        $errors = array_filter([
            !in_array($data['material_category'], $allowed, true) ? 'Categorie matiere premiere invalide.' : null,
            $this->validateRequired($data['item_name'], 'Nom article', 160),
            $this->validateRequired($data['unit_label'], 'Unite', 30),
            $data['quantity_in_stock'] < 0 ? 'Quantite stock invalide.' : null,
            $data['min_alert_quantity'] < 0 ? 'Seuil alerte invalide.' : null,
            $data['unit_cost_dzd'] < 0 ? 'Cout unitaire invalide.' : null,
            $this->validateDate($data['purchase_date'], 'Date achat'),
        ]);
        if ($errors !== []) {
            return new JsonResponse(['error' => array_values($errors)[0]], 422);
        }

        $this->app->db()->prepare(
            'INSERT INTO raw_material_inventory
             (material_category, item_name, unit_label, quantity_in_stock, min_alert_quantity, unit_cost_dzd, purchase_date, supplier_name, note, created_by)
             VALUES
             (:material_category, :item_name, :unit_label, :quantity_in_stock, :min_alert_quantity, :unit_cost_dzd, :purchase_date, :supplier_name, :note, :created_by)'
        )->execute([
            'material_category' => $data['material_category'],
            'item_name' => $data['item_name'],
            'unit_label' => $data['unit_label'],
            'quantity_in_stock' => $data['quantity_in_stock'],
            'min_alert_quantity' => $data['min_alert_quantity'],
            'unit_cost_dzd' => $data['unit_cost_dzd'],
            'purchase_date' => $data['purchase_date'],
            'supplier_name' => $data['supplier_name'] !== '' ? $data['supplier_name'] : null,
            'note' => $data['note'] !== '' ? $data['note'] : null,
            'created_by' => $this->app->currentUserId(),
        ]);

        return new JsonResponse(['ok' => true], 201);
    }

    #[Route('/api/admin/raw-materials/{id}', name: 'api_admin_raw_materials_update', methods: ['PATCH'])]
    public function updateRawMaterial(int $id, Request $request): JsonResponse
    {
        if ($deny = $this->denyUnlessAdmin()) {
            return $deny;
        }

        $payload = json_decode((string) $request->getContent(), true) ?: [];
        $data = [
            'material_category' => strtoupper(trim((string) ($payload['material_category'] ?? ''))),
            'item_name' => trim((string) ($payload['item_name'] ?? '')),
            'unit_label' => trim((string) ($payload['unit_label'] ?? 'piece')),
            'quantity_in_stock' => (float) ($payload['quantity_in_stock'] ?? 0),
            'min_alert_quantity' => (float) ($payload['min_alert_quantity'] ?? 0),
            'unit_cost_dzd' => (float) ($payload['unit_cost_dzd'] ?? 0),
            'purchase_date' => trim((string) ($payload['purchase_date'] ?? '')),
            'supplier_name' => trim((string) ($payload['supplier_name'] ?? '')),
            'note' => trim((string) ($payload['note'] ?? '')),
        ];
        $allowed = ['BASE', 'ALCOOL', 'COLORANT', 'BOUTEILLE', 'TICKET', 'BOUCHON', 'AUTRE'];
        $errors = array_filter([
            !in_array($data['material_category'], $allowed, true) ? 'Categorie matiere premiere invalide.' : null,
            $this->validateRequired($data['item_name'], 'Nom article', 160),
            $this->validateRequired($data['unit_label'], 'Unite', 30),
            $data['quantity_in_stock'] < 0 ? 'Quantite stock invalide.' : null,
            $data['min_alert_quantity'] < 0 ? 'Seuil alerte invalide.' : null,
            $data['unit_cost_dzd'] < 0 ? 'Cout unitaire invalide.' : null,
            $this->validateDate($data['purchase_date'], 'Date achat'),
        ]);
        if ($errors !== []) {
            return new JsonResponse(['error' => array_values($errors)[0]], 422);
        }

        $this->app->db()->prepare(
            'UPDATE raw_material_inventory
             SET material_category = :material_category,
                 item_name = :item_name,
                 unit_label = :unit_label,
                 quantity_in_stock = :quantity_in_stock,
                 min_alert_quantity = :min_alert_quantity,
                 unit_cost_dzd = :unit_cost_dzd,
                 purchase_date = :purchase_date,
                 supplier_name = :supplier_name,
                 note = :note
             WHERE id = :id'
        )->execute([
            'material_category' => $data['material_category'],
            'item_name' => $data['item_name'],
            'unit_label' => $data['unit_label'],
            'quantity_in_stock' => $data['quantity_in_stock'],
            'min_alert_quantity' => $data['min_alert_quantity'],
            'unit_cost_dzd' => $data['unit_cost_dzd'],
            'purchase_date' => $data['purchase_date'],
            'supplier_name' => $data['supplier_name'] !== '' ? $data['supplier_name'] : null,
            'note' => $data['note'] !== '' ? $data['note'] : null,
            'id' => $id,
        ]);

        return new JsonResponse(['ok' => true]);
    }

    #[Route('/api/admin/raw-materials/{id}', name: 'api_admin_raw_materials_delete', methods: ['DELETE'])]
    public function deleteRawMaterial(int $id): JsonResponse
    {
        if ($deny = $this->denyUnlessAdmin()) {
            return $deny;
        }

        $this->app->db()->prepare('DELETE FROM raw_material_inventory WHERE id = :id')->execute(['id' => $id]);

        return new JsonResponse(['ok' => true]);
    }

    #[Route('/api/admin/expenses', name: 'api_admin_expenses_create', methods: ['POST'])]
    public function createExpense(Request $request): JsonResponse
    {
        if ($deny = $this->denyUnlessAdmin()) {
            return $deny;
        }

        $payload = json_decode((string) $request->getContent(), true) ?: [];
        $data = [
            'expense_type' => strtoupper(trim((string) ($payload['expense_type'] ?? ''))),
            'label' => trim((string) ($payload['label'] ?? '')),
            'amount_dzd' => (float) ($payload['amount_dzd'] ?? 0),
            'expense_date' => trim((string) ($payload['expense_date'] ?? '')),
            'note' => trim((string) ($payload['note'] ?? '')),
        ];
        $allowed = ['RAW_MATERIAL', 'SALARY', 'TRANSPORT', 'RENT', 'OTHER'];
        $errors = array_filter([
            !in_array($data['expense_type'], $allowed, true) ? 'Type de charge invalide.' : null,
            $this->validateRequired($data['label'], 'Libelle', 160),
            $data['amount_dzd'] <= 0 ? 'Montant invalide.' : null,
            $this->validateDate($data['expense_date'], 'Date charge'),
        ]);
        if ($errors !== []) {
            return new JsonResponse(['error' => array_values($errors)[0]], 422);
        }

        $this->app->db()->prepare(
            'INSERT INTO business_expenses (expense_type, label, amount_dzd, expense_date, note, created_by)
             VALUES (:expense_type, :label, :amount_dzd, :expense_date, :note, :created_by)'
        )->execute([
            'expense_type' => $data['expense_type'],
            'label' => $data['label'],
            'amount_dzd' => $data['amount_dzd'],
            'expense_date' => $data['expense_date'],
            'note' => $data['note'] !== '' ? $data['note'] : null,
            'created_by' => $this->app->currentUserId(),
        ]);

        return new JsonResponse(['ok' => true], 201);
    }

    #[Route('/api/admin/expenses/{id}', name: 'api_admin_expenses_update', methods: ['PATCH'])]
    public function updateExpense(int $id, Request $request): JsonResponse
    {
        if ($deny = $this->denyUnlessAdmin()) {
            return $deny;
        }

        $payload = json_decode((string) $request->getContent(), true) ?: [];
        $data = [
            'expense_type' => strtoupper(trim((string) ($payload['expense_type'] ?? ''))),
            'label' => trim((string) ($payload['label'] ?? '')),
            'amount_dzd' => (float) ($payload['amount_dzd'] ?? 0),
            'expense_date' => trim((string) ($payload['expense_date'] ?? '')),
            'note' => trim((string) ($payload['note'] ?? '')),
        ];
        $allowed = ['RAW_MATERIAL', 'SALARY', 'TRANSPORT', 'RENT', 'OTHER'];
        $errors = array_filter([
            !in_array($data['expense_type'], $allowed, true) ? 'Type de charge invalide.' : null,
            $this->validateRequired($data['label'], 'Libelle', 160),
            $data['amount_dzd'] <= 0 ? 'Montant invalide.' : null,
            $this->validateDate($data['expense_date'], 'Date charge'),
        ]);
        if ($errors !== []) {
            return new JsonResponse(['error' => array_values($errors)[0]], 422);
        }

        $check = $this->app->db()->prepare('SELECT id FROM business_expenses WHERE id = :id LIMIT 1');
        $check->execute(['id' => $id]);
        if (!$check->fetch()) {
            return new JsonResponse(['error' => 'Charge introuvable.'], 404);
        }

        $stmt = $this->app->db()->prepare(
            'UPDATE business_expenses
             SET expense_type = :expense_type, label = :label, amount_dzd = :amount_dzd, expense_date = :expense_date, note = :note
             WHERE id = :id'
        );
        $stmt->execute([
            'expense_type' => $data['expense_type'],
            'label' => $data['label'],
            'amount_dzd' => $data['amount_dzd'],
            'expense_date' => $data['expense_date'],
            'note' => $data['note'] !== '' ? $data['note'] : null,
            'id' => $id,
        ]);

        return new JsonResponse(['ok' => true]);
    }

    #[Route('/api/admin/expenses/{id}', name: 'api_admin_expenses_delete', methods: ['DELETE'])]
    public function deleteExpense(int $id): JsonResponse
    {
        if ($deny = $this->denyUnlessAdmin()) {
            return $deny;
        }

        $this->app->db()->prepare('DELETE FROM business_expenses WHERE id = :id')->execute(['id' => $id]);

        return new JsonResponse(['ok' => true]);
    }

    #[Route('/api/admin/account', name: 'api_admin_account', methods: ['GET'])]
    public function account(): JsonResponse
    {
        if ($deny = $this->denyUnlessAdmin()) {
            return $deny;
        }

        return new JsonResponse($this->faceProfilesResponse((int) $this->app->currentUserId()));
    }

    #[Route('/api/admin/account/faces', name: 'api_admin_account_face_create', methods: ['POST'])]
    public function createAccountFace(Request $request): JsonResponse
    {
        if ($deny = $this->denyUnlessAdmin()) {
            return $deny;
        }

        $userId = (int) $this->app->currentUserId();
        $payload = json_decode((string) $request->getContent(), true) ?: [];
        $label = trim((string) ($payload['label'] ?? ''));

        if ($label === '') {
            $label = 'Acces visage ' . date('d/m/Y H:i');
        }

        if (mb_strlen($label) > self::FACE_PROFILE_LABEL_MAX_LENGTH) {
            return new JsonResponse(['error' => 'Le nom du visage est trop long.'], 422);
        }

        try {
            $matrix = $this->normalizeFaceMatrix($payload['matrix'] ?? null);
        } catch (\RuntimeException $e) {
            return new JsonResponse(['error' => $e->getMessage()], 422);
        }

        try {
            $this->ensureFaceAuthProfilesTable();

            $stmt = $this->app->db()->prepare(
                'INSERT INTO face_auth_profiles (user_id, profile_label, face_matrix_json)
                 VALUES (:user_id, :profile_label, :face_matrix_json)'
            );
            $stmt->execute([
                'user_id' => $userId,
                'profile_label' => $label,
                'face_matrix_json' => json_encode($matrix, JSON_THROW_ON_ERROR),
            ]);

            $this->syncLegacyMatrixFromFaceProfiles($userId);
        } catch (\PDOException $e) {
            $message = str_contains(mb_strtolower($e->getMessage()), 'duplicate entry')
                ? 'La base bloque encore plusieurs visages pour ce compte. La contrainte a ete detectee.'
                : 'Impossible d enregistrer ce visage pour le moment.';

            return new JsonResponse(['error' => $message], 409);
        } catch (\Throwable) {
            return new JsonResponse(['error' => 'Impossible d enregistrer ce visage pour le moment.'], 500);
        }

        return new JsonResponse($this->faceProfilesResponse($userId), 201);
    }

    #[Route('/api/admin/account/faces/{profileId}', name: 'api_admin_account_face_delete', methods: ['DELETE'])]
    public function deleteAccountFace(int $profileId): JsonResponse
    {
        if ($deny = $this->denyUnlessAdmin()) {
            return $deny;
        }

        $userId = (int) $this->app->currentUserId();
        $this->ensureFaceAuthProfilesTable();

        $delete = $this->app->db()->prepare(
            'DELETE FROM face_auth_profiles
             WHERE id = :id
               AND user_id = :user_id'
        );
        $delete->execute([
            'id' => $profileId,
            'user_id' => $userId,
        ]);

        if ($delete->rowCount() === 0) {
            return new JsonResponse(['error' => 'Visage introuvable.'], 404);
        }

        $this->syncLegacyMatrixFromFaceProfiles($userId);

        return new JsonResponse($this->faceProfilesResponse($userId));
    }
}
