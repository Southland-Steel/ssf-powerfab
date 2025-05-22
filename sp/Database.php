<?php
/**
 * SQL Server Database Connection Class for AMPPS
 * Uses ODBC which is available in your PHP installation
 */
class Database {
    private $host = '192.168.81.85';
    private $port = '1433';
    private $username = 'sp.reporter';
    private $password = 'Steelfr139!'; // *** UPDATE THIS WITH YOUR ACTUAL PASSWORD ***
    private $database = 'PLMSOUTHLAND'; // Update if different
    private $connection = null;
    private $error = '';
    private $connected = false;

    /**
     * Constructor - Initialize database connection
     */
    public function __construct() {
        $this->connect();
    }

    /**
     * Establish connection to SQL Server using ODBC
     */
    private function connect() {
        if (!function_exists('odbc_connect')) {
            $this->error = "ODBC functions not available in PHP";
            return false;
        }

        // Try different ODBC driver names
        $drivers = [
            'SQL Server',  // This is what you have installed
            'ODBC Driver 18 for SQL Server',
            'ODBC Driver 17 for SQL Server',
            'SQL Server Native Client 11.0',
            'SQL Server Native Client 10.0'
        ];

        foreach ($drivers as $driver) {
            // Build connection string
            // For ODBC Driver 18, we need TrustServerCertificate=yes
            $connectionString = "Driver={{$driver}};Server={$this->host},{$this->port};Database={$this->database}";

            // Add trust certificate for newer drivers
            if (strpos($driver, '17') !== false || strpos($driver, '18') !== false) {
                $connectionString .= ";TrustServerCertificate=yes;Encrypt=no";
            }

            // Attempt connection with timeout
            $this->connection = @odbc_connect($connectionString, $this->username, $this->password);

            if ($this->connection) {
                $this->connected = true;
                $this->error = '';
                break; // Success!
            }
        }

        if (!$this->connected) {
            $lastError = odbc_errormsg();
            $this->error = "Failed to connect to SQL Server. Last error: " . $lastError;
            $this->error .= "\nMake sure to: 1) Update the password, 2) Install ODBC Driver, 3) Check server accessibility";
        }
    }

    /**
     * Execute SELECT query and return all results
     */
    public function query($sql, $params = array()) {
        if (!$this->connected) {
            $this->error = "Not connected to database";
            return false;
        }

        try {
            // For ODBC, we need to handle parameters differently
            if (!empty($params)) {
                $sql = $this->prepareQuery($sql, $params);
            }

            $result = @odbc_exec($this->connection, $sql);

            if (!$result) {
                $this->error = odbc_errormsg($this->connection);
                return false;
            }

            $rows = array();
            while ($row = odbc_fetch_array($result)) {
                // Handle datetime objects
                foreach ($row as $key => $value) {
                    if ($value instanceof DateTime) {
                        $row[$key] = $value->format('Y-m-d H:i:s');
                    }
                }
                $rows[] = $row;
            }

            odbc_free_result($result);
            return $rows;

        } catch (Exception $e) {
            $this->error = $e->getMessage();
            return false;
        }
    }

    /**
     * Execute INSERT, UPDATE, DELETE queries
     */
    public function execute($sql, $params = array()) {
        if (!$this->connected) {
            $this->error = "Not connected to database";
            return false;
        }

        try {
            if (!empty($params)) {
                $sql = $this->prepareQuery($sql, $params);
            }

            $result = @odbc_exec($this->connection, $sql);

            if (!$result) {
                $this->error = odbc_errormsg($this->connection);
                return false;
            }

            return true;

        } catch (Exception $e) {
            $this->error = $e->getMessage();
            return false;
        }
    }

    /**
     * Get single row from query
     */
    public function queryRow($sql, $params = array()) {
        $results = $this->query($sql, $params);
        if ($results && count($results) > 0) {
            return $results[0];
        }
        return false;
    }

    /**
     * Get single value from query
     */
    public function queryValue($sql, $params = array()) {
        $row = $this->queryRow($sql, $params);
        if ($row && count($row) > 0) {
            return reset($row);
        }
        return false;
    }

    /**
     * Basic parameter substitution for ODBC
     */
    private function prepareQuery($sql, $params) {
        foreach ($params as $param) {
            $pos = strpos($sql, '?');
            if ($pos !== false) {
                if (is_null($param)) {
                    $value = 'NULL';
                } elseif (is_numeric($param)) {
                    $value = $param;
                } elseif (is_bool($param)) {
                    $value = $param ? 1 : 0;
                } else {
                    // Escape single quotes by doubling them
                    $value = "'" . str_replace("'", "''", $param) . "'";
                }
                $sql = substr_replace($sql, $value, $pos, 1);
            }
        }
        return $sql;
    }

    /**
     * Get last error message
     */
    public function getError() {
        return $this->error;
    }

    /**
     * Check if connected
     */
    public function isConnected() {
        return $this->connected;
    }

    /**
     * Get number of affected rows
     */
    public function affectedRows() {
        if ($this->connection) {
            return odbc_num_rows($this->connection);
        }
        return 0;
    }

    /**
     * Close connection
     */
    public function close() {
        if ($this->connection) {
            odbc_close($this->connection);
            $this->connection = null;
            $this->connected = false;
        }
    }

    /**
     * Destructor - Close connection
     */
    public function __destruct() {
        $this->close();
    }

    /**
     * Test connection and return diagnostic info
     */
    public function testConnection() {
        $info = array(
            'connected' => $this->connected,
            'error' => $this->error,
            'host' => $this->host . ':' . $this->port,
            'database' => $this->database,
            'username' => $this->username
        );

        if ($this->connected) {
            // Try to get SQL Server version
            $version = $this->queryValue("SELECT @@VERSION");
            if ($version) {
                $info['sql_server_version'] = substr($version, 0, 100) . '...';
            }

            // Try to count records in the feedback table
            $count = $this->queryValue("SELECT COUNT(*) AS cnt FROM FEEDBACK_FBK_RAW");
            if ($count !== false) {
                $info['feedback_table_rows'] = $count;
            }
        }

        return $info;
    }
}

// Example usage and connection test:
/*
$db = new Database();

if ($db->isConnected()) {
    echo "✓ Connected to SQL Server successfully!\n\n";

    // Test query
    $machines = $db->query("
        SELECT TOP 5
            FFR_CNC AS Machine,
            FFR_NEST AS Nest,
            DATM AS LastUpdate
        FROM FEEDBACK_FBK_RAW
        WHERE FFR_CNC IS NOT NULL
        ORDER BY DATM DESC
    ");

    if ($machines) {
        echo "Recent machine activity:\n";
        foreach ($machines as $machine) {
            echo "- {$machine['Machine']} | Nest: {$machine['Nest']} | Updated: {$machine['LastUpdate']}\n";
        }
    }
} else {
    echo "✗ Connection failed: " . $db->getError() . "\n";
}
*/
?>