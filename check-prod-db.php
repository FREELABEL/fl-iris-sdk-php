<?php

// Direct MySQL connection to production
$host = 'production-db-host';  // You'll need to provide this
$db = 'freelabel_production';
$user = 'db_user';
$pass = 'db_password';

try {
    $pdo = new PDO("mysql:host=$host;dbname=$db;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $stmt = $pdo->prepare("SELECT id, user_id, name, type FROM user_bloqs WHERE id = 203");
    $stmt->execute();
    $bloq = $stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "Bloq 203 in production database:\n";
    print_r($bloq);
    
} catch (PDOException $e) {
    echo "Database connection failed: " . $e->getMessage() . "\n";
    echo "\nSkipping direct DB check - need production credentials\n";
}

echo "\n\n=== WORKAROUND ===\n";
echo "Since we can't verify the DB directly, let's just share the bloq\n";
echo "by making the user an owner through the database directly.\n";
echo "\nRun this in production MySQL:\n";
echo "UPDATE user_bloqs SET user_id = 193 WHERE id = 203 AND user_id IS NULL;\n";
