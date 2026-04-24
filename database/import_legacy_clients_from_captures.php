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

ensureClientColumns($pdo);

$clients = legacyClients();
$missingCodes = array_values(array_diff(range(1, 214), array_column($clients, 'client_code')));

$roleId = (int) $pdo->query("SELECT id FROM roles WHERE role_name = 'CLIENT' LIMIT 1")->fetchColumn();
if ($roleId <= 0) {
    fwrite(STDERR, "Missing CLIENT role.\n");
    exit(1);
}

$selectByCode = $pdo->prepare('SELECT id FROM users WHERE client_code = :client_code LIMIT 1');
$selectByPhone = $pdo->prepare('SELECT id FROM users WHERE phone = :phone LIMIT 1');
$insertUser = $pdo->prepare(
    "INSERT INTO users (
        first_name,
        last_name,
        perfume_shop_name,
        phone,
        location,
        email,
        client_code,
        fiscal_code,
        admin_only_client,
        password_hash,
        is_active
    ) VALUES (
        :first_name,
        :last_name,
        :perfume_shop_name,
        :phone,
        :location,
        :email,
        :client_code,
        :fiscal_code,
        1,
        :password_hash,
        1
    )"
);
$updateUser = $pdo->prepare(
    "UPDATE users
     SET first_name = :first_name,
         last_name = :last_name,
         perfume_shop_name = :perfume_shop_name,
         phone = :phone,
         location = :location,
         client_code = :client_code,
         fiscal_code = :fiscal_code,
         admin_only_client = 1,
         is_active = 1
     WHERE id = :id"
);
$insertRole = $pdo->prepare('INSERT IGNORE INTO user_roles (user_id, role_id) VALUES (:user_id, :role_id)');

$inserted = 0;
$updated = 0;

$pdo->beginTransaction();
try {
    foreach ($clients as $client) {
        $identity = normalizeIdentity($client['identity']);
        [$firstName, $lastName] = splitIdentity($identity);
        $phone = normalizePhone($client['phone'] ?? null);
        $code = (string) $client['client_code'];
        $email = sprintf('legacy-client-%03d@idene.local', (int) $code);
        $passwordHash = password_hash('legacy-import-' . $code, PASSWORD_DEFAULT);

        $existingId = null;
        $selectByCode->execute(['client_code' => $code]);
        $row = $selectByCode->fetch();
        if ($row) {
            $existingId = (int) $row['id'];
        } elseif ($phone !== null) {
            $selectByPhone->execute(['phone' => $phone]);
            $row = $selectByPhone->fetch();
            if ($row) {
                $existingId = (int) $row['id'];
            }
        }

        $baseParams = [
            'first_name' => $firstName,
            'last_name' => $lastName,
            'perfume_shop_name' => $identity,
            'phone' => $phone,
            'location' => '',
            'client_code' => $code,
            'fiscal_code' => null,
        ];
        $insertParams = $baseParams + [
            'email' => $email,
            'password_hash' => $passwordHash,
        ];

        if ($existingId !== null) {
            $updateUser->execute($baseParams + ['id' => $existingId]);
            $insertRole->execute(['user_id' => $existingId, 'role_id' => $roleId]);
            $updated++;
            continue;
        }

        $insertUser->execute($insertParams);
        $userId = (int) $pdo->lastInsertId();
        $insertRole->execute(['user_id' => $userId, 'role_id' => $roleId]);
        $inserted++;
    }

    $pdo->commit();
} catch (Throwable $e) {
    $pdo->rollBack();
    fwrite(STDERR, $e->getMessage() . "\n");
    exit(1);
}

echo "Imported legacy clients.\n";
echo "Inserted: {$inserted}\n";
echo "Updated: {$updated}\n";
echo "Visible clients imported: " . count($clients) . "\n";
echo "Missing codes from screenshots: " . implode(', ', $missingCodes) . "\n";

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

