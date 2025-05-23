<?php
/**
 * SQL Server Database Connection Class
 *
 * This class provides a connection to Microsoft SQL Server database
 * using PDO with ODBC or sqlsrv driver
 */
class Database {
    private $host = '192.168.81.85';
    private $port = '1433';
    private $username = 'sp.reporter';
    private $password = 'Steelfr139!';
    private $database = 'PLMSOUTHLAND'; // Update this with your actual database name
    private $connection = null;
    private $stmt = null;
    private $error = '';
    private $driver = 'odbc'; // Options: 'odbc', 'sqlsrv', 'dblib'

    /**
     * Constructor - Initialize database connection
     */
    public function __construct() {
        $this->connect();
    }

    /**
     * Establish connection to SQL Server
     */
    private function connect() {
        try {
            // Try different connection methods based on available drivers
            if ($this->driver === 'sqlsrv' && extension_loaded('pdo_sqlsrv')) {
                // Method 1: Using PDO_SQLSRV (if installed)
                $dsn = "sqlsrv:Server={$this->host},{$this->port};Database={$this->database}";
                $options = array(
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::SQLSRV_ATTR_QUERY_TIMEOUT => 30,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                );
            } elseif ($this->driver === 'dblib' && extension_loaded('pdo_dblib')) {
                // Method 2: Using PDO_DBLIB (common on Linux)
                $dsn = "dblib:host={$this->host}:{$this->port};dbname={$this->database}";
                $options = array(
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                );
            } else {
                // Method 3: Using ODBC (most compatible, usually available on Windows)
                $dsn = "odbc:Driver={SQL Server};Server={$this->host},{$this->port};Database={$this->database}";
                // Alternative ODBC drivers to try:
                // $dsn = "odbc:Driver={ODBC Driver 17 for SQL Server};Server={$this->host},{$this->port};Database={$this->database}";
                // $dsn = "odbc:Driver={SQL Server Native Client 11.0};Server={$this->host},{$this->port};Database={$this->database}";

                $options = array(
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
                );
            }

            $this->connection = new PDO($dsn, $this->username, $this->password, $options);

        } catch (PDOException $e) {
            $this->error = $e->getMessage();

            // Try alternative ODBC connection string if first attempt fails
            if (strpos($this->error, 'could not find driver') !== false ||
                strpos($this->error, 'Data source name not found') !== false) {
                try {
                    // Try with different ODBC driver name
                    $dsn = "odbc:Driver={ODBC Driver 17 for SQL Server};Server={$this->host},{$this->port};Database={$this->database}";
                    $this->connection = new PDO($dsn, $this->username, $this->password, $options);
                    $this->error = ''; // Clear error if successful
                } catch (PDOException $e2) {
                    $this->error = "Connection failed: " . $e2->getMessage() .
                        "\n\nAvailable PDO drivers: " . implode(', ', PDO::getAvailableDrivers());
                }
            }

            if ($this->error) {
                die("Database connection error: " . $this->error);
            }
        }
    }

