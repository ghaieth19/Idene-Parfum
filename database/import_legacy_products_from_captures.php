<?php

declare(strict_types=1);

$root = dirname(__DIR__);
$envPath = $root . DIRECTORY_SEPARATOR . '.env';

if (!is_file($envPath)) {
    fwrite(STDERR, "Missing .env file.\n");
    exit(1);
}

$env = parseEnvFile($envPath);
$host = $env['DB_HOST'] ?? '127.0.0.1';
$port = $env['DB_PORT'] ?? '3306';
$name = $env['DB_NAME'] ?? 'idene_parfum';
$charset = $env['DB_CHARSET'] ?? 'utf8mb4';
$user = $env['DB_USER'] ?? 'root';
$password = $env['DB_PASSWORD'] ?? '';

$dsn = sprintf('mysql:host=%s;port=%s;dbname=%s;charset=%s', $host, $port, $name, $charset);
$pdo = new PDO($dsn, $user, $password, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
]);

$products = legacyProducts();

$catalogCount = (int) $pdo->query('SELECT COUNT(*) FROM perfume_catalog')->fetchColumn();
if ($catalogCount < 209) {
    fwrite(STDERR, "The current database must already contain at least 209 catalog rows.\n");
    exit(1);
}

$updateCatalog = $pdo->prepare(
    "UPDATE perfume_catalog
     SET catalog_group = :catalog_group,
         segment = :segment,
         code = :code,
         name = :name,
         source_label = :source_label,
         is_active = 1
     WHERE id = :id"
);

$updateProduct = $pdo->prepare(
    "UPDATE products
     SET sku = :sku,
         is_active = 1
     WHERE perfume_catalog_id = :catalog_id"
);

$ensureProduct = $pdo->prepare(
    "INSERT INTO products (perfume_catalog_id, sku, is_active)
     SELECT :catalog_id, :sku, 1
     FROM DUAL
     WHERE NOT EXISTS (
         SELECT 1 FROM products WHERE perfume_catalog_id = :catalog_id_check
     )"
);

$ensureStock = $pdo->prepare(
    "INSERT INTO stock (product_id, quantity_ml, min_alert_ml, raw_material_quantity_ml, raw_material_min_alert_ml)
     SELECT :product_id, 0, 0, 0, 0
     FROM DUAL
     WHERE NOT EXISTS (
         SELECT 1 FROM stock WHERE product_id = :product_id_check
     )"
);

$findProductId = $pdo->prepare('SELECT id FROM products WHERE perfume_catalog_id = :catalog_id LIMIT 1');

$pdo->beginTransaction();
try {
    foreach ($products as $product) {
        [$catalogGroup, $segment] = inferCatalogMeta($product['name'], $product['source_label']);
        $code = (string) $product['code'];
        $sku = sprintf('ART-%03d', (int) $product['code']);

        $updateCatalog->execute([
            'catalog_group' => $catalogGroup,
            'segment' => $segment,
            'code' => $code,
            'name' => $product['name'],
            'source_label' => $product['source_label'],
            'id' => (int) $product['code'],
        ]);

        $ensureProduct->execute([
            'catalog_id' => (int) $product['code'],
            'sku' => $sku,
            'catalog_id_check' => (int) $product['code'],
        ]);

        $updateProduct->execute([
            'sku' => $sku,
            'catalog_id' => (int) $product['code'],
        ]);

        $findProductId->execute(['catalog_id' => (int) $product['code']]);
        $productId = (int) $findProductId->fetchColumn();
        if ($productId > 0) {
            $ensureStock->execute([
                'product_id' => $productId,
                'product_id_check' => $productId,
            ]);
        }
    }

    $pdo->exec('UPDATE perfume_catalog SET is_active = 0 WHERE id > 209');
    $pdo->exec('UPDATE products SET is_active = 0 WHERE perfume_catalog_id > 209');

    $pdo->commit();
} catch (Throwable $e) {
    $pdo->rollBack();
    fwrite(STDERR, $e->getMessage() . "\n");
    exit(1);
}

echo "Imported legacy products from captures.\n";
echo "Products mapped: " . count($products) . "\n";
echo "Active catalog rows 1..209 refreshed and rows >209 disabled.\n";

