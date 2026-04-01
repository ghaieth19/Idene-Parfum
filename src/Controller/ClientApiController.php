<?php

namespace App\Controller;

use App\Service\OrderPricingService;
use App\Support\AppContext;
use App\Support\ApiResponse;
use App\Support\InputValidator;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Client-side API endpoints for the dashboard, shop, orders, invoices and profile.
 */
final class ClientApiController
{
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
            return ApiResponse::error('Non authentifié.', 401);
        }
        return null;
    }

    // ═══════════════════════════════════════════════════════
    //  DASHBOARD
    // ═══════════════════════════════════════════════════════

    #[Route('/api/client/dashboard', name: 'api_client_dashboard', methods: ['GET'])]
    public function dashboard(): JsonResponse
    {
        if ($err = $this->requireAuth()) return $err;
        $db = $this->app->db();
        $uid = $this->userId();

        // Total orders + total amount
        $stmt = $db->prepare("SELECT COUNT(*) AS cnt, COALESCE(SUM(total_dzd),0) AS total FROM orders WHERE customer_user_id = :uid");
        $stmt->execute(['uid' => $uid]);
        $orderStats = $stmt->fetch();

        // Unpaid invoices
        $stmt2 = $db->prepare("SELECT COALESCE(SUM(i.total_dzd - COALESCE(paid.paid,0)),0) AS unpaid
            FROM invoices i
            INNER JOIN orders o ON o.id = i.order_id
            LEFT JOIN (SELECT invoice_id, SUM(CASE WHEN status='VALIDE' THEN amount_dzd ELSE 0 END) AS paid FROM payments GROUP BY invoice_id) paid ON paid.invoice_id = i.id
            WHERE o.customer_user_id = :uid AND i.status IN ('NON_PAYE','PARTIEL')");
        $stmt2->execute(['uid' => $uid]);
        $unpaid = (float) $stmt2->fetchColumn();

        // Total invoices count
        $stmt3 = $db->prepare("SELECT COUNT(*) FROM invoices i INNER JOIN orders o ON o.id = i.order_id WHERE o.customer_user_id = :uid");
        $stmt3->execute(['uid' => $uid]);
        $totalInvoices = (int) $stmt3->fetchColumn();

        // This month amount
        $stmt4 = $db->prepare("SELECT COALESCE(SUM(total_dzd),0) FROM orders WHERE customer_user_id = :uid AND YEAR(created_at)=YEAR(CURRENT_DATE) AND MONTH(created_at)=MONTH(CURRENT_DATE)");
        $stmt4->execute(['uid' => $uid]);
        $monthAmount = (float) $stmt4->fetchColumn();

        // Last order
        $stmt5 = $db->prepare("SELECT order_number, created_at FROM orders WHERE customer_user_id = :uid ORDER BY id DESC LIMIT 1");
        $stmt5->execute(['uid' => $uid]);
        $lastOrder = $stmt5->fetch();

        // Recent orders (5)
        $stmt6 = $db->prepare("SELECT id, order_number, status, total_dzd, created_at FROM orders WHERE customer_user_id = :uid ORDER BY id DESC LIMIT 5");
        $stmt6->execute(['uid' => $uid]);
        $recentOrders = $stmt6->fetchAll();

        return new JsonResponse([
            'success' => true,
            'stats' => [
                'total_orders'     => (int) $orderStats['cnt'],
                'total_amount'     => (float) $orderStats['total'],
                'unpaid_amount'    => $unpaid,
                'total_invoices'   => $totalInvoices,
                'month_amount'     => $monthAmount,
                'last_order_number'=> $lastOrder['order_number'] ?? null,
                'last_order_date'  => $lastOrder['created_at'] ?? null,
            ],
            'recent_orders' => $recentOrders,
        ]);
    }

    // ═══════════════════════════════════════════════════════
    //  SHOP PRODUCTS (with prices for logged-in clients)
    // ═══════════════════════════════════════════════════════

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
            if ($stockMl <= 0) $stockStatus = 'out_of_stock';
            elseif ($alertMl > 0 && $stockMl <= $alertMl) $stockStatus = 'limited';

            $products[] = [
                'id'            => (int) $row['id'],
                'product_id'    => (int) $row['product_id'],
                'name'          => $row['name'],
                'code'          => $row['code'],
                'catalog_group' => $row['catalog_group'],
                'segment'       => $row['segment'],
                'price'         => (float) $row['price'],
                'stock_ml'      => $stockMl,
                'stock_status'  => $stockStatus,
            ];
        }

        return new JsonResponse(['success' => true, 'products' => $products]);
    }

    // ═══════════════════════════════════════════════════════
    //  CLIENT ORDERS
    // ═══════════════════════════════════════════════════════

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

        return new JsonResponse(['success' => true, 'orders' => $stmt->fetchAll()]);
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
        $shipPostal = trim((string) ($shipping['postal_code'] ?? ''));
        $shipCountry = trim((string) ($shipping['country'] ?? ''));
        $shipLine2 = trim((string) ($shipping['line2'] ?? ''));

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
            if (array_key_exists('perfume_id', $line)) {
                $normalizedItems[] = [
                    'perfume_id' => (int) $line['perfume_id'],
                    'qty' => $qty,
                ];
                continue;
            }

            // Compatibilite avec anciens payloads front (product_id/quantity).
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
                    'line2' => $shipLine2,
                    'city' => $shipCity,
                    'region' => $shipRegion,
                    'postal_code' => $shipPostal,
                    'country' => $shipCountry,
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

        $itemsStmt = $db->prepare("SELECT oi.*, pc.name AS product_name
            FROM order_items oi
            INNER JOIN products p ON p.id = oi.product_id
            INNER JOIN perfume_catalog pc ON pc.id = p.perfume_catalog_id
            WHERE oi.order_id = :oid ORDER BY oi.id");
        $itemsStmt->execute(['oid' => $id]);

        return new JsonResponse(['success' => true, 'order' => $order, 'items' => $itemsStmt->fetchAll()]);
    }

    // ═══════════════════════════════════════════════════════
    //  CLIENT INVOICES
    // ═══════════════════════════════════════════════════════

    #[Route('/api/client/invoices', name: 'api_client_invoices', methods: ['GET'])]
    public function clientInvoices(): JsonResponse
    {
        if ($err = $this->requireAuth()) return $err;
        $db = $this->app->db();
        $uid = $this->userId();

        $stmt = $db->prepare("SELECT i.id, i.invoice_number, i.status, i.subtotal_dzd, i.tax_dzd, i.total_dzd,
            i.due_date, i.issued_at, o.order_number
            FROM invoices i
            INNER JOIN orders o ON o.id = i.order_id
            WHERE o.customer_user_id = :uid
            ORDER BY i.id DESC");
        $stmt->execute(['uid' => $uid]);

        return new JsonResponse(['success' => true, 'invoices' => $stmt->fetchAll()]);
    }

    // ═══════════════════════════════════════════════════════
    //  CLIENT PROFILE
    // ═══════════════════════════════════════════════════════

    #[Route('/api/client/profile', name: 'api_client_profile_update', methods: ['PATCH'])]
    public function updateProfile(Request $request): JsonResponse
    {
        if ($err = $this->requireAuth()) return $err;
        $db = $this->app->db();
        $uid = $this->userId();

        $data = json_decode($request->getContent(), true) ?? [];
        $firstName = trim($data['first_name'] ?? '');
        $lastName  = trim($data['last_name'] ?? '');
        $shopName  = trim($data['shop_name'] ?? '');
        $phone     = trim($data['phone'] ?? '');

        $errors = [
            $this->validator->name($firstName, 'Prénom'),
            $this->validator->name($lastName, 'Nom'),
            $this->validator->phone($phone, false),
        ];
        $errors = array_values(array_filter($errors));
        if ($errors) return ApiResponse::validation($errors);

        $stmt = $db->prepare("UPDATE users SET first_name = :fn, last_name = :ln, perfume_shop_name = :sn, phone = :ph WHERE id = :uid");
        $stmt->execute(['fn' => $firstName, 'ln' => $lastName, 'sn' => $shopName, 'ph' => $phone, 'uid' => $uid]);

        return ApiResponse::ok(['message' => 'Profil mis à jour.']);
    }

    #[Route('/api/client/password', name: 'api_client_password', methods: ['PATCH'])]
    public function changePassword(Request $request): JsonResponse
    {
        if ($err = $this->requireAuth()) return $err;
        $db = $this->app->db();
        $uid = $this->userId();

        $data = json_decode($request->getContent(), true) ?? [];
        $current = $data['current_password'] ?? '';
        $newPwd  = $data['new_password'] ?? '';

        if ($current === '' || $newPwd === '') {
            return ApiResponse::error('Tous les champs sont obligatoires.', 422);
        }
        if (mb_strlen($newPwd) < 8) {
            return ApiResponse::error('Le mot de passe doit contenir au moins 8 caractères.', 422);
        }

        $stmt = $db->prepare("SELECT password_hash FROM users WHERE id = :uid");
        $stmt->execute(['uid' => $uid]);
        $hash = $stmt->fetchColumn();

        if (!$hash || !password_verify($current, $hash)) {
            return ApiResponse::error('Mot de passe actuel incorrect.', 422);
        }

        $newHash = password_hash($newPwd, PASSWORD_BCRYPT);
        $db->prepare("UPDATE users SET password_hash = :hash WHERE id = :uid")->execute(['hash' => $newHash, 'uid' => $uid]);

        return ApiResponse::ok(['message' => 'Mot de passe mis à jour.']);
    }
}
