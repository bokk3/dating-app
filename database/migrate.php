<?php
/**
 * Database Migration Runner
 * 
 * Runs database migrations in order and tracks which ones have been executed
 */

class MigrationRunner
{
    private $pdo;
    private $migrationsPath;
    
    public function __construct()
    {
        // Load database configuration
        $dbConfig = require_once __DIR__ . '/../config/database.php';
        
        // Create PDO connection
        $dsn = "mysql:host={$dbConfig['host']};port={$dbConfig['port']};charset={$dbConfig['charset']}";
        
        try {
            $this->pdo = new PDO($dsn, $dbConfig['username'], $dbConfig['password'], $dbConfig['options']);
            $this->migrationsPath = __DIR__ . '/migrations/';
            
            // Create database if it doesn't exist
            $this->createDatabase($dbConfig['database']);
            
            // Switch to the application database
            $this->pdo->exec("USE `{$dbConfig['database']}`");
            
            // Create migrations tracking table
            $this->createMigrationsTable();
            
        } catch (PDOException $e) {
            die("Database connection failed: " . $e->getMessage());
        }
    }
    
    private function createDatabase($dbName)
    {
        $sql = "CREATE DATABASE IF NOT EXISTS `{$dbName}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci";
        $this->pdo->exec($sql);
        echo "Database '{$dbName}' created or already exists.\n";
    }
    
    private function createMigrationsTable()
    {
        $sql = "
            CREATE TABLE IF NOT EXISTS migrations (
                id INT AUTO_INCREMENT PRIMARY KEY,
                migration VARCHAR(255) NOT NULL UNIQUE,
                executed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX idx_migration (migration)
            )
        ";
        
        $this->pdo->exec($sql);
        echo "Migrations tracking table created.\n";
    }
    
    public function runMigrations()
    {
        $migrationFiles = $this->getMigrationFiles();
        $executedMigrations = $this->getExecutedMigrations();
        
        $pendingMigrations = array_diff($migrationFiles, $executedMigrations);
        
        if (empty($pendingMigrations)) {
            echo "No pending migrations found.\n";
            return;
        }
        
        echo "Found " . count($pendingMigrations) . " pending migrations.\n";
        
        foreach ($pendingMigrations as $migration) {
            $this->runMigration($migration);
        }
        
        echo "All migrations completed successfully!\n";
    }
    
    private function getMigrationFiles()
    {
        $files = glob($this->migrationsPath . '*.sql');
        $migrations = [];
        
        foreach ($files as $file) {
            $migrations[] = basename($file, '.sql');
        }
        
        sort($migrations);
        return $migrations;
    }
    