function parseEnvFile(string $path): array
{
    $values = [];
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [];
    foreach ($lines as $line) {
        $trimmed = trim($line);
        if ($trimmed === '' || str_starts_with($trimmed, '#') || !str_contains($trimmed, '=')) {
            continue;
        }

        [$key, $value] = explode('=', $trimmed, 2);
        $values[trim($key)] = trim($value, " \t\n\r\0\x0B\"'");
    }

    return $values;
}

function inferCatalogMeta(string $name, string $sourceLabel): array
{
    $upper = mb_strtoupper($name . ' ' . $sourceLabel);

    if (
        str_contains($upper, 'ENFANT')
        || str_contains($upper, 'CHILDREN')
        || str_contains($upper, 'BEN 10')
        || str_contains($upper, 'BARBY')
    ) {
        return ['ENFANT', 'ENFANT'];
    }

    if (
        str_contains($upper, 'LUXE')
        || str_contains($upper, 'PARFUM LUX')
    ) {
        return ['LUXE', 'MIXTE'];
    }

    if (
        str_contains($upper, 'SMART')
        || str_contains($upper, 'BOUTEILLES')
        || str_contains($upper, '1L')
        || str_contains($upper, 'FIXATEUR')
        || str_contains($upper, 'AIR FRAICHE')
    ) {
        return ['SMART', 'UNISEX'];
    }

    if (str_contains($upper, 'HOMME')) {
        return ['PRINCIPAL', 'HOMME'];
    }

    if (str_contains($upper, 'FEMME')) {
        return ['PRINCIPAL', 'FEMME'];
    }

    return ['PRINCIPAL', 'MIXTE'];
}

