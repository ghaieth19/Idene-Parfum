<?php

namespace App\Controller;

use App\Support\AppContext;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class AdminOrderPdfController
{
    private const TVA_RATE = 0.19;
    private const CICT_RATE = 0.01;
    private const TIMBRE = 1.000;

    public function __construct(private readonly AppContext $app)
    {
    }

    #[Route('/admin/orders/{id}/pdf', name: 'app_admin_order_pdf', methods: ['GET'])]
    public function __invoke(int $id, Request $request): Response
    {
        $userId = $this->app->currentUserId();
        if (!$userId) {
            return new RedirectResponse('/auth');
        }

        if (!$this->app->isAdminSession()) {
            return new Response('Acces admin requis', 403);
        }

        $variant = strtoupper(trim((string) $request->query->get('variant', '')));
        $exportSiteOrder = $variant === 'DETAIL_SITE';
        $exportWholesaleOrder = $variant === 'WHOLESALE_STOCK';

        $db = $this->app->db();
        $stmt = $db->prepare(
            "SELECT
                o.id,
                o.order_number,
                o.sale_type,
                o.created_at,
                o.notes,
                i.invoice_number
             FROM orders o
             LEFT JOIN invoices i ON i.order_id = o.id
             WHERE o.id = :id
             LIMIT 1"
        );
        $stmt->execute(['id' => $id]);
        $order = $stmt->fetch();

        if (!$order) {
            return new Response('Commande introuvable', 404);
        }

        $itemsStmt = $db->prepare(
            "SELECT
                p.id AS product_id,
                pc.code,
                pc.name,
                oi.quantity_ml,
                oi.unit_price_dzd,
                oi.line_total_dzd,
                pp_detail.price_dzd AS detail_unit_price_dzd,
                pp_gros.price_dzd AS gros_unit_price_dzd
             FROM order_items oi
             INNER JOIN products p ON p.id = oi.product_id
             INNER JOIN perfume_catalog pc ON pc.id = p.perfume_catalog_id
             LEFT JOIN product_prices pp_detail
                    ON pp_detail.product_id = p.id
                   AND pp_detail.sale_type = 'DETAIL'
                   AND pp_detail.ends_at IS NULL
             LEFT JOIN product_prices pp_gros
                    ON pp_gros.product_id = p.id
                   AND pp_gros.sale_type = 'GROS'
                   AND pp_gros.ends_at IS NULL
             WHERE oi.order_id = :order_id
             ORDER BY oi.id ASC"
        );
        $itemsStmt->execute(['order_id' => $id]);
        $items = $itemsStmt->fetchAll();

        $client = json_decode((string) ($order['notes'] ?? '{}'), true) ?: [];
        $shipping = is_array($client['shipping_address'] ?? null) ? $client['shipping_address'] : [];
        $documentMeta = is_array($client['document_meta'] ?? null) ? $client['document_meta'] : [];
        $saleType = strtoupper((string) ($order['sale_type'] ?? 'DETAIL'));
        $isWholesale = $saleType === 'GROS';
        $renderWholesaleDocument = $exportWholesaleOrder || ($isWholesale && !$exportSiteOrder);

        $htBrut = 0.0;
        $rowsHtml = '';
        $lineItemsMeta = is_array($documentMeta['line_items'] ?? null) ? array_values($documentMeta['line_items']) : [];
        foreach ($items as $index => $item) {
            $lineMeta = is_array($lineItemsMeta[$index] ?? null) ? $lineItemsMeta[$index] : [];
            $quantityValue = (float) ($item['quantity_ml'] ?? 0);
            if ($exportSiteOrder) {
                $unitPrice = (float) ($item['detail_unit_price_dzd'] ?? 0);
            } elseif ($exportWholesaleOrder && !$isWholesale) {
                $unitPrice = (float) ($item['gros_unit_price_dzd'] ?? 0);
            } else {
                $unitPrice = (float) ($item['unit_price_dzd'] ?? 0);
            }
            if ($unitPrice <= 0) {
                $unitPrice = (float) ($item['unit_price_dzd'] ?? 0);
            }
            $lineTotal = $exportSiteOrder
                ? $quantityValue * $unitPrice
                : (float) $item['line_total_dzd'];
            $quantity = $this->quantity($quantityValue);
            $htBrut += $lineTotal;

            $designation = strtoupper(trim((string) ($lineMeta['display_name'] ?? $item['name'] ?? 'Produit')));
            $referenceCode = (string) ($lineMeta['display_code'] ?? $item['code'] ?? '-');
            $rowsHtml .= sprintf(
                '<tr class="item-row">
                    <td class="center">%s</td>
                    <td>%s</td>
                    <td class="center">%s</td>
                    <td class="center">1</td>
                    <td class="right">%s</td>
                    <td class="right">0.000</td>
                    <td class="right">19.00</td>
                    <td class="right">%s</td>
                </tr>',
                htmlspecialchars($referenceCode, ENT_QUOTES),
                htmlspecialchars($designation, ENT_QUOTES),
                htmlspecialchars($quantity, ENT_QUOTES),
                $this->money($unitPrice),
                $this->money($lineTotal)
            );
        }

        $summary = $this->buildSummary($htBrut, $documentMeta, $renderWholesaleDocument);
        $createdAt = (string) ($order['created_at'] ?? '');
        $clientName = trim((string) ($documentMeta['contact_name'] ?? ''));
        if ($clientName === '') {
            $clientName = trim((string) (($client['first_name'] ?? '') . ' ' . ($client['last_name'] ?? '')));
        }
        $phone = trim((string) ($shipping['phone'] ?? ($client['phone'] ?? '')));
        $line1 = trim((string) ($documentMeta['address'] ?? ($shipping['line1'] ?? '')));
        $city = trim((string) ($documentMeta['city'] ?? ($shipping['city'] ?? '')));
        $region = trim((string) ($documentMeta['postal_code'] ?? ($shipping['region'] ?? '')));
        $clientAddress = trim($phone . '  ' . $line1);
        $clientCity = trim($city . '  ' . $region);
        $documentTitle = $renderWholesaleDocument ? 'FACTURE' : 'BON DE COMMANDE';
        $documentNumber = $renderWholesaleDocument
            ? (string) ($order['invoice_number'] ?? $order['order_number'])
            : (string) $order['order_number'];
        $amountLabel = $renderWholesaleDocument
            ? 'Arretee la Presente Facture a la somme de :'
            : 'Arrete le present bon de commande a la somme de :';

        $html = $this->renderInvoiceHtml([
            'document_title' => $documentTitle,
            'amount_label' => $amountLabel,
            'invoice_number' => $documentNumber,
            'invoice_date' => $this->formatDate((string) ($documentMeta['document_date'] ?? $createdAt)),
            'delivery_date' => $this->formatDate((string) ($documentMeta['document_date'] ?? $createdAt)),
            'client_code' => $this->clientCode($client, (int) $order['id']),
            'client_name' => $clientName !== '' ? $clientName : 'CLIENT',
            'client_address' => $clientAddress !== '' ? $clientAddress : 'TUNISIE',
            'client_city' => $clientCity !== '' ? $clientCity : 'TUNISIE',
            'mf' => (string) ($documentMeta['fiscal_code'] ?? $client['mf'] ?? '959529F/A/M/000'),
            'rows_html' => $rowsHtml,
            'summary' => $summary,
        ]);

        $options = new Options();
        $options->set('isRemoteEnabled', false);
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $response = new Response($dompdf->output());
        $filename = ($renderWholesaleDocument ? 'facture-stock-' : 'bon-commande-') . $order['order_number'] . '.pdf';
        $response->headers->set('Content-Type', 'application/pdf');
        $response->headers->set('Content-Disposition', 'attachment; filename="' . $filename . '"');

        return $response;
    }

    private function renderInvoiceHtml(array $data): string
    {
        $summary = $data['summary'];
        $rowsHtml = (string) $data['rows_html'];
        $emptyRows = '';
        for ($i = 0; $i < 4; $i++) {
            $emptyRows .= '<tr class="blank-row"><td></td><td></td><td></td><td></td><td></td><td></td><td></td><td></td></tr>';
        }

        $template = <<<'HTML'
<html>
<head>
    <meta charset="utf-8">
    <style>
        @page { margin: 8mm 8mm 8mm 8mm; }
        body { font-family: DejaVu Sans, sans-serif; color: #111; font-size: 10px; margin: 0; }
        .brand { margin: 6mm 0 5mm 4mm; font-size: 20px; font-style: italic; letter-spacing: 0.2px; }
        .client-text { margin: 0 0 5mm 4mm; width: 78mm; font-size: 10px; line-height: 1.6; }
        .client-text .code { font-weight: 700; margin-bottom: 1.5mm; }
        .client-text .name { font-size: 11px; font-style: italic; }
        table { border-collapse: collapse; width: 100%; }
        .top-layout td { vertical-align: top; }
        .box, .main-table, .tax-table, .summary-table, .stamp-table, .amount-table { border: 1px solid #8f8f8f; }
        .box td, .box th,
        .main-table td, .main-table th,
        .tax-table td, .tax-table th,
        .summary-table td, .summary-table th,
        .stamp-table td, .stamp-table th,
        .amount-table td { border: 1px solid #8f8f8f; padding: 4px 6px; }
        .box th, .main-table th, .tax-table th, .summary-table th { background: #d8d8d8; font-weight: 700; }
        .invoice-box { width: 86mm; }
        .invoice-box .title-cell { font-size: 17px; letter-spacing: 0.4px; }
        .invoice-box .number-cell { font-size: 16px; font-weight: 700; }
        .invoice-box .date-row td { height: 20px; }
        .spacer { width: 8mm; }
        .main-table { table-layout: fixed; margin-top: 2mm; }
        .main-table th { font-size: 9px; }
        .main-table td { font-size: 9px; vertical-align: top; }
        .main-table .delivery-row td { padding: 2px 4px; font-size: 8px; }
        .main-table .delivery-label { text-align: right; }
        .main-table .item-row td { height: 14px; }
        .main-table .blank-row td { height: 16px; }
        .center { text-align: center; }
        .right { text-align: right; }
        .bottom-layout { margin-top: 4mm; }
        .bottom-layout td { vertical-align: top; }
        .tax-wrap { width: 29%; }
        .summary-wrap { width: 46%; }
        .stamp-wrap { width: 25%; }
        .tax-table th, .summary-table th, .stamp-table th { font-size: 9px; }
        .tax-table td, .summary-table td, .stamp-table td { font-size: 9px; }
        .tax-table .total-head { text-align: left; }
        .summary-table th { text-align: left; width: 58%; }
        .summary-table td { width: 42%; }
        .stamp-box { height: 88px; background: #fff; }
        .amount-table { margin-top: 4px; }
        .amount-table td { padding: 5px 7px; font-size: 10px; }
        .amount-table strong { font-size: 11px; }
        .company { margin-top: 5mm; border-top: 1px solid #8f8f8f; padding-top: 3mm; text-align: center; font-size: 9px; line-height: 1.25; }
    </style>
</head>
<body>
    <div class="brand">IDENE PARFUM</div>
    <div class="client-text">
        <div class="code">Code Client : {{client_code}}</div>
        <div class="name">{{client_name}}</div>
        <div>{{client_address}}</div>
        <div>{{client_city}}</div>
        <div>M.F&nbsp;&nbsp; {{mf}}</div>
    </div>

    <table class="top-layout">
        <tr>
            <td>
                <table class="box invoice-box">
                    <tr>
                        <th class="title-cell">{{document_title}}</th>
                        <th class="number-cell">{{invoice_number}}</th>
                    </tr>
                    <tr class="date-row">
                        <td class="center"><strong>Date :</strong></td>
                        <td class="center"><strong>{{invoice_date}}</strong></td>
                    </tr>
                    <tr>
                        <td colspan="2" style="height: 34px;"></td>
                    </tr>
                </table>
            </td>
            <td class="spacer"></td>
            <td></td>
        </tr>
    </table>

    <table class="main-table">
        <thead>
            <tr>
                <th style="width: 12%;">Reference</th>
                <th style="width: 46%;">Designation</th>
                <th style="width: 6%;">Qte</th>
                <th style="width: 4%;">Fodec</th>
                <th style="width: 8%;">Prix U</th>
                <th style="width: 6%;">Rem</th>
                <th style="width: 6%;">TVA</th>
                <th style="width: 12%;">Total HT</th>
            </tr>
        </thead>
        <tbody>
            <tr class="delivery-row">
                <td></td>
                <td class="delivery-label">Livraison</td>
                <td colspan="2" class="center">Du:</td>
                <td class="center">{{delivery_date}}</td>
                <td></td>
                <td></td>
                <td></td>
            </tr>
            {{rows_html}}
            {{empty_rows}}
        </tbody>
    </table>

    <table class="bottom-layout">
        <tr>
            <td class="tax-wrap">
                <table class="tax-table">
                    <tr>
                        <th>Base T.V.A.</th>
                        <th>Taux</th>
                        <th>Mt T.V.A.</th>
                    </tr>
                    <tr>
                        <td class="right">{{base_tva}}</td>
                        <td class="center">19.00</td>
                        <td class="right">{{mt_tva}}</td>
                    </tr>
                    <tr>
                        <th colspan="2" class="total-head">Total T.V.A.</th>
                        <th class="right">{{mt_tva}}</th>
                    </tr>
                </table>
            </td>
            <td style="width: 1%;"></td>
            <td class="summary-wrap">
                <table class="summary-table">
                    <tr><th>HT BRUT:</th><td class="right">{{ht_brut}}</td></tr>
                    <tr><th>TOTAL Remise</th><td class="right">0.000</td></tr>
                    <tr><th>HT NET:</th><td class="right">{{ht_net}}</td></tr>
                    <tr><th>CICT</th><td class="right">{{cict}}</td></tr>
                    <tr><th>TOTAL TVA</th><td class="right">{{mt_tva}}</td></tr>
                    <tr><th>Timbre Fiscal</th><td class="right">{{timbre}}</td></tr>
                    <tr><th>TOTAL A PAYER</th><td class="right">{{total_to_pay}}</td></tr>
                </table>
            </td>
            <td style="width: 1%;"></td>
            <td class="stamp-wrap">
                <table class="stamp-table">
                    <tr><th>Cachet &amp; Signature</th></tr>
                    <tr>
                        <td class="stamp-box"></td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>

    <table class="amount-table">
        <tr>
            <td>{{amount_label}} <strong>{{amount_words}}</strong></td>
        </tr>
    </table>

    <div class="company">
        SOCIETE IDENE PARFUM. FABRICATION DE PARFUMS, PRODUITS COSMETIQUES ET CAPILLAIRES<br>
        RUE DES CARAVANES 2074 BORJ LOUZIR ARIANA/Tel : +216 22 275 098<br>
        MATRICULE FISCAL : 959529F/A/M/000 RC : B0126902006
    </div>
</body>
</html>
HTML;

        return strtr($template, [
            '{{document_title}}' => htmlspecialchars((string) ($data['document_title'] ?? 'FACTURE'), ENT_QUOTES),
            '{{amount_label}}' => htmlspecialchars((string) ($data['amount_label'] ?? 'Arretee la Presente Facture a la somme de :'), ENT_QUOTES),
            '{{invoice_number}}' => htmlspecialchars((string) $data['invoice_number'], ENT_QUOTES),
            '{{invoice_date}}' => htmlspecialchars((string) $data['invoice_date'], ENT_QUOTES),
            '{{delivery_date}}' => htmlspecialchars((string) $data['delivery_date'], ENT_QUOTES),
            '{{client_code}}' => htmlspecialchars((string) $data['client_code'], ENT_QUOTES),
            '{{client_name}}' => htmlspecialchars((string) $data['client_name'], ENT_QUOTES),
            '{{client_address}}' => htmlspecialchars((string) $data['client_address'], ENT_QUOTES),
            '{{client_city}}' => htmlspecialchars((string) $data['client_city'], ENT_QUOTES),
            '{{mf}}' => htmlspecialchars((string) $data['mf'], ENT_QUOTES),
            '{{rows_html}}' => $rowsHtml,
            '{{empty_rows}}' => $emptyRows,
            '{{base_tva}}' => $this->money($summary['base_tva']),
            '{{mt_tva}}' => $this->money($summary['mt_tva']),
            '{{ht_brut}}' => $this->money($summary['ht_brut']),
            '{{ht_net}}' => $this->money($summary['ht_net']),
            '{{cict}}' => $this->money($summary['cict']),
            '{{timbre}}' => $this->money($summary['timbre']),
            '{{total_to_pay}}' => $this->money($summary['total_to_pay']),
            '{{amount_words}}' => htmlspecialchars($this->spellAmountFr($summary['total_to_pay']), ENT_QUOTES),
        ]);
    }

    private function buildSummary(float $htBrut, array $documentMeta = [], bool $isWholesale = false): array
    {
        $totalsMeta = is_array($documentMeta['totals'] ?? null) ? $documentMeta['totals'] : [];

        if ($totalsMeta !== []) {
            return [
                'ht_brut' => (float) ($totalsMeta['subtotal'] ?? $htBrut),
                'ht_net' => (float) ($totalsMeta['htNet'] ?? $htBrut),
                'cict' => (float) ($totalsMeta['cict'] ?? 0),
                'base_tva' => (float) ($totalsMeta['baseTva'] ?? 0),
                'mt_tva' => (float) ($totalsMeta['tva'] ?? 0),
                'timbre' => (float) ($totalsMeta['timbre'] ?? self::TIMBRE),
                'total_to_pay' => (float) ($totalsMeta['totalToPay'] ?? $htBrut),
            ];
        }

        $totalToPay = $htBrut;
        $mtTva = $totalToPay * self::TVA_RATE;
        $htNet = max(0.0, $totalToPay - $mtTva);
        $cict = $totalToPay * self::CICT_RATE;
        $baseTva = $htNet + $cict;

        return [
            'ht_brut' => $totalToPay,
            'ht_net' => $htNet,
            'cict' => $cict,
            'base_tva' => $baseTva,
            'mt_tva' => $mtTva,
            'timbre' => self::TIMBRE,
            'total_to_pay' => $totalToPay,
        ];
    }

    private function clientCode(array $client, int $fallback): string
    {
        $documentMeta = is_array($client['document_meta'] ?? null) ? $client['document_meta'] : [];
        $code = $documentMeta['client_code']
            ?? $client['client_code']
            ?? $client['code_client']
            ?? $client['customer_code']
            ?? $fallback;

        return str_pad((string) $code, 3, '0', STR_PAD_LEFT);
    }

    private function formatDate(string $value): string
    {
        if (preg_match('/^\d{4}-\d{2}-\d{2}/', $value) === 1) {
            return substr($value, 8, 2) . '/' . substr($value, 5, 2) . '/' . substr($value, 0, 4);
        }

        if ($value !== '') {
            $timestamp = strtotime($value);
            if ($timestamp !== false) {
                return date('d/m/Y', $timestamp);
            }
        }

        return $value !== '' ? $value : date('d/m/Y');
    }

    private function quantity(float $value): string
    {
        if (abs($value - round($value)) < 0.0001) {
            return (string) (int) round($value);
        }

        return $this->money($value);
    }

    private function spellAmountFr(float $amount): string
    {
        $dinars = (int) floor($amount);
        $millimes = (int) round(($amount - $dinars) * 1000);

        return ucfirst($this->spellNumberFr($dinars)) . ' Dinar(s) ' . sprintf('%03d', $millimes) . ' Millime(s)';
    }

    private function spellNumberFr(int $number): string
    {
        if ($number === 0) {
            return 'zero';
        }

        if ($number < 0) {
            return 'moins ' . $this->spellNumberFr(abs($number));
        }

        $parts = [];
        $millions = intdiv($number, 1000000);
        if ($millions > 0) {
            $parts[] = $millions === 1 ? 'un million' : $this->spellUnderThousand($millions) . ' millions';
            $number %= 1000000;
        }

        $thousands = intdiv($number, 1000);
        if ($thousands > 0) {
            $parts[] = $thousands === 1 ? 'mille' : $this->spellUnderThousand($thousands) . ' mille';
            $number %= 1000;
        }

        if ($number > 0) {
            $parts[] = $this->spellUnderThousand($number);
        }

        return trim(implode(' ', $parts));
    }

    private function spellUnderThousand(int $number): string
    {
        $units = [
            0 => 'zero', 1 => 'un', 2 => 'deux', 3 => 'trois', 4 => 'quatre',
            5 => 'cinq', 6 => 'six', 7 => 'sept', 8 => 'huit', 9 => 'neuf',
            10 => 'dix', 11 => 'onze', 12 => 'douze', 13 => 'treize', 14 => 'quatorze',
            15 => 'quinze', 16 => 'seize', 17 => 'dix-sept', 18 => 'dix-huit', 19 => 'dix-neuf',
        ];
        $tens = [
            20 => 'vingt', 30 => 'trente', 40 => 'quarante', 50 => 'cinquante',
            60 => 'soixante', 80 => 'quatre-vingt',
        ];

        $words = [];
        $hundreds = intdiv($number, 100);
        $rest = $number % 100;

        if ($hundreds > 0) {
            if ($hundreds === 1) {
                $words[] = 'cent';
            } else {
                $words[] = $units[$hundreds] . ' cent';
            }
        }

        if ($rest > 0) {
            $words[] = $this->spellUnderHundred($rest, $units, $tens);
        }

        return trim(implode(' ', $words));
    }

    private function spellUnderHundred(int $number, array $units, array $tens): string
    {
        if ($number < 20) {
            return $units[$number];
        }

        if ($number < 70) {
            $ten = intdiv($number, 10) * 10;
            $unit = $number % 10;
            if ($unit === 0) {
                return $tens[$ten];
            }
            if ($unit === 1) {
                return $tens[$ten] . ' et un';
            }

            return $tens[$ten] . '-' . $units[$unit];
        }

        if ($number < 80) {
            if ($number === 71) {
                return 'soixante et onze';
            }

            return 'soixante-' . $this->spellUnderHundred($number - 60, $units, $tens);
        }

        if ($number === 80) {
            return 'quatre-vingts';
        }

        return 'quatre-vingt-' . $this->spellUnderHundred($number - 80, $units, $tens);
    }

    private function money(float $amount): string
    {
        return number_format($amount, 3, '.', '');
    }
}