    private function getExecutedMigrations()
    {
        $stmt = $this->pdo->query("SELECT migration FROM migrations ORDER BY migration");
        return $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
    
    private function runMigration($migration)
    {
        $filePath = $this->migrationsPath . $migration . '.sql';
        
        if (!file_exists($filePath)) {
            throw new Exception("Migration file not found: {$filePath}");
        }
        
        echo "Running migration: {$migration}...\n";
        
        $sql = file_get_contents($filePath);
        
        try {
            // Start transaction
            $this->pdo->beginTransaction();
            
            // Execute migration SQL
            $this->pdo->exec($sql);
            
            // Record migration as executed
            $stmt = $this->pdo->prepare("INSERT INTO migrations (migration) VALUES (?)");
            $stmt->execute([$migration]);
            
            // Commit transaction
            $this->pdo->commit();
            
            echo "✓ Migration {$migration} completed successfully.\n";
            
        } catch (Exception $e) {
            // Rollback on error
            $this->pdo->rollBack();
            throw new Exception("Migration {$migration} failed: " . $e->getMessage());
        }
    }
    
    public function rollbackLastMigration()
    {
        $lastMigration = $this->getLastExecutedMigration();
        
        if (!$lastMigration) {
            echo "No migrations to rollback.\n";
            return;
        }
        
        // Note: Automatic rollback is complex and database-specific
        // For now, we'll just remove the migration record
        // Manual rollback scripts would need to be created for each migration
        
        echo "Warning: Automatic rollback is not implemented.\n";
        echo "Please manually rollback migration: {$lastMigration}\n";
        echo "Then run: php migrate.php --remove-record {$lastMigration}\n";
    }
    
    private function getLastExecutedMigration()
    {
        $stmt = $this->pdo->query("SELECT migration FROM migrations ORDER BY executed_at DESC LIMIT 1");
        return $stmt->fetchColumn();
    }
    
    public function removeMigrationRecord($migration)
    {
        $stmt = $this->pdo->prepare("DELETE FROM migrations WHERE migration = ?");
        $stmt->execute([$migration]);
        echo "Migration record '{$migration}' removed.\n";
    }
    
    public function status()
    {
        $allMigrations = $this->getMigrationFiles();
        $executedMigrations = $this->getExecutedMigrations();
        
        echo "Migration Status:\n";
        echo "================\n";
        
        foreach ($allMigrations as $migration) {
            $status = in_array($migration, $executedMigrations) ? '✓ EXECUTED' : '✗ PENDING';
            echo sprintf("%-50s %s\n", $migration, $status);
        }
        
        $pendingCount = count(array_diff($allMigrations, $executedMigrations));
        echo "\nTotal migrations: " . count($allMigrations) . "\n";
        echo "Executed: " . count($executedMigrations) . "\n";
        echo "Pending: " . $pendingCount . "\n";
    }
    
    public function seedDatabase()
    {
        echo "Seeding database with sample data...\n";
        
        // Insert default subscription plans
        $plans = [
            ['Basic', 'Free basic features', 0.00, 0.00, '["basic_swipes", "basic_matches"]'],
            ['Premium', 'Premium features and unlimited swipes', 9.99, 99.99, '["unlimited_swipes", "super_likes", "read_receipts", "profile_boost"]'],
            ['Platinum', 'All premium features plus exclusive access', 19.99, 199.99, '["unlimited_swipes", "super_likes", "read_receipts", "profile_boost", "priority_support", "exclusive_events"]']
        ];
        
        $stmt = $this->pdo->prepare("
            INSERT IGNORE INTO subscription_plans (name, description, price_monthly, price_yearly, features, sort_order) 
            VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        foreach ($plans as $index => $plan) {
            $stmt->execute([...$plan, $index]);
        }
        
        echo "✓ Subscription plans seeded.\n";
        echo "Database seeding completed!\n";
    }
}

// CLI interface
if (php_sapi_name() === 'cli') {
    $runner = new MigrationRunner();
    
    $command = $argv[1] ?? 'migrate';
    
    switch ($command) {
        case 'migrate':
            $runner->runMigrations();
            break;
            
        case 'status':
            $runner->status();
            break;
            
        case 'rollback':
            $runner->rollbackLastMigration();
            break;
            
        case 'seed':
            $runner->seedDatabase();
            break;
            
        case '--remove-record':
            if (!isset($argv[2])) {
                echo "Usage: php migrate.php --remove-record <migration_name>\n";
                exit(1);
            }
            $runner->removeMigrationRecord($argv[2]);
            break;
            
        default:
            echo "Usage: php migrate.php [command]\n";
            echo "Commands:\n";
            echo "  migrate    Run pending migrations (default)\n";
            echo "  status     Show migration status\n";
            echo "  rollback   Rollback last migration\n";
            echo "  seed       Seed database with sample data\n";
            echo "  --remove-record <name>  Remove migration record\n";
            break;
    }
} else {
    // Web interface (for development only)
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        echo "<h1>Database Migration Runner</h1>";
        echo "<p><strong>Warning:</strong> This interface should only be used in development.</p>";
        echo '<a href="?action=migrate">Run Migrations</a> | ';
        echo '<a href="?action=status">Status</a> | ';
        echo '<a href="?action=seed">Seed Database</a>';
        
        if (isset($_GET['action'])) {
            echo "<hr><pre>";
            $runner = new MigrationRunner();
            
            switch ($_GET['action']) {
                case 'migrate':
                    $runner->runMigrations();
                    break;
                case 'status':
                    $runner->status();
                    break;
                case 'seed':
                    $runner->seedDatabase();
                    break;
            }
            echo "</pre>";
        }
    }
}