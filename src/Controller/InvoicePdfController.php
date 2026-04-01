<?php

namespace App\Controller;

use App\Support\AppContext;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class InvoicePdfController
{
    public function __construct(private readonly AppContext $app)
    {
    }

    #[Route('/invoice/{id}/pdf', name: 'app_invoice_pdf', methods: ['GET'])]
    public function __invoke(int $id): Response
    {
        $userId = $this->app->currentUserId();
        if (!$userId) {
            return new RedirectResponse('/auth');
        }

        $db = $this->app->db();

        $stmt = $db->prepare(
            "SELECT
                i.id AS invoice_id,
                i.invoice_number,
                i.status AS invoice_status,
                i.total_dzd,
                i.subtotal_dzd,
                i.issued_at,
                o.id AS order_id,
                o.order_number,
                o.notes
             FROM invoices i
             INNER JOIN orders o ON o.id = i.order_id
             WHERE i.id = :id AND o.customer_user_id = :uid
             LIMIT 1"
        );
        $stmt->execute(['id' => $id, 'uid' => $userId]);
        $invoice = $stmt->fetch();

        if (!$invoice) {
            return new Response('Facture introuvable', 404);
        }

        $itemsStmt = $db->prepare(
            "SELECT
                pc.name,
                oi.quantity_ml,
                oi.unit_price_dzd,
                oi.line_total_dzd
             FROM order_items oi
             INNER JOIN products p ON p.id = oi.product_id
             INNER JOIN perfume_catalog pc ON pc.id = p.perfume_catalog_id
             WHERE oi.order_id = :orderId
             ORDER BY oi.id ASC"
        );
        $itemsStmt->execute(['orderId' => $invoice['order_id']]);
        $items = $itemsStmt->fetchAll();

        $client = json_decode((string) ($invoice['notes'] ?? '{}'), true) ?: [];

        $rowsHtml = '';
        foreach ($items as $item) {
            $rowsHtml .= sprintf(
                '<tr><td>%s</td><td>%s</td><td>%s DT</td><td>%s DT</td></tr>',
                htmlspecialchars((string) $item['name'], ENT_QUOTES),
                htmlspecialchars((string) $item['quantity_ml'], ENT_QUOTES),
                number_format((float) $item['unit_price_dzd'], 2, '.', ''),
                number_format((float) $item['line_total_dzd'], 2, '.', '')
            );
        }

        $html = sprintf(
            '<html><head><meta charset="utf-8"><style>
                body{font-family:DejaVu Sans,sans-serif;color:#111;font-size:12px}
                h1,h2{margin:0 0 10px}
                .box{margin-bottom:20px}
                table{width:100%%;border-collapse:collapse;margin-top:10px}
                th,td{border:1px solid #ccc;padding:8px;text-align:left}
                th{background:#f3f3f3}
                .meta{margin:4px 0}
                .total{margin-top:12px;font-weight:bold;font-size:14px}
            </style></head><body>
                <h1>IDENE PARFUM</h1>
                <div class="box">
                    <h2>Facture %s</h2>
                    <div class="meta">Commande: %s</div>
                    <div class="meta">Date: %s</div>
                </div>
                <div class="box">
                    <h2>Client</h2>
                    <div class="meta">Nom: %s</div>
                    <div class="meta">Prenom: %s</div>
                    <div class="meta">Telephone: %s</div>
                    <div class="meta">Parfumerie: %s</div>
                </div>
                <div class="box">
                    <h2>Articles</h2>
                    <table>
                        <thead><tr><th>Parfum</th><th>Quantite</th><th>Prix unitaire</th><th>Total</th></tr></thead>
                        <tbody>%s</tbody>
                    </table>
                    <div class="total">Montant total: %s DT</div>
                </div>
            </body></html>',
            htmlspecialchars((string) $invoice['invoice_number'], ENT_QUOTES),
            htmlspecialchars((string) $invoice['order_number'], ENT_QUOTES),
            htmlspecialchars(substr((string) $invoice['issued_at'], 0, 10), ENT_QUOTES),
            htmlspecialchars((string) ($client['last_name'] ?? ''), ENT_QUOTES),
            htmlspecialchars((string) ($client['first_name'] ?? ''), ENT_QUOTES),
            htmlspecialchars((string) ($client['phone'] ?? ''), ENT_QUOTES),
            htmlspecialchars((string) ($client['shop'] ?? ''), ENT_QUOTES),
            $rowsHtml,
            number_format((float) $invoice['total_dzd'], 2, '.', '')
        );

        $options = new Options();
        $options->set('isRemoteEnabled', false);
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $response = new Response($dompdf->output());
        $filename = 'facture-' . $invoice['invoice_number'] . '.pdf';
        $response->headers->set('Content-Type', 'application/pdf');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');

        return $response;
    }
}
