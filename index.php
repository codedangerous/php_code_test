<?php

$host = "localhost";
$username = "root";
$password = "root";
$dbname = "php_code_test";

// Task 1
try {
    $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION, // Turn on exceptions for errors
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,       // Fetch associative arrays
        PDO::ATTR_EMULATE_PREPARES   => false,                  // Use real prepared statements
    ];

    $pdo = new PDO($dsn, $username, $password, $options);

    $selectSql = "SELECT orderid, comments FROM sweetwater_test ORDER BY orderid";
    $stmt = $pdo->query($selectSql);
    $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $categories = [
        'Comments about candy' => [
            '/\bcand(?:y|ies)\b/i',
            '/\bsmarties\b/i',
            '/\bbit[\s-]*o[\s-]*honey\b/i',
        ],

        'Comments about call me / don\'t call me' => [
            '/\bcall\s+me\b/i',
        ],

        'Comments about who referred me' => [
            '/\breferred\b/i',
            '/\breferral\b/i',
        ],

        'Comments about signature requirements upon delivery' => [
            '/\bsignature\b/i',
        ],
    ];

    $groupedComments = [];

    foreach ($categories as $category => $patterns) {
        $groupedComments[$category] = [];
    }

    $groupedComments['Miscellaneous comments'] = [];

    foreach ($comments as $comment) {
        $matched = false;

        foreach ($categories as $category => $patterns) {
            foreach ($patterns as $pattern) {
                if (preg_match($pattern, $comment['comments'])) {
                    $groupedComments[$category][] = $comment;
                    $matched = true;
                    break;
                }
            }
        }
        if (!$matched) {
            $groupedComments['Miscellaneous comments'][] = $comment;
        }
    }

    foreach ($groupedComments as $category => $items) {
        echo '<h2>' . htmlspecialchars($category, ENT_QUOTES, 'UTF-8') . '</h2>';

        if (empty($items)) {
            echo '<p>No comments in this category.</p>';
            continue;
        }

        echo '<ul>';

        foreach ($items as $item) {
            echo '<li>';
            echo '<strong>Order #' .
                htmlspecialchars((string) $item['orderid'], ENT_QUOTES, 'UTF-8') .
                ':</strong> ';

            echo nl2br(htmlspecialchars(
                $item['comments'],
                ENT_QUOTES,
                'UTF-8'
            ));

            echo '</li>';
        }

        echo '</ul>';
    }

} catch (PDOException $e) {
    die("Database Error: " . $e->getMessage());
}

// Task 2
function extractExpectedShipDate(string $comment): ?string
{
    $pattern = '/Expected Ship Date:\s*(\d{2}\/\d{2}\/\d{2})/i';

    if (!preg_match($pattern, $comment, $matches)) {
        return null;
    }

    $date = DateTime::createFromFormat('!m/d/y', $matches[1]);
    $errors = DateTime::getLastErrors();

    // Reject invalid dates
    if (
        $date === false ||
        ($errors !== false &&
            ($errors['warning_count'] > 0 || $errors['error_count'] > 0))
    ) {
        return null;
    }

    return $date->format('Y-m-d');
}

$select = $pdo->query(
    "SELECT orderid, comments
     FROM sweetwater_test
     WHERE comments LIKE '%Expected Ship Date:%'"
);

$update = $pdo->prepare(
    "UPDATE sweetwater_test
     SET shipdate_expected = :shipdate
     WHERE orderid = :orderid"
);

$pdo->beginTransaction();

try {
    while ($row = $select->fetch(PDO::FETCH_ASSOC)) {
        $shipdate = extractExpectedShipDate($row['comments']);

        // Skip comments without a valid expected ship date.
        if ($shipdate === null) {
            continue;
        }

        $update->execute([
            ':shipdate' => $shipdate,
            ':orderid' => $row['orderid'],
        ]);
    }

    $pdo->commit();
} catch (Throwable $e) {
    $pdo->rollBack();
    throw $e;
}

$pdo = null;