function legacyProducts(): array
{
    return [
        ['code' => 1, 'name' => 'ACQUA', 'source_label' => 'E.D.T VRAC ACQUA'],
        ['code' => 2, 'name' => 'MARCHMELOW', 'source_label' => 'E.D.T VRAC MARCHMELOW'],
        ['code' => 3, 'name' => 'CASA', 'source_label' => 'E.D.T VRAC CASA'],
        ['code' => 4, 'name' => 'C K 1', 'source_label' => 'E.D.T VRAC C K 1'],
        ['code' => 5, 'name' => 'DAVID FOFF', 'source_label' => 'E.D.T VRAC DAVID FOFF'],
        ['code' => 6, 'name' => 'PERGOLESE', 'source_label' => 'E.D.T VRAC PERGOLESE'],
        ['code' => 7, 'name' => '1881 (HOMME)', 'source_label' => 'E.D.T VRAC 1881 (HOMME)'],
        ['code' => 8, 'name' => '1881 (FEMME)', 'source_label' => 'E.D.T VRAC 1881 (FEMME)'],
        ['code' => 9, 'name' => '1L (enfants)', 'source_label' => 'E.D.T VRAC 1L (enfants)'],
        ['code' => 10, 'name' => 'SECRET DE VICTORIA', 'source_label' => 'E.D.T VRAC SECRET DE VICTORIA'],
        ['code' => 11, 'name' => '212 (HOMME)', 'source_label' => 'E.D.T VRAC 212 (HOMME)'],
        ['code' => 12, 'name' => 'ANGEL', 'source_label' => 'E.D.T VRAC ANGEL'],
        ['code' => 13, 'name' => 'DOVE', 'source_label' => 'E.D.T VRAC DOVE'],
        ['code' => 14, 'name' => 'DRAKKAR', 'source_label' => 'E.D.T VRAC DRAKKAR'],
        ['code' => 15, 'name' => 'AMIR ELOUD', 'source_label' => 'E.D.T VRAC AMIR ELOUD'],
        ['code' => 16, 'name' => 'HUGO BOSS', 'source_label' => 'E.D.T VRAC HUGO BOSS'],
        ['code' => 17, 'name' => 'IDOLE POWER', 'source_label' => 'E.D.T VRAC IDOLE POWER'],
        ['code' => 18, 'name' => 'BOSS', 'source_label' => 'E.D.T VRAC BOSS'],
        ['code' => 19, 'name' => 'LCOSTE BLANC', 'source_label' => 'E.D.T VRAC LCOSTE BLANC'],
        ['code' => 20, 'name' => 'JADORE', 'source_label' => 'E.D.T VRAC JADORE'],
        ['code' => 21, 'name' => 'KENZO (HOMME)', 'source_label' => 'E.D.T VRAC KENZO (HOMME)'],
        ['code' => 22, 'name' => 'KENZO FLOWER', 'source_label' => 'E.D.T VRAC KENZO FLOWER'],
        ['code' => 23, 'name' => 'GLAMOUR', 'source_label' => 'E.D.T VRAC GLAMOUR'],
        ['code' => 24, 'name' => 'ROMA', 'source_label' => 'E.D.T VRAC ROMA'],
        ['code' => 25, 'name' => 'BACARATE ROUGE', 'source_label' => 'E.D.T VRAC BACARATE ROUGE'],
        ['code' => 26, 'name' => 'TERRE DAMAS', 'source_label' => 'E.D.T VRAC TERRE DAMAS'],
        ['code' => 27, 'name' => 'WACWACHA (FEMME)', 'source_label' => 'E.D.T VRAC WACWACHA (FEMME)'],
        ['code' => 28, 'name' => "PARFUM D'OR", 'source_label' => "E.D.T VRAC PARFUM D'OR"],
        ['code' => 29, 'name' => 'TOP', 'source_label' => 'E.D.T VRAC TOP'],
        ['code' => 30, 'name' => 'EAU LAMPIA', 'source_label' => 'E.D.T VRAC EAU LAMPIA'],
        ['code' => 31, 'name' => 'GOOD GIRL', 'source_label' => 'E.D.T VRAC GOOD GIRL'],
        ['code' => 32, 'name' => 'AZZARO', 'source_label' => 'E.D.T VRAC AZZARO'],
        ['code' => 33, 'name' => 'CIGAR', 'source_label' => 'E.D.T VRAC CIGAR'],
        ['code' => 34, 'name' => 'BRUT', 'source_label' => 'E.D.T VRAC BRUT'],
        ['code' => 35, 'name' => 'KALIMET', 'source_label' => 'E.D.T VRAC KALIMET'],
        ['code' => 36, 'name' => 'VICTORIA INTENSE', 'source_label' => 'E.D.T VRAC VICTORIA INTENSE'],
        ['code' => 37, 'name' => 'GUCCI RACH', 'source_label' => 'E.D.T VRAC GUCCI RACH'],
        ['code' => 38, 'name' => 'TRESOR ROSE', 'source_label' => 'E.D.T VRAC TRESOR ROSE'],
        ['code' => 39, 'name' => 'GI GI MEN', 'source_label' => 'E.D.T VRAC GI GI MEN'],
        ['code' => 40, 'name' => 'GOLF', 'source_label' => 'E.D.T VRAC GOLF'],
        ['code' => 41, 'name' => 'BOURGEOI', 'source_label' => 'E.D.T VRAC BOURGEOI'],
        ['code' => 42, 'name' => 'NARCISO NOIR', 'source_label' => 'E.D.T VRAC NARCISO NOIR'],
        ['code' => 43, 'name' => 'FREENEIGHT', 'source_label' => 'E.D.T VRAC FREENEIGHT'],
        ['code' => 44, 'name' => 'ADICT', 'source_label' => 'E.D.T VRAC ADICT'],
        ['code' => 45, 'name' => 'GHOBAR EDHAHEB', 'source_label' => 'E.D.T VRAC GHOBAR EDHAHEB'],
        ['code' => 46, 'name' => "L'INSTANT", 'source_label' => "E.D.T VRAC L'INSTANT"],
        ['code' => 47, 'name' => 'ALLURE SPORT', 'source_label' => 'E.D.T VRAC ALLURE SPORT'],
        ['code' => 48, 'name' => 'KAYALI VANILLE', 'source_label' => 'E.D.T VRAC KAYALI VANILLE'],
        ['code' => 49, 'name' => 'BLEU TERQUOISE', 'source_label' => 'E.D.T VRAC BLEU TERQUOISE'],
        ['code' => 50, 'name' => 'DIAMONTA', 'source_label' => 'E.D.T VRAC DIAMONTA'],
        ['code' => 51, 'name' => "L'INCIDENCE (FEMME)", 'source_label' => "E.D.T VRAC L'INCIDENCE (FEMME)"],
        ['code' => 52, 'name' => 'NINA POMME', 'source_label' => 'E.D.T VRAC NINA POMME'],
        ['code' => 53, 'name' => 'CHROM ADZARO', 'source_label' => 'E.D.T VRAC CHROM ADZARO'],
        ['code' => 54, 'name' => 'BLACK XS (HOMME)', 'source_label' => 'E.D.T VRAC BLACK XS (HOMME)'],
        ['code' => 55, 'name' => 'ADIDAS CLASSIC', 'source_label' => 'E.D.T VRAC ADIDAS CLASSIC'],
        ['code' => 56, 'name' => 'DANHIL DESIRE', 'source_label' => 'E.D.T VRAC DANHIL DESIRE'],
        ['code' => 57, 'name' => 'ESCADA MOON', 'source_label' => 'E.D.T VRAC ESCADA MOON'],
        ['code' => 58, 'name' => 'GUCCI BAMBO', 'source_label' => 'E.D.T VRAC GUCCI BAMBO'],
        ['code' => 59, 'name' => 'MAXIMUM', 'source_label' => 'E.D.T VRAC MAXIMUM'],
        ['code' => 60, 'name' => 'COCO POUR ELLE', 'source_label' => 'E.D.T VRAC COCO POUR ELLE'],
        ['code' => 61, 'name' => 'SCANDAL Femme', 'source_label' => 'E.D.T VRAC SCANDAL Femme'],
        ['code' => 62, 'name' => 'ONE MILLION', 'source_label' => 'E.D.T VRAC ONE MILLION'],
        ['code' => 63, 'name' => 'YOU homme', 'source_label' => 'E.D.T VRAC YOU homme'],
        ['code' => 64, 'name' => 'LA BELLE VIE', 'source_label' => 'E.D.T VRAC LA BELLE VIE'],
        ['code' => 65, 'name' => 'DOL & GABAN A', 'source_label' => 'E.D.T VRAC DOL & GABAN A'],
        ['code' => 66, 'name' => 'FANTAISIE', 'source_label' => 'E.D.T VRAC FANTAISIE'],
        ['code' => 67, 'name' => 'FLORA', 'source_label' => 'E.D.T VRAC FLORA'],
        ['code' => 68, 'name' => 'MISS CHERIE', 'source_label' => 'E.D.T VRAC MISS CHERIE'],
        ['code' => 69, 'name' => 'HYPNOSE', 'source_label' => 'E.D.T VRAC HYPNOSE'],
        ['code' => 70, 'name' => 'ESCADA PARADAISE', 'source_label' => 'E.D.T VRAC ESCADA PARADAISE'],
        ['code' => 71, 'name' => 'CHER-MEN', 'source_label' => 'E.D.T VRAC CHER-MEN'],
        ['code' => 72, 'name' => 'CANDY', 'source_label' => 'E.D.T VRAC CANDY'],
        ['code' => 73, 'name' => 'GENTELMAN', 'source_label' => 'E.D.T VRAC GENTELMAN'],
        ['code' => 74, 'name' => 'CAMMAYA', 'source_label' => 'E.D.T VRAC CAMMAYA'],
        ['code' => 75, 'name' => 'BLACK OPIUM', 'source_label' => 'E.D.T VRAC BLACK OPIUM'],
        ['code' => 76, 'name' => 'GHARAM', 'source_label' => 'E.D.T VRAC GHARAM'],
        ['code' => 77, 'name' => 'SIVER WESKY', 'source_label' => 'E.D.T VRAC SIVER WESKY'],
        ['code' => 78, 'name' => 'BLEU DE CHARNEL', 'source_label' => 'E.D.T VRAC BLEU DE CHARNEL'],
        ['code' => 79, 'name' => 'BERBERIE HER', 'source_label' => 'E.D.T VRAC BERBERIE HER'],
        ['code' => 80, 'name' => 'COUCOU TOI', 'source_label' => 'E.D.T VRAC COUCOU TOI'],
        ['code' => 81, 'name' => 'MY WAY FEMME', 'source_label' => 'E.D.T VRAC MY WAY FEMME'],
        ['code' => 82, 'name' => 'CLASS', 'source_label' => 'E.D.T VRAC CLASS'],
        ['code' => 83, 'name' => 'BLUE JEANS', 'source_label' => 'E.D.T VRAC BLUE JEANS'],
        ['code' => 84, 'name' => 'JOLI', 'source_label' => 'E.D.T VRAC JOLI'],
        ['code' => 85, 'name' => 'EVIDENCE', 'source_label' => 'E.D.T VRAC EVIDENCE'],
        ['code' => 86, 'name' => "L'INCIDENCE (HOMME)", 'source_label' => "E.D.T VRAC L'INCIDENCE (HOMME)"],
        ['code' => 87, 'name' => 'AXE CHOCOLAT', 'source_label' => 'E.D.T VRAC AXE CHOCOLAT'],
        ['code' => 88, 'name' => 'DANHIL fresh', 'source_label' => 'E.D.T VRAC DANHIL fresh'],
        ['code' => 89, 'name' => 'SILVER', 'source_label' => 'E.D.T VRAC SILVER'],
        ['code' => 90, 'name' => 'EROS', 'source_label' => 'E.D.T VRAC EROS'],
        ['code' => 91, 'name' => 'CHLOE (FEMME)', 'source_label' => 'E.D.T VRAC CHLOE (FEMME)'],
        ['code' => 92, 'name' => 'FRANKO OUD', 'source_label' => 'E.D.T VRAC FRANKO OUD'],
        ['code' => 93, 'name' => 'ANA ELABYADH', 'source_label' => 'E.D.T VRAC ANA ELABYADH'],
        ['code' => 94, 'name' => 'LOLITA', 'source_label' => 'E.D.T VRAC LOLITA'],
        ['code' => 95, 'name' => 'GUILTY MEN', 'source_label' => 'E.D.T VRAC GUILTY MEN'],
        ['code' => 96, 'name' => 'AMIRET ELABAB', 'source_label' => 'E.D.T VRAC AMIRET ELABAB'],
        ['code' => 97, 'name' => 'MANIFESTO', 'source_label' => 'E.D.T VRAC MANIFESTO'],
        ['code' => 98, 'name' => 'PI', 'source_label' => 'E.D.T VRAC PI'],
        ['code' => 99, 'name' => 'HYPNOTIC DE POISON', 'source_label' => 'E.D.T VRAC HYPNOTIC DE POISON'],
        ['code' => 100, 'name' => 'DIOR INTENSE', 'source_label' => 'E.D.T VRAC DIOR INTENSE'],
        ['code' => 101, 'name' => 'GUESS', 'source_label' => 'E.D.T VRAC GUESS'],
        ['code' => 102, 'name' => 'IDYLE', 'source_label' => 'E.D.T VRAC IDYLE'],
        ['code' => 103, 'name' => 'ESSENTIEL', 'source_label' => 'E.D.T VRAC ESSENTIEL'],
        ['code' => 104, 'name' => "L'INTERDIT FEMME", 'source_label' => "E.D.T VRAC L'INTERDIT FEMME"],
        ['code' => 105, 'name' => 'HAREM', 'source_label' => 'E.D.T VRAC HAREM'],
        ['code' => 106, 'name' => 'IDOLE', 'source_label' => 'E.D.T VRAC IDOLE'],
        ['code' => 107, 'name' => "REVE D'OR", 'source_label' => "E.D.T REVE D'OR"],
        ['code' => 108, 'name' => 'SMALTO', 'source_label' => 'E.D.T SMALTO'],
        ['code' => 109, 'name' => 'COCO VANILLE', 'source_label' => 'E.D.T COCO VANILLE'],
        ['code' => 110, 'name' => 'AMIR EL ARAB', 'source_label' => 'E.D.T AMIR EL ARAB'],
        ['code' => 111, 'name' => 'VICTORIA PASSION', 'source_label' => 'E.D.T VICTORIA PASSION'],
        ['code' => 112, 'name' => 'CARTIER', 'source_label' => 'E.D.T CARTIER'],
        ['code' => 113, 'name' => 'LIBRE femme', 'source_label' => 'E.D.T LIBRE femme'],
        ['code' => 114, 'name' => 'MALAKITE ROUGE', 'source_label' => 'EDT VRAC MALAKITE ROUGE'],
        ['code' => 115, 'name' => 'AMWAJ', 'source_label' => 'E.D.T VRAC AMWAJ'],
        ['code' => 116, 'name' => 'SHALISE', 'source_label' => 'E.D.T VRAC SHALISE'],
        ['code' => 117, 'name' => 'DIABLE BLEU', 'source_label' => 'E.D.T VRAC DIABLE BLEU'],
        ['code' => 118, 'name' => 'LACOSTE BLACK', 'source_label' => 'E.D.T VRAC LACOSTE BLACK'],
        ['code' => 119, 'name' => 'ALEIN', 'source_label' => 'E.D.T VRAC ALEIN'],
        ['code' => 120, 'name' => 'GUCCI BLOOM', 'source_label' => 'E.D.T.VRAC GUCCI BLOOM'],
        ['code' => 121, 'name' => 'LA ROBE NOIRE', 'source_label' => 'E.D.T.VRAC LA ROBE NOIRE'],
        ['code' => 122, 'name' => 'ROSE VANILLE', 'source_label' => 'E.D.T.VRAC ROSE VANILLE'],
        ['code' => 123, 'name' => 'ONE MILLION ELLIXIR', 'source_label' => 'E.D.T.VRAC ONE MILLION ELLIXIR'],
        ['code' => 124, 'name' => 'EXTRAIT PARFUM', 'source_label' => 'EXTRAIT PARFUM'],
        ['code' => 125, 'name' => 'SAUVAGE (ROUGE)', 'source_label' => 'E D T SAUVAGE (ROUGE)'],
        ['code' => 126, 'name' => 'MALAKITE VERT', 'source_label' => 'E D T VRAC MALAKITE VERT'],
        ['code' => 127, 'name' => 'ALLURE ESSENTIEL', 'source_label' => 'E D T ALLURE ESSENTIEL'],
        ['code' => 128, 'name' => 'FIXATEUR', 'source_label' => 'FIXATEUR'],
        ['code' => 129, 'name' => 'AIR FRAICHE', 'source_label' => 'AIR FRAICHE'],
        ['code' => 130, 'name' => 'INTERDIT HOMME', 'source_label' => 'E.D.T VRAC INTERDIT HOMME'],
        ['code' => 131, 'name' => 'MY WAY HOMME', 'source_label' => 'E.D.T VRAC MY WAY HOMME'],
        ['code' => 132, 'name' => 'CASH-MUR', 'source_label' => 'E.D.T.VRAC CASH-MUR'],
        ['code' => 133, 'name' => 'ANAIS', 'source_label' => 'E.D.T.VRAC ANAIS'],
        ['code' => 134, 'name' => 'THE MOLT', 'source_label' => 'E.D.T.VRAC THE MOLT'],
        ['code' => 135, 'name' => 'RED PERL', 'source_label' => 'E.D.T.VRAC RED PERL'],
        ['code' => 136, 'name' => 'CARIRA HOMME', 'source_label' => 'E.D.T.VRAC CARIRA HOMME'],
        ['code' => 137, 'name' => 'VRAC 1 L DOUBLE BASE', 'source_label' => 'VRAC 1 L DOUBLE BASE'],
        ['code' => 138, 'name' => 'FA', 'source_label' => 'E.D.T.VRAC FA'],
        ['code' => 139, 'name' => 'BIEN ETRE', 'source_label' => 'E.D.T.VRAC BIEN ETRE'],
        ['code' => 140, 'name' => 'LAVANDE', 'source_label' => 'E.D.T.VRAC LAVANDE'],
        ['code' => 141, 'name' => 'INVECTUS', 'source_label' => 'E.D.T VRAC INVECTUS'],
        ['code' => 142, 'name' => 'EAU DC', 'source_label' => 'E.D.T VRAC EAU DC'],
        ['code' => 143, 'name' => 'BOSS WHITE', 'source_label' => 'E.D.T VRAC BOSS WHITE'],
        ['code' => 144, 'name' => 'LACOSTE GRIS', 'source_label' => 'E.D.T VRAC LACOSTE GRIS'],
        ['code' => 145, 'name' => 'SI', 'source_label' => 'E.D.T VRAC SI'],
        ['code' => 146, 'name' => 'UDV BLEU', 'source_label' => 'E.D.T VRAC UDV BLEU'],
        ['code' => 147, 'name' => '5 eme AV', 'source_label' => 'E.D.T VRAC 5 eme AV'],
        ['code' => 148, 'name' => 'YARA', 'source_label' => 'E.D.T.VRAC YARA'],
        ['code' => 149, 'name' => 'UDV ROSE', 'source_label' => 'E.D.T. VRAC UDV ROSE'],
        ['code' => 150, 'name' => 'GUCCI OUD', 'source_label' => 'E.D.T VRAC GUCCI OUD'],
        ['code' => 151, 'name' => 'TEMPS FORD', 'source_label' => 'E.D.T VRAC TEMPS FORD'],
        ['code' => 152, 'name' => 'BOSS ORANGE H', 'source_label' => 'E.D.T. VRAC BOSS ORANGE H'],
        ['code' => 153, 'name' => 'PLAISIR', 'source_label' => 'E.D.T. VRAC PLAISIR'],
        ['code' => 154, 'name' => 'NINA JAUNE', 'source_label' => 'E.D.T VRAC NINA JAUNE'],
        ['code' => 155, 'name' => 'ANDRA', 'source_label' => 'E.D.T VRAC ANDRA'],
        ['code' => 156, 'name' => 'PURE XS', 'source_label' => 'E.D.T VRAC PURE XS'],
        ['code' => 157, 'name' => 'SCANDALE', 'source_label' => 'E.D.T VRAC SCANDALE'],
        ['code' => 158, 'name' => 'BARBARA', 'source_label' => 'E.D.T VRAC BARBARA'],
        ['code' => 159, 'name' => 'STRONGER WITH ME', 'source_label' => 'E.D.T VRAC STRONGER WITH ME'],
        ['code' => 160, 'name' => 'CHANEL 5', 'source_label' => 'E.D.T VRAC CHANEL 5'],
        ['code' => 161, 'name' => 'MALIZIA', 'source_label' => 'E.D.T VRAC MALIZIA'],
        ['code' => 162, 'name' => 'DECLARATION', 'source_label' => 'E.D.T VRAC DECLARATION'],
        ['code' => 163, 'name' => 'AVIATOR', 'source_label' => 'E.D.T VRAC AVIATOR'],
        ['code' => 164, 'name' => 'BARBY', 'source_label' => 'E.D.T. VRAC BARBY'],
        ['code' => 165, 'name' => 'ELLI SAIB', 'source_label' => 'E.D.T VRAC ELLI SAIB'],
        ['code' => 166, 'name' => 'VICTORIA VANILLE', 'source_label' => 'E.D.T VICTORIA VANILLE'],
        ['code' => 167, 'name' => '4*4', 'source_label' => 'E D T 4*4'],
        ['code' => 168, 'name' => 'MEN EXTREME', 'source_label' => 'E.D.T VRAC MEN EXTREME'],
        ['code' => 169, 'name' => 'SAUVAGE', 'source_label' => 'E.D.T VRAC SAUVAGE'],
        ['code' => 170, 'name' => 'NIVEA', 'source_label' => 'E.D.T VRAC NIVEA'],
        ['code' => 171, 'name' => 'OUD VANILLE', 'source_label' => 'E.D.T VRAC OUD VANILLE'],
        ['code' => 172, 'name' => 'SI FLEURI', 'source_label' => 'E.D.T VRAC SI FLEURI'],
        ['code' => 173, 'name' => 'DEFI', 'source_label' => 'E.D.T VRAC DEFI'],
        ['code' => 174, 'name' => 'FOLLA (enfants)', 'source_label' => 'E.D.T VRAC FOLLA (enfants)'],
        ['code' => 175, 'name' => 'MUSK TAHARA', 'source_label' => 'E.D.T VRAC MUSK TAHARA'],
        ['code' => 176, 'name' => 'MY BEST FRIEND ( BEN 10 )', 'source_label' => 'E.D.T MY BEST FRIEND ( BEN 10 )'],
        ['code' => 177, 'name' => 'MY BEST FRIEND ( BARBY )', 'source_label' => 'E.D.T MY BEST FRIEND ( BARBY )'],
        ['code' => 178, 'name' => 'EGOISME', 'source_label' => 'E.D.T VRAC EGOISME'],
        ['code' => 179, 'name' => 'UDV GRIS', 'source_label' => 'E.D.T VRAC. UDV GRIS'],
        ['code' => 180, 'name' => 'DUNHIL FRESH', 'source_label' => 'E.D.T VRAC DUNHIL FRESH'],
        ['code' => 181, 'name' => 'CHANCE CHANEL', 'source_label' => 'E.D.T VRAC CHANCE CHANEL'],
        ['code' => 182, 'name' => 'EXTRAIT PARFUM (LUXE)', 'source_label' => 'EXTRAIT PARFUM (LUXE)'],
        ['code' => 183, 'name' => 'JUST CAVALIER ( HOMME)', 'source_label' => 'E.D.T JUST CAVALIER ( HOMME)'],
        ['code' => 184, 'name' => 'WANTED', 'source_label' => 'E.D.T WANTED'],
        ['code' => 185, 'name' => 'LACOSTE BLEU', 'source_label' => 'E.D.T VRAC. LACOSTE BLEU'],
        ['code' => 186, 'name' => 'HUGO EXTREM', 'source_label' => 'E.D.T VRAC. HUGO EXTREM'],
        ['code' => 187, 'name' => 'ARMANI CASHMIR', 'source_label' => 'E.D.T VRAC. ARMANI CASHMIR'],
        ['code' => 188, 'name' => 'ONE MILLION PRIVE', 'source_label' => 'E.D.T VRAC. ONE MILLION PRIVE'],
        ['code' => 189, 'name' => 'My BERBERY', 'source_label' => 'E.D.T VRAC My BERBERY'],
        ['code' => 190, 'name' => 'MON PARIS', 'source_label' => 'E.D.T VRAC MON PARIS'],
        ['code' => 191, 'name' => 'NUIT DE TRESOR', 'source_label' => 'E.D.T VRAC NUIT DE TRESOR'],
        ['code' => 192, 'name' => 'HUGO EXTREM femme', 'source_label' => 'E.D.T. VRAC HUGO EXTREM femme'],
        ['code' => 193, 'name' => 'ANGEL MUSE', 'source_label' => 'E.D.T. VRAC ANGEL MUSE'],
        ['code' => 194, 'name' => 'HOBBI', 'source_label' => 'E.D.T VRAC. HOBBI'],
        ['code' => 195, 'name' => 'CREED', 'source_label' => 'E.D.T VRAC CREED'],
        ['code' => 196, 'name' => 'MESA', 'source_label' => 'E.D.T VRAC MESA'],
        ['code' => 197, 'name' => 'Bouteilles 90ml', 'source_label' => 'Bouteilles 90ml'],
        ['code' => 198, 'name' => 'Bouteilles 100ml', 'source_label' => 'Bouteilles 100ml'],
        ['code' => 199, 'name' => 'Bouteilles50ml', 'source_label' => 'Bouteilles50ml'],
        ['code' => 200, 'name' => 'PAQUET SMART 15 x 15 ml', 'source_label' => 'PAQUET SMART 15 x 15 ml'],
        ['code' => 201, 'name' => '1L', 'source_label' => 'E.D.T VRAC 1L'],
        ['code' => 202, 'name' => 'PURE XS femme', 'source_label' => 'E.D.T PURE XS femme'],
        ['code' => 203, 'name' => 'NARCISS', 'source_label' => 'E.D.T VRAC NARCISS'],
        ['code' => 204, 'name' => 'VRAC PARFUM LUX', 'source_label' => 'VRAC PARFUM LUX'],
        ['code' => 205, 'name' => 'SMART ADULTES 100 ML', 'source_label' => 'E.D.T SMART ADULTES 100 ML'],
        ['code' => 206, 'name' => 'VRAC ASS 1L', 'source_label' => 'VRAC ASS 1L'],
        ['code' => 207, 'name' => 'MON GUIRLIN', 'source_label' => 'E.D.T VRAC MON GUIRLIN'],
        ['code' => 208, 'name' => "L'UNE D'ETE", 'source_label' => "E.D.T L'UNE D'ETE"],
        ['code' => 209, 'name' => 'CHILDREN SMART', 'source_label' => 'E.D.T CHILDREN SMART'],
    ];
}
