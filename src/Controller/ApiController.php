<?php

namespace App\Controller;

use App\Service\OrderPricingService;
use App\Support\AppContext;
use App\Support\ApiResponse;
use App\Support\InputValidator;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

final class ApiController
{
    public function __construct(
        private readonly AppContext $app,
        private readonly InputValidator $validator,
        private readonly OrderPricingService $orderPricing,
    )
    {
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
                'reason' => 'Restauration stock suppression commande client',
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

    #[Route('/api/perfumes', name: 'api_perfumes', methods: ['GET'])]
    public function perfumes(Request $request): JsonResponse
    {
        $search = trim((string) $request->query->get('search', ''));
        $category = trim((string) $request->query->get('category', 'ALL'));

        $sql = "SELECT
                    pc.id,
                    pc.catalog_group,
                    pc.segment,
                    pc.code,
                    pc.name,
                    pc.price_dzd,
                    COALESCE(s.quantity_ml, 0) AS stock_bottles
                FROM perfume_catalog pc
                LEFT JOIN products pr ON pr.perfume_catalog_id = pc.id
                LEFT JOIN stock s ON s.product_id = pr.id
                WHERE pc.is_active = 1";
        $params = [];

        if ($search !== '') {
            $sql .= " AND (name LIKE :search OR code LIKE :search_code)";
            $params['search'] = '%' . $search . '%';
            $params['search_code'] = '%' . $search . '%';
        }

        if ($category !== '' && $category !== 'ALL') {
            if ($category === 'PRINCIPAL_HOMME') {
                $sql .= " AND catalog_group = 'PRINCIPAL' AND segment = 'HOMME'";
            } elseif ($category === 'PRINCIPAL_FEMME') {
                $sql .= " AND catalog_group = 'PRINCIPAL' AND segment = 'FEMME'";
            } else {
                $sql .= " AND catalog_group = :category";
                $params['category'] = $category;
            }
        }

        $sql .= " ORDER BY
            CASE
                WHEN code REGEXP '^[0-9]+$' THEN CAST(code AS UNSIGNED)
                ELSE 999999
            END ASC,
            code ASC,
            name ASC";
        $stmt = $this->app->db()->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll();

        return new JsonResponse(['items' => $rows]);
    }

    #[Route('/api/orders/history', name: 'api_orders_history', methods: ['GET'])]
    public function history(Request $request): JsonResponse
    {
        $userId = $this->app->currentUserId();
        if (!$userId) {
            return ApiResponse::error('Non authentifie.', 401);
        }

        $sql = "SELECT
                    o.id AS order_id,
                    o.order_number,
                    o.status AS order_status,
                    o.total_dzd,
                    o.created_at AS order_date,
                    o.notes,
                    i.id AS invoice_id,
                    i.invoice_number,
                    i.status AS invoice_status,
                    i.total_dzd AS invoice_total,
                    i.issued_at,
                    COALESCE(SUM(CASE WHEN p.status = 'VALIDE' THEN p.amount_dzd ELSE 0 END), 0) AS paid_amount
                FROM orders o
                LEFT JOIN invoices i ON i.order_id = o.id
                LEFT JOIN payments p ON p.invoice_id = i.id
                WHERE o.customer_user_id = :uid
                GROUP BY o.id, o.order_number, o.status, o.total_dzd, o.created_at, o.notes,
                         i.id, i.invoice_number, i.status, i.total_dzd, i.issued_at
                ORDER BY o.id DESC";

        $stmt = $this->app->db()->prepare($sql);
        $stmt->execute(['uid' => $userId]);
        $rows = $stmt->fetchAll();

        return new JsonResponse(['items' => $rows]);
    }

    #[Route('/api/orders', name: 'api_create_order', methods: ['POST'])]
    public function createOrder(Request $request): JsonResponse
    {
        $payload = json_decode((string) $request->getContent(), true) ?: [];
        $userId = $this->app->currentUserId();
        if (!$userId) {
            return ApiResponse::error('Non authentifie.', 401);
        }
        $client = $payload['client'] ?? [];
        $items = $payload['items'] ?? [];

        if (count($items) === 0) {
            return ApiResponse::error('Panier vide', 400);
        }

        $clientErrors = array_filter([
            $this->validator->required(trim((string) ($client['last_name'] ?? '')), 'Nom', 100),
            $this->validator->required(trim((string) ($client['first_name'] ?? '')), 'Prenom', 100),
            $this->validator->phone(trim((string) ($client['phone'] ?? ''))),
            $this->validator->required(trim((string) ($client['shop'] ?? '')), 'Nom de la parfumerie', 150),
        ]);
        if ($clientErrors !== []) {
            return ApiResponse::validation($clientErrors);
        }

        $resolvedItems = $this->orderPricing->resolveClientItems($items);
        if (isset($resolvedItems['error'])) {
            return ApiResponse::error($resolvedItems['error'], $resolvedItems['status']);
        }

        $db = $this->app->db();
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
            ], JSON_UNESCAPED_UNICODE);

            $stmtOrder = $db->prepare(
                "INSERT INTO orders (order_number, customer_user_id, sale_type, status, notes, subtotal_dzd, total_dzd)
                 VALUES (:n, :uid, 'DETAIL', 'CONFIRMEE', :notes, :sub, :total)"
            );
            $stmtOrder->execute([
                'n' => $orderNumber,
                'uid' => $userId,
                'notes' => $notes,
                'sub' => $total,
                'total' => $total,
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
            $invoiceId = (int) $db->lastInsertId();

            $db->commit();

            return ApiResponse::ok([
                'order_id' => $orderId,
                'invoice_id' => $invoiceId,
                'order_number' => $orderNumber,
                'invoice_number' => $invoiceNumber,
                'total' => $total,
            ]);
        } catch (\Throwable $e) {
            $db->rollBack();
            return ApiResponse::error('Erreur creation commande', 500);
        }
    }