function ensureClientColumns(PDO $pdo): void
{
    $pdo->exec('ALTER TABLE users MODIFY phone VARCHAR(30) NULL');

    $schema = (string) $pdo->query('SELECT DATABASE()')->fetchColumn();
    $check = $pdo->prepare(
        'SELECT COUNT(*)
         FROM INFORMATION_SCHEMA.COLUMNS
         WHERE TABLE_SCHEMA = :schema
           AND TABLE_NAME = "users"
           AND COLUMN_NAME = :column_name'
    );

    foreach ([
        'client_code' => 'ALTER TABLE users ADD COLUMN client_code VARCHAR(80) NULL AFTER email',
        'fiscal_code' => 'ALTER TABLE users ADD COLUMN fiscal_code VARCHAR(120) NULL AFTER client_code',
        'admin_only_client' => 'ALTER TABLE users ADD COLUMN admin_only_client TINYINT(1) NOT NULL DEFAULT 0 AFTER fiscal_code',
    ] as $column => $sql) {
        $check->execute(['schema' => $schema, 'column_name' => $column]);
        if ((int) $check->fetchColumn() === 0) {
            $pdo->exec($sql);
        }
    }
}

function normalizeIdentity(string $value): string
{
    return preg_replace('/\s+/', ' ', trim($value)) ?? trim($value);
}

function normalizePhone(?string $value): ?string
{
    $digits = preg_replace('/\D+/', '', (string) $value);
    return $digits !== '' ? $digits : null;
}

function splitIdentity(string $identity): array
{
    $upper = mb_strtoupper($identity);
    $businessKeywords = [
        'PARFUMERIE', 'PERFUMERIE', 'PARFUMRRIE', 'PARFEMERIE', 'BEAUTY', 'BOUTIQUE',
        'LIBRAIRIE', 'LIBRERIE', 'LAIBRERIE', 'PHARMA', 'PHOTOCOPIE', 'PHOTO',
        'SOCIETE', 'STE', 'MAGASIN', 'MAISON', 'COMPANY', 'DISTRIBUTION',
        'COIF', 'COSMET', 'L\'ESCALE', 'LE MONDE', 'LAB',
    ];

    foreach ($businessKeywords as $keyword) {
        if (str_contains($upper, $keyword)) {
            return ['', $identity];
        }
    }

    $plainIdentity = trim((string) preg_replace('/\([^)]*\)/', '', $identity));
    $parts = array_values(array_filter(explode(' ', $plainIdentity), static fn ($part) => $part !== ''));
    if (count($parts) <= 1) {
        return ['', $identity];
    }

    $firstName = array_pop($parts);
    $lastName = implode(' ', $parts);

    return [$firstName, $lastName];
}

