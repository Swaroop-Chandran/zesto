<?php
/**
 * Zesto — Database Configuration (PDO)
 * Singleton pattern for a single shared connection per request.
 */

define('DB_HOST',    getenv('DB_HOST')    ?: ($_ENV['DB_HOST']    ?? 'localhost'));
define('DB_NAME',    getenv('DB_NAME')    ?: ($_ENV['DB_NAME']    ?? 'zesto'));
define('DB_USER',    getenv('DB_USER')    ?: ($_ENV['DB_USER']    ?? 'root'));
define('DB_PASS',    getenv('DB_PASS')    !== false ? getenv('DB_PASS') : ($_ENV['DB_PASS'] ?? ''));
define('DB_CHARSET', getenv('DB_CHARSET') ?: ($_ENV['DB_CHARSET'] ?? 'utf8mb4'));

class Database {
    private static ?PDO $instance = null;

    public static function getConnection(): PDO {
        if (self::$instance === null) {
            $host = DB_HOST;
            $dbName = DB_NAME;
            $user = DB_USER;
            $pass = DB_PASS;
            $charset = DB_CHARSET;

            $dsnWithoutDb = "mysql:host={$host};charset={$charset}";
            $options = [
                PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES   => false,
            ];

            try {
                // 1. Connect to MySQL server without database first
                $pdo = new PDO($dsnWithoutDb, $user, $pass, $options);

                // 2. Check if database 'zesto' exists
                $stmt = $pdo->prepare("SELECT SCHEMA_NAME FROM information_schema.SCHEMATA WHERE SCHEMA_NAME = :db LIMIT 1");
                $stmt->execute([':db' => $dbName]);
                $zestoExists = $stmt->fetchColumn() !== false;

                $tablesExist = false;
                if ($zestoExists) {
                    try {
                        $stmt = $pdo->query("SHOW TABLES FROM `{$dbName}` LIKE 'restaurants'");
                        $tablesExist = $stmt->fetchColumn() !== false;
                    } catch (PDOException $ex) {
                        $tablesExist = false;
                    }
                }

                if (!$zestoExists || !$tablesExist) {
                    // Check if 'zyrop_food_order' database exists
                    $stmt = $pdo->prepare("SELECT SCHEMA_NAME FROM information_schema.SCHEMATA WHERE SCHEMA_NAME = 'zyrop_food_order' LIMIT 1");
                    $stmt->execute();
                    $zyropExists = $stmt->fetchColumn() !== false;

                    // Create database 'zesto'
                    $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET {$charset} COLLATE utf8mb4_unicode_ci");

                    $migrationPerformed = false;
                    $migratedTables = [];

                    if ($zyropExists) {
                        // Fetch all tables in 'zyrop_food_order'
                        $stmt = $pdo->query("SHOW TABLES FROM `zyrop_food_order`");
                        $tables = $stmt->fetchAll(PDO::FETCH_COLUMN);

                        if (!empty($tables)) {
                            // Move all tables to 'zesto' cleanly using RENAME TABLE
                            $renameQueries = [];
                            foreach ($tables as $table) {
                                $renameQueries[] = "`zyrop_food_order`.`{$table}` TO `{$dbName}`.`{$table}`";
                                $migratedTables[] = $table;
                            }
                            if (!empty($renameQueries)) {
                                $pdo->exec("RENAME TABLE " . implode(', ', $renameQueries));
                                $migrationPerformed = true;
                            }
                            // Drop legacy database
                            $pdo->exec("DROP DATABASE `zyrop_food_order`");

                            // Perform schema updates on migrated tables to ensure compatibility
                            // 1. Drop old tables that are incompatible and need to be recreated by setup.sql
                            $pdo->exec("USE `{$dbName}`");
                            $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");
                            $pdo->exec("DROP TABLE IF EXISTS `order_items`, `orders`, `otp_verifications`");
                            $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

                            // 2. Alter 'users' table to match Zesto schema (unsigned ID, role, etc.)
                            try {
                                $pdo->exec("ALTER TABLE `users` MODIFY `id` INT UNSIGNED AUTO_INCREMENT");
                            } catch (PDOException $ex) {
                                // Ignore if already unsigned or key constraints prevent modify
                            }
                            
                            $pdo->exec("ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `role` ENUM('customer','restaurant_owner','delivery_partner','admin') NOT NULL DEFAULT 'customer'");
                            $pdo->exec("ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `avatar` VARCHAR(500) DEFAULT NULL");
                            $pdo->exec("ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `is_active` TINYINT(1) NOT NULL DEFAULT 1");
                        }
                    }

                    // Always run setup.sql safely to ensure any missing required tables are created and seeded
                    $setupSqlPath = __DIR__ . '/../setup.sql';
                    if (file_exists($setupSqlPath)) {
                        $sql = file_get_contents($setupSqlPath);
                        
                        // Switch context to newly created database
                        $pdo->exec("USE `{$dbName}`");
                        $pdo->exec("SET FOREIGN_KEY_CHECKS = 0");

                        // Drop incompatible legacy tables to allow users.id type modification
                        $pdo->exec("DROP TABLE IF EXISTS `order_items`, `orders`, `otp_verifications`");

                        // Ensure 'users' table is fully Zesto-compatible if it pre-exists
                        try {
                            $pdo->exec("ALTER TABLE `users` MODIFY `id` INT UNSIGNED AUTO_INCREMENT");
                        } catch (PDOException $ex) {}
                        
                        $pdo->exec("ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `role` ENUM('customer','restaurant_owner','delivery_partner','admin') NOT NULL DEFAULT 'customer'");
                        $pdo->exec("ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `avatar` VARCHAR(500) DEFAULT NULL");
                        $pdo->exec("ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `is_active` TINYINT(1) NOT NULL DEFAULT 1");

                        // Parse and execute statements
                        $queries = preg_split('/;\s*[\r\n]+/', $sql);
                        foreach ($queries as $query) {
                            $query = trim($query);
                            if (empty($query)) continue;
                            // Skip DATABASE creation / USE commands in setup.sql to prevent circularity
                            if (stripos($query, 'CREATE DATABASE') === 0 || stripos($query, 'USE ') === 0) {
                                continue;
                            }
                            $pdo->exec($query);
                        }
                        $pdo->exec("SET FOREIGN_KEY_CHECKS = 1");
                    }

                    // Log bootstrap actions
                    $logMsg = '[' . date('Y-m-d H:i:s') . '] Database Bootstrapped. Name: ' . $dbName;
                    if ($migrationPerformed) {
                        $logMsg .= ' (Migrated ' . count($migratedTables) . ' tables from zyrop_food_order and populated missing schema)';
                    } else {
                        $logMsg .= ' (Seeded fresh tables from setup.sql)';
                    }
                    $logMsg .= PHP_EOL;
                    error_log($logMsg, 3, __DIR__ . '/../logs/application.log');
                }

                // 3. Connect to the final database specifically
                $dsnWithDb = "mysql:host={$host};dbname={$dbName};charset={$charset}";
                self::$instance = new PDO($dsnWithDb, $user, $pass, $options);

            } catch (PDOException $e) {
                // Log full PDO exceptions with trace to logs/error.log
                $logMsg = '[' . date('Y-m-d H:i:s') . '] Connection Failure: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine() . PHP_EOL . $e->getTraceAsString() . PHP_EOL;
                error_log($logMsg, 3, __DIR__ . '/../logs/error.log');

                http_response_code(500);
                header('Content-Type: application/json; charset=utf-8');

                // Determine environment: expose detailed exceptions only in development (localhost)
                $isDev = ($_SERVER['HTTP_HOST'] ?? 'localhost') === 'localhost' || strpos($_SERVER['HTTP_HOST'] ?? '', '127.0.0.1') !== false;
                if ($isDev) {
                    $msg = 'Database Connection Failed: ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine();
                } else {
                    $msg = 'Database connection failed. Please contact the administrator.';
                }
                die(json_encode(['success' => false, 'message' => $msg]));
            }
        }
        return self::$instance;
    }

    private function __construct() {}
    private function __clone() {}
}

/**
 * Shortcut helper — get the PDO instance.
 */
function db(): PDO {
    return Database::getConnection();
}
