<?php
// api-drones.php
//
// Simple drones CRUD API for GitHub Pages admin UI.
// Uses the same database as your Laravel app and applies basic CORS for ennoid-jpg.github.io

// === CONFIG ===
$githubOrigin = 'https://ennoid-jpg.github.io';

// Copy these from your .env on Hostinger (DB_HOST, DB_DATABASE, DB_USERNAME, DB_PASSWORD)
$dbHost = 'YOUR_DB_HOST';
$dbName = 'YOUR_DB_DATABASE';
$dbUser = 'YOUR_DB_USERNAME';
$dbPass = 'YOUR_DB_PASSWORD';

// === CORS HEADERS ===
header('Access-Control-Allow-Origin: ' . $githubOrigin);
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');
header('Access-Control-Allow-Credentials: true');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

header('Content-Type: application/json');

// === HELPERS ===
function respond($data, $status = 200)
{
    http_response_code($status);
    echo json_encode($data);
    exit;
}

// Basic error handler
set_exception_handler(function ($e) {
    respond([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage(),
    ], 500);
});

// === CONNECT TO DB ===
try {
    $dsn = "mysql:host=$dbHost;dbname=$dbName;charset=utf8mb4";
    $pdo = new PDO($dsn, $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
} catch (PDOException $e) {
    respond([
        'success' => false,
        'message' => 'Database connection failed: ' . $e->getMessage(),
    ], 500);
}

// === ROUTING ===
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? $_POST['action'] ?? 'list';

// --- LIST DRONES (GET) ---
if ($method === 'GET' && $action === 'list') {
    $search = isset($_GET['search']) ? trim($_GET['search']) : '';
    $type   = isset($_GET['type']) ? trim($_GET['type']) : '';

    $sql = "SELECT id_drone, name, type, image, description, price, brand, stock FROM drones WHERE 1=1";
    $params = [];

    if ($search !== '') {
        $sql .= " AND (name LIKE :search OR brand LIKE :search OR type LIKE :search)";
        $params[':search'] = '%' . $search . '%';
    }

    if ($type !== '') {
        $sql .= " AND type = :type";
        $params[':type'] = $type;
    }

    $sql .= " ORDER BY id_drone DESC";

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $drones = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Distinct types
    $typesStmt = $pdo->query("SELECT DISTINCT type FROM drones ORDER BY type ASC");
    $types = $typesStmt->fetchAll(PDO::FETCH_COLUMN);

    respond([
        'success' => true,
        'drones'  => $drones,
        'types'   => $types,
    ]);
}

// --- CREATE DRONE (POST) ---
if ($method === 'POST' && $action === 'create') {
    $name  = trim($_POST['name']  ?? '');
    $type  = trim($_POST['type']  ?? '');
    $brand = trim($_POST['brand'] ?? '');
    $desc  = trim($_POST['description'] ?? '');
    $price = $_POST['price'] ?? null;
    $stock = $_POST['stock'] ?? null;
    $image = trim($_POST['image'] ?? '');

    if ($name === '' || $type === '' || $price === null || $stock === null) {
        respond([
            'success' => false,
            'message' => 'Name, type, price and stock are required.',
        ], 422);
    }

    $stmt = $pdo->prepare("
        INSERT INTO drones (name, type, image, description, price, brand, stock)
        VALUES (:name, :type, :image, :description, :price, :brand, :stock)
    ");
    $stmt->execute([
        ':name'        => $name,
        ':type'        => $type,
        ':image'       => $image,
        ':description' => $desc,
        ':price'       => $price,
        ':brand'       => $brand,
        ':stock'       => (int)$stock,
    ]);

    respond([
        'success' => true,
        'message' => 'Drone created successfully.',
        'id_drone' => $pdo->lastInsertId(),
    ]);
}

// --- UPDATE DRONE (POST) ---
if ($method === 'POST' && $action === 'update') {
    $id    = isset($_POST['id_drone']) ? (int)$_POST['id_drone'] : 0;
    $name  = trim($_POST['name']  ?? '');
    $type  = trim($_POST['type']  ?? '');
    $brand = trim($_POST['brand'] ?? '');
    $desc  = trim($_POST['description'] ?? '');
    $price = $_POST['price'] ?? null;
    $stock = $_POST['stock'] ?? null;
    $image = trim($_POST['image'] ?? '');

    if ($id <= 0) {
        respond(['success' => false, 'message' => 'Invalid drone ID.'], 422);
    }

    if ($name === '' || $type === '' || $price === null || $stock === null) {
        respond([
            'success' => false,
            'message' => 'Name, type, price and stock are required.',
        ], 422);
    }

    $stmt = $pdo->prepare("
        UPDATE drones
        SET name = :name,
            type = :type,
            image = :image,
            description = :description,
            price = :price,
            brand = :brand,
            stock = :stock
        WHERE id_drone = :id
    ");
    $stmt->execute([
        ':id'          => $id,
        ':name'        => $name,
        ':type'        => $type,
        ':image'       => $image,
        ':description' => $desc,
        ':price'       => $price,
        ':brand'       => $brand,
        ':stock'       => (int)$stock,
    ]);

    respond([
        'success' => true,
        'message' => 'Drone updated successfully.',
    ]);
}

// --- DELETE DRONE (POST) ---
if ($method === 'POST' && $action === 'delete') {
    $id = isset($_POST['id_drone']) ? (int)$_POST['id_drone'] : 0;
    if ($id <= 0) {
        respond(['success' => false, 'message' => 'Invalid drone ID.'], 422);
    }

    $stmt = $pdo->prepare("DELETE FROM drones WHERE id_drone = :id");
    $stmt->execute([':id' => $id]);

    respond([
        'success' => true,
        'message' => 'Drone deleted successfully.',
    ]);
}

// --- FALLBACK ---
respond([
    'success' => false,
    'message' => 'Unknown action or method.',
], 400);