function legacyClients(): array
{
    return [
        ['client_code' => 1, 'identity' => 'EXTREME'],
        ['client_code' => 2, 'identity' => 'BOUBIDI COIF'],
        ['client_code' => 3, 'identity' => 'BEN NACER SAFIA'],
        ['client_code' => 4, 'identity' => '2 MIT'],
        ['client_code' => 5, 'identity' => 'ZENKRI'],
        ['client_code' => 6, 'identity' => 'BENNA RIADH'],
        ['client_code' => 7, 'identity' => 'ALI DAOUD'],
        ['client_code' => 8, 'identity' => 'CHAABAN WALID'],
        ['client_code' => 9, 'identity' => 'NEFZI NADIA'],
        ['client_code' => 10, 'identity' => 'BEN YAHMED LOTFI'],
        ['client_code' => 11, 'identity' => 'MNOUCHI JAMEL'],
        ['client_code' => 12, 'identity' => 'PARFUMERIE ZIED TOUIL'],
        ['client_code' => 13, 'identity' => 'LEITH'],
        ['client_code' => 14, 'identity' => 'PARFUMERIE TRABELSI SAIDA'],
        ['client_code' => 15, 'identity' => 'SONIA DAKHLAOUI'],
        ['client_code' => 16, 'identity' => 'TRABELSI MOHAMED'],
        ['client_code' => 17, 'identity' => 'BELGACEM NARJES'],
        ['client_code' => 18, 'identity' => 'LIBRAIRIE DORRA'],
        ['client_code' => 19, 'identity' => 'ELJANI SOUMAYA'],
        ['client_code' => 20, 'identity' => 'BEAUTY FLY'],
        ['client_code' => 21, 'identity' => 'MZOUGHI LASAAD'],
        ['client_code' => 22, 'identity' => 'PHOTO TURKI'],
        ['client_code' => 23, 'identity' => 'PARFUMERIE SAMRA COIF'],
        ['client_code' => 24, 'identity' => 'PARFUMERIE MAATOUG'],
        ['client_code' => 25, 'identity' => 'COMSMOGROS'],
        ['client_code' => 26, 'identity' => 'MAHER'],
        ['client_code' => 27, 'identity' => 'BEN SALEM'],
        ['client_code' => 28, 'identity' => 'PRETTY COIF'],
        ['client_code' => 29, 'identity' => 'RIAHI TAHANI'],
        ['client_code' => 30, 'identity' => 'PARFEMERIE RAHMA'],
        ['client_code' => 31, 'identity' => 'SALAH FAFA'],
        ['client_code' => 32, 'identity' => 'PARFUMERIE ELEGANCE'],
        ['client_code' => 33, 'identity' => 'BELHAJ ALI MOUNIR'],
        ['client_code' => 34, 'identity' => 'SLAMA NEJIBA'],
        ['client_code' => 35, 'identity' => 'SEBTI'],
        ['client_code' => 36, 'identity' => 'STE TESTOURIA'],
        ['client_code' => 37, 'identity' => 'SARBEJI SAMI', 'phone' => '71276788'],
        ['client_code' => 38, 'identity' => 'OUALI YAHIA'],
        ['client_code' => 39, 'identity' => 'JAIT NACER'],
        ['client_code' => 40, 'identity' => 'CO GE CO'],
        ['client_code' => 41, 'identity' => 'SGHAIRI KAMEL'],
        ['client_code' => 42, 'identity' => 'ZINKRI'],
        ['client_code' => 43, 'identity' => 'BEZINE ASMA', 'phone' => '24406078'],
        ['client_code' => 44, 'identity' => 'ARWA ZEID'],
        ['client_code' => 45, 'identity' => 'PARFUMERIE RANIA'],
        ['client_code' => 46, 'identity' => 'SASSI WAHIBA', 'phone' => '20250043'],
        ['client_code' => 47, 'identity' => 'PARFUMERIE IMEN', 'phone' => '26146620'],
        ['client_code' => 48, 'identity' => 'KATROU', 'phone' => '20002525'],
        ['client_code' => 49, 'identity' => 'BEN SALAH YOUSSEF', 'phone' => '22726541'],
        ['client_code' => 50, 'identity' => 'PHARMACOIF'],
        ['client_code' => 51, 'identity' => 'COSMOCHIC'],
        ['client_code' => 52, 'identity' => 'NOUIOUI CHAKER', 'phone' => '24725797'],
        ['client_code' => 53, 'identity' => 'BEN CHEDLY SAMI', 'phone' => '96009114'],
        ['client_code' => 54, 'identity' => 'BEN SALAH SAMI'],
        ['client_code' => 55, 'identity' => 'MOUAZ'],
        ['client_code' => 56, 'identity' => 'BEN SALAH ZOUHAIR'],
        ['client_code' => 57, 'identity' => 'SOCIETE ASMACOS DISTRIBUTION'],
        ['client_code' => 58, 'identity' => 'PARFUMERIE LA SIRENE'],
        ['client_code' => 59, 'identity' => 'BEAUTY COIF'],
        ['client_code' => 60, 'identity' => 'PARFUMERIE SIALA'],
        ['client_code' => 61, 'identity' => 'SOCIETE COSMAVITA'],
        ['client_code' => 62, 'identity' => 'PARFUMERIE LA SIRENE'],
        ['client_code' => 63, 'identity' => 'LAIBRERIE NAASSEN'],
        ['client_code' => 64, 'identity' => 'BOUALI HAYET'],
        ['client_code' => 65, 'identity' => 'BEN SALAH HICHEM'],
        ['client_code' => 66, 'identity' => 'PARADIS COSMETIQUES'],
        ['client_code' => 67, 'identity' => 'PARFUMERIE STARS'],
        ['client_code' => 68, 'identity' => 'FETHI BOUBIDI'],
        ['client_code' => 69, 'identity' => 'PARFUMERIE NABIL', 'phone' => '95154330'],
        ['client_code' => 70, 'identity' => 'BEN ZAIED NABIL'],
        ['client_code' => 71, 'identity' => 'PARFUMERIE ELKHADHRA'],
        ['client_code' => 72, 'identity' => 'PARFUMERIE KHANCHOUCH', 'phone' => '27537535'],
        ['client_code' => 73, 'identity' => 'GADOUAR LOTFI'],
        ['client_code' => 74, 'identity' => 'MEHDI ARIANA'],
        ['client_code' => 75, 'identity' => 'BOUTIQUE JAMELOUCOUM'],
        ['client_code' => 76, 'identity' => 'BEN FARHAT GUENNICHI AMOR'],
        ['client_code' => 77, 'identity' => 'SOCIETE NORD AFRIQUE'],
        ['client_code' => 78, 'identity' => 'SOMACOIF'],
        ['client_code' => 79, 'identity' => 'RAMZI'],
        ['client_code' => 80, 'identity' => 'SOCIETE EL BARAKA'],
        ['client_code' => 81, 'identity' => 'PARFUMERIE ELEGANCE'],
        ['client_code' => 82, 'identity' => 'BEN SALAH FETHI', 'phone' => '23203619'],
        ['client_code' => 83, 'identity' => 'TRABELSSI FETHIA', 'phone' => '98273371'],
        ['client_code' => 84, 'identity' => 'BEL HAJ NAJIA', 'phone' => '21775132'],
        ['client_code' => 85, 'identity' => 'RABBOUDI SLIM (ERACOS)'],
        ['client_code' => 86, 'identity' => 'STE YOUNES COIF'],
        ['client_code' => 87, 'identity' => 'AIDLI NAFISSA'],
        ['client_code' => 88, 'identity' => 'STE YOUNES BEAUTY CENTER'],
        ['client_code' => 89, 'identity' => 'BOUTIQUE AMEL', 'phone' => '20815871'],
        ['client_code' => 99, 'identity' => 'BEAUTY A R'],
        ['client_code' => 100, 'identity' => 'PARFUMERIE MUGUET'],
        ['client_code' => 101, 'identity' => 'TOUAFIK'],
        ['client_code' => 102, 'identity' => 'BEN HRIZ'],
        ['client_code' => 103, 'identity' => 'PARFUMERIE SAMSARA'],
        ['client_code' => 104, 'identity' => 'LE MONDE COSMETIQUE'],
        ['client_code' => 105, 'identity' => 'LOTFI KALLALIA'],
        ['client_code' => 106, 'identity' => 'JAMILA KATROU', 'phone' => '23221441'],
        ['client_code' => 107, 'identity' => 'GHERIANI HABIB'],
        ['client_code' => 108, 'identity' => 'PARFUMERIE BEN SALAH YOUSSE', 'phone' => '20912643'],
        ['client_code' => 109, 'identity' => 'BEN AYOUB RIDHA'],
        ['client_code' => 110, 'identity' => 'MAISON DU COIFFUR'],
        ['client_code' => 111, 'identity' => 'BECHIR'],
        ['client_code' => 112, 'identity' => 'BEN SALAH YOUSSEF (FAYCEL)'],
        ['client_code' => 113, 'identity' => 'BEN MOUSSA SALIM'],
        ['client_code' => 114, 'identity' => 'ANIS BEN YAHYATEN'],
        ['client_code' => 115, 'identity' => 'A LA PAGE'],
        ['client_code' => 116, 'identity' => 'BEN HAJ YOUSSEF RADHIA'],
        ['client_code' => 117, 'identity' => 'MRABET'],
        ['client_code' => 118, 'identity' => 'AYADI A.B.C.O'],
        ['client_code' => 119, 'identity' => 'CHIHAB'],
        ['client_code' => 120, 'identity' => "L'ESCALE"],
        ['client_code' => 121, 'identity' => 'HADJ MASSAOUD FAHD'],
        ['client_code' => 122, 'identity' => 'MAGASIN MARWA 2', 'phone' => '52929700'],
        ['client_code' => 123, 'identity' => 'HADI FOUNI'],
        ['client_code' => 124, 'identity' => 'PHARMASUD'],
        ['client_code' => 125, 'identity' => 'HEDIA BOUGUERRA'],
        ['client_code' => 126, 'identity' => 'AYMEN ZENKRI'],
        ['client_code' => 127, 'identity' => 'AMIR MARAS'],
        ['client_code' => 128, 'identity' => 'ZENKRI GACEM (HAKIM)', 'phone' => '25632206'],
        ['client_code' => 129, 'identity' => 'GUENZOUI'],
        ['client_code' => 130, 'identity' => 'SOCIETE ECO - PRIX'],
        ['client_code' => 131, 'identity' => 'HAMZA BEN ABDALLAH'],
        ['client_code' => 132, 'identity' => 'PARFUMERIE MAAMAR BECHIR', 'phone' => '90300039'],
        ['client_code' => 133, 'identity' => 'PERFUMERIE MAROUAN'],
        ['client_code' => 134, 'identity' => 'ANWAR AROUA'],
        ['client_code' => 135, 'identity' => 'STE ABDENNADHER DE COMMERC'],
        ['client_code' => 136, 'identity' => 'PARFUMERIE I.D.C'],
        ['client_code' => 137, 'identity' => 'PARFUMERIE MAUAZ'],
        ['client_code' => 138, 'identity' => 'HRIZI YASSINE AHMED'],
        ['client_code' => 149, 'identity' => 'PARFUMERIE LA REINE'],
        ['client_code' => 150, 'identity' => 'KHAIRI ELHAJEM'],
        ['client_code' => 151, 'identity' => 'BOUTIQUE JAMILA', 'phone' => '99512120'],
        ['client_code' => 152, 'identity' => 'STE YASMINE COIF'],
        ['client_code' => 153, 'identity' => 'RACHID AIN ZAGHOUEN'],
        ['client_code' => 154, 'identity' => 'YASMINE COIF'],
        ['client_code' => 155, 'identity' => 'KOUIR HOUSSEM'],
        ['client_code' => 156, 'identity' => 'BEN HADJ MASSAOUD HAMMADI'],
        ['client_code' => 157, 'identity' => 'AL YOSR'],
        ['client_code' => 158, 'identity' => 'PARFUMERIE SAMIR'],
        ['client_code' => 159, 'identity' => 'CHOKRI EZZEMZI'],
        ['client_code' => 160, 'identity' => 'ANWAR'],
        ['client_code' => 161, 'identity' => 'SOCIETE HAMAYO'],
        ['client_code' => 162, 'identity' => 'AOUDI GHOFRANE'],
        ['client_code' => 163, 'identity' => 'RUE EL ATLAS LAFAYETTE'],
        ['client_code' => 164, 'identity' => 'AJILI AWATEF'],
        ['client_code' => 165, 'identity' => 'PARFUMERIE RAOUD'],
        ['client_code' => 166, 'identity' => 'SEM LAB'],
        ['client_code' => 167, 'identity' => 'TRABELSI MOURAD BEN MOHAMED'],
        ['client_code' => 168, 'identity' => 'SOCIETE EXTRA COIF', 'phone' => '54075451'],
        ['client_code' => 169, 'identity' => 'NAIMA BEN ALI'],
        ['client_code' => 170, 'identity' => 'KHALIL'],
        ['client_code' => 171, 'identity' => 'NEJIB TIJANI'],
        ['client_code' => 172, 'identity' => 'ATEF TURKI', 'phone' => '24362183'],
        ['client_code' => 173, 'identity' => 'TRABELSSI MOUNIR', 'phone' => '25702855'],
        ['client_code' => 174, 'identity' => 'ELKHARRAT BEYREM'],
        ['client_code' => 175, 'identity' => 'STE MERICEM DISTRIBUTION', 'phone' => '92447325'],
        ['client_code' => 176, 'identity' => 'ADEL SAID'],
        ['client_code' => 177, 'identity' => 'CHIRAZ'],
        ['client_code' => 178, 'identity' => 'YASSER DISTRIBUTION', 'phone' => '22982907'],
        ['client_code' => 179, 'identity' => 'WAIL MNASRI', 'phone' => '54428337'],
        ['client_code' => 180, 'identity' => 'PARFUMERIE ELHASNA', 'phone' => '52869043'],
        ['client_code' => 181, 'identity' => 'GUNEIN LEILA'],
        ['client_code' => 182, 'identity' => 'PARFUMERIE ZOHAIR BEN SALAH'],
        ['client_code' => 183, 'identity' => 'PARFUMERIE AVIS BEAUTE', 'phone' => '22403433'],
        ['client_code' => 184, 'identity' => 'MENSI ABDELAZIZ'],
        ['client_code' => 185, 'identity' => 'LEILA ABIDI'],
        ['client_code' => 186, 'identity' => 'OMAR MZOUGHI'],
        ['client_code' => 187, 'identity' => 'AYOUB ZAYET', 'phone' => '22445804'],
        ['client_code' => 188, 'identity' => 'MEHER', 'phone' => '92117440'],
        ['client_code' => 189, 'identity' => 'LASSAD BEN AMAR SLIMENE'],
        ['client_code' => 190, 'identity' => 'KATROU LATIFA'],
        ['client_code' => 191, 'identity' => 'INES PLAST'],
        ['client_code' => 192, 'identity' => 'TAOUFIK HEKIMI'],
        ['client_code' => 193, 'identity' => 'INES AROUA', 'phone' => '94350706'],
        ['client_code' => 194, 'identity' => 'IDEAL BEAUTE'],
        ['client_code' => 195, 'identity' => 'SATOURI RADOUANE'],
        ['client_code' => 196, 'identity' => 'MARYEM SPRLOS'],
        ['client_code' => 197, 'identity' => 'HK FARACHA COMPANY'],
        ['client_code' => 198, 'identity' => 'MELPRO'],
        ['client_code' => 199, 'identity' => 'GHANMI NAJEH'],
        ['client_code' => 200, 'identity' => 'PARFUMRRIE KHMAIS'],
        ['client_code' => 201, 'identity' => 'KATRO IMEN'],
        ['client_code' => 202, 'identity' => 'TOUIL ZIED'],
        ['client_code' => 203, 'identity' => 'JABER TLILI', 'phone' => '26079051'],
        ['client_code' => 204, 'identity' => 'BEN LELLAH KHDIJA', 'phone' => '20799429'],
        ['client_code' => 205, 'identity' => 'BGUIR HOUCINE', 'phone' => '27147541'],
        ['client_code' => 206, 'identity' => 'NAMOUCHI ALI'],
        ['client_code' => 207, 'identity' => 'MONCEF LAABIDI'],
        ['client_code' => 208, 'identity' => 'LIBRERIE EL ARABI'],
        ['client_code' => 209, 'identity' => 'SAMIHA'],
        ['client_code' => 210, 'identity' => 'NADIA BOUABDALLAH'],
        ['client_code' => 211, 'identity' => 'LIBRAIRIE ELARABI', 'phone' => '98600568'],
        ['client_code' => 212, 'identity' => 'NAGAHI KARIM'],
        ['client_code' => 213, 'identity' => 'KHARRAT BAYREM'],
        ['client_code' => 214, 'identity' => 'LAJINI SALIM'],
    ];
}