    /**
     * Execute SELECT query
     *
     * @param string $sql SQL query string
     * @param array $params Parameters for prepared statement
     * @return array|false Query results or false on error
     */
    public function query($sql, $params = array()) {
        try {
            $this->stmt = $this->connection->prepare($sql);

            if (empty($params)) {
                $this->stmt->execute();
            } else {
                $this->stmt->execute($params);
            }

            return $this->stmt->fetchAll(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            $this->error = $e->getMessage();
            return false;
        }
    }

    /**
     * Execute INSERT, UPDATE, DELETE queries
     *
     * @param string $sql SQL query string
     * @param array $params Parameters for prepared statement
     * @return bool True on success, false on error
     */
    public function execute($sql, $params = array()) {
        try {
            $this->stmt = $this->connection->prepare($sql);

            if (empty($params)) {
                return $this->stmt->execute();
            } else {
                return $this->stmt->execute($params);
            }

        } catch (PDOException $e) {
            $this->error = $e->getMessage();
            return false;
        }
    }

    /**
     * Get single row from query
     *
     * @param string $sql SQL query string
     * @param array $params Parameters for prepared statement
     * @return array|false Single row or false on error
     */
    public function queryRow($sql, $params = array()) {
        try {
            $this->stmt = $this->connection->prepare($sql);

            if (empty($params)) {
                $this->stmt->execute();
            } else {
                $this->stmt->execute($params);
            }

            return $this->stmt->fetch(PDO::FETCH_ASSOC);

        } catch (PDOException $e) {
            $this->error = $e->getMessage();
            return false;
        }
    }

    /**
     * Get single value from query
     *
     * @param string $sql SQL query string
     * @param array $params Parameters for prepared statement
     * @return mixed Single value or false on error
     */
    public function queryValue($sql, $params = array()) {
        $row = $this->queryRow($sql, $params);
        if ($row && count($row) > 0) {
            return reset($row); // Return first value
        }
        return false;
    }

    /**
     * Get number of affected rows
     *
     * @return int Number of affected rows
     */
    public function affectedRows() {
        if ($this->stmt) {
            return $this->stmt->rowCount();
        }
        return 0;
    }

    /**
     * Get last insert ID
     *
     * @return string Last insert ID
     */
    public function lastInsertId() {
        return $this->connection->lastInsertId();
    }

    /**
     * Begin transaction
     *
     * @return bool
     */
    public function beginTransaction() {
        return $this->connection->beginTransaction();
    }

    /**
     * Commit transaction
     *
     * @return bool
     */
    public function commit() {
        return $this->connection->commit();
    }

    /**
     * Rollback transaction
     *
     * @return bool
     */
    public function rollback() {
        return $this->connection->rollBack();
    }

    /**
     * Get last error message
     *
     * @return string Error message
     */
    public function getError() {
        return $this->error;
    }

    /**
     * Check if connected
     *
     * @return bool
     */
    public function isConnected() {
        return $this->connection !== null;
    }

    /**
     * Close connection
     */
    public function close() {
        $this->stmt = null;
        $this->connection = null;
    }

    /**
     * Destructor - Close connection
     */
    public function __destruct() {
        $this->close();
    }

    /**
     * Escape string for SQL (basic protection)
     * Note: Prepared statements are preferred
     *
     * @param string $string String to escape
     * @return string Escaped string
     */
    public function escape($string) {
        // For prepared statements, escaping is not necessary
        // This is here for compatibility only
        return $this->connection->quote($string);
    }

    /**
     * Get available PDO drivers
     *
     * @return array Available drivers
     */
    public function getAvailableDrivers() {
        return PDO::getAvailableDrivers();
    }

    /**
     * Get server info
     *
     * @return string Server information
     */
    public function getServerInfo() {
        if ($this->connection) {
            return $this->connection->getAttribute(PDO::ATTR_SERVER_VERSION);
        }
        return false;
    }

    /**
     * Test connection and show diagnostic info
     *
     * @return array Diagnostic information
     */
    public function testConnection() {
        $info = array(
            'connected' => $this->isConnected(),
            'error' => $this->error,
            'pdo_drivers' => PDO::getAvailableDrivers(),
            'php_version' => PHP_VERSION,
            'os' => PHP_OS
        );

        if ($this->isConnected()) {
            try {
                $info['server_version'] = $this->getServerInfo();
                $info['database'] = $this->database;

                // Try a simple query
                $result = $this->queryValue("SELECT @@VERSION");
                $info['sql_server_version'] = $result;

                // Test the specific table
                $count = $this->queryValue("SELECT COUNT(*) FROM FEEDBACK_FBK_RAW");
                $info['feedback_table_rows'] = $count;

            } catch (Exception $e) {
                $info['test_error'] = $e->getMessage();
            }
        }

        return $info;
    }
}

// Diagnostic script - uncomment to test connection
/*
$db = new Database();
$info = $db->testConnection();
echo "<pre>";
print_r($info);
echo "</pre>";

if (!$db->isConnected()) {
    echo "\n\nTo fix this issue, you need to:\n";
    echo "1. Install SQL Server ODBC driver or PDO_SQLSRV extension\n";
    echo "2. Update the password in the Database class\n";
    echo "3. Ensure SQL Server allows remote connections\n";
}
*/
?>