    #[Route('/api/invoices/{id}/pay', name: 'api_pay_invoice', methods: ['POST'])]
    public function payInvoice(int $id): JsonResponse
    {
        $userId = $this->app->currentUserId();
        if (!$userId) {
            return ApiResponse::error('Non authentifie.', 401);
        }

        $db = $this->app->db();
        $stmt = $db->prepare(
            "SELECT
                i.total_dzd,
                COALESCE(SUM(CASE WHEN p.status = 'VALIDE' THEN p.amount_dzd ELSE 0 END), 0) AS paid_amount
             FROM invoices i
             INNER JOIN orders o ON o.id = i.order_id
             LEFT JOIN payments p ON p.invoice_id = i.id
             WHERE i.id = :id
               AND o.customer_user_id = :uid
             GROUP BY i.id, i.total_dzd
             LIMIT 1"
        );
        $stmt->execute([
            'id' => $id,
            'uid' => $userId,
        ]);
        $invoice = $stmt->fetch();
        if (!$invoice) {
            return ApiResponse::error('Facture introuvable', 404);
        }

        $db->beginTransaction();
        try {
            $remaining = max(0, (float) $invoice['total_dzd'] - (float) $invoice['paid_amount']);
            if ($remaining <= 0) {
                $db->rollBack();

                return ApiResponse::error('Cette facture est deja payee.', 409);
            }

            $insertPay = $db->prepare(
                "INSERT INTO payments (invoice_id, method, amount_dzd, status)
                 VALUES (:iid, 'ESPECES', :amount, 'VALIDE')"
            );
            $insertPay->execute([
                'iid' => $id,
                'amount' => $remaining,
            ]);

            $this->syncInvoiceStatus($db, $id);
            $db->commit();
            return ApiResponse::ok();
        } catch (\Throwable $e) {
            $db->rollBack();
            return ApiResponse::error('Erreur paiement', 500);
        }
    }

    #[Route('/api/orders/{id}', name: 'api_delete_order', methods: ['DELETE'])]
    public function deleteOrder(int $id, Request $request): JsonResponse
    {
        $userId = $this->app->currentUserId();
        if (!$userId) {
            return ApiResponse::error('Non authentifie.', 401);
        }

        $db = $this->app->db();
        $db->beginTransaction();
        try {
            $stmtCheck = $db->prepare("SELECT id FROM orders WHERE id = :id AND customer_user_id = :uid LIMIT 1");
            $stmtCheck->execute(['id' => $id, 'uid' => $userId]);
            if (!$stmtCheck->fetch()) {
                $db->rollBack();
                return ApiResponse::error('Commande introuvable', 404);
            }

            $this->restoreOrderStock($db, $id);

            $stmtInv = $db->prepare("SELECT id FROM invoices WHERE order_id = :oid");
            $stmtInv->execute(['oid' => $id]);
            $invoiceIds = array_map(static fn(array $r) => (int) $r['id'], $stmtInv->fetchAll());
            if (count($invoiceIds) > 0) {
                $in = implode(',', $invoiceIds);
                $db->exec("DELETE FROM payments WHERE invoice_id IN ($in)");
                $db->exec("DELETE FROM invoices WHERE id IN ($in)");
            }
            $stmtItems = $db->prepare("DELETE FROM order_items WHERE order_id = :oid");
            $stmtItems->execute(['oid' => $id]);
            $stmtOrder = $db->prepare("DELETE FROM orders WHERE id = :id");
            $stmtOrder->execute(['id' => $id]);
            $db->commit();
            return ApiResponse::ok();
        } catch (\Throwable $e) {
            $db->rollBack();
            return ApiResponse::error('Erreur suppression', 500);
        }
    }

    #[Route('/api/orders/{id}/client', name: 'api_update_order_client', methods: ['PATCH'])]
    public function updateOrderClient(int $id, Request $request): JsonResponse
    {
        $payload = json_decode((string) $request->getContent(), true) ?: [];
        $userId = $this->app->currentUserId();
        if (!$userId) {
            return ApiResponse::error('Non authentifie.', 401);
        }

        $errors = array_filter([
            $this->validator->required(trim((string) ($payload['last_name'] ?? '')), 'Nom', 100),
            $this->validator->required(trim((string) ($payload['first_name'] ?? '')), 'Prenom', 100),
            $this->validator->phone(trim((string) ($payload['phone'] ?? ''))),
            $this->validator->required(trim((string) ($payload['shop'] ?? '')), 'Nom de la parfumerie', 150),
        ]);
        if ($errors !== []) {
            return ApiResponse::validation($errors);
        }

        $notes = json_encode([
            'last_name' => (string) ($payload['last_name'] ?? ''),
            'first_name' => (string) ($payload['first_name'] ?? ''),
            'phone' => (string) ($payload['phone'] ?? ''),
            'shop' => (string) ($payload['shop'] ?? ''),
        ], JSON_UNESCAPED_UNICODE);

        $check = $this->app->db()->prepare(
            "SELECT id FROM orders WHERE id = :id AND customer_user_id = :uid LIMIT 1"
        );
        $check->execute([
            'id' => $id,
            'uid' => $userId,
        ]);
        if (!$check->fetch()) {
            return ApiResponse::error('Commande introuvable', 404);
        }

        $stmt = $this->app->db()->prepare(
            "UPDATE orders SET notes = :notes WHERE id = :id AND customer_user_id = :uid"
        );
        $stmt->execute([
            'notes' => $notes,
            'id' => $id,
            'uid' => $userId,
        ]);

        return ApiResponse::ok();
    }
}
