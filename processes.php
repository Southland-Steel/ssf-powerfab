<?php
// Database configuration

$db_host = '192.168.80.12';
$db_user = 'ssf.reporter';
$db_pass = 'SSF.reporter251@*';
$db_name = 'fabrication';

// Connect to database
$mysqli = new mysqli($db_host, $db_user, $db_pass, $db_name);

if ($mysqli->connect_error) {
    die('Connect Error (' . $mysqli->connect_errno . ') ' . $mysqli->connect_error);
}

// Handle process kill action
if (isset($_POST['kill']) && isset($_POST['process_id'])) {
    $process_id = (int)$_POST['process_id'];
    $kill_query = "KILL $process_id";
    if ($mysqli->query($kill_query)) {
        $message = "Process $process_id successfully killed";
    } else {
        $message = "Error killing process: " . $mysqli->error;
    }
}

// Get process list
$query = "SHOW FULL PROCESSLIST";
$result = $mysqli->query($query);
?>

    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>MySQL Process Manager</title>
        <style>
            table {
                border-collapse: collapse;
                width: 100%;
                margin: 20px 0;
            }
            th, td {
                border: 1px solid #ddd;
                padding: 8px;
                text-align: left;
            }
            th {
                background-color: #f2f2f2;
            }
            tr:nth-child(even) {
                background-color: #f9f9f9;
            }
            .message {
                padding: 10px;
                margin: 10px 0;
                border-radius: 4px;
            }
            .success {
                background-color: #dff0d8;
                border: 1px solid #d6e9c6;
                color: #3c763d;
            }
            .error {
                background-color: #f2dede;
                border: 1px solid #ebccd1;
                color: #a94442;
            }
            button {
                background-color: #dc3545;
                color: white;
                border: none;
                padding: 5px 10px;
                border-radius: 3px;
                cursor: pointer;
            }
            button:hover {
                background-color: #c82333;
            }
        </style>
    </head>
    <body>
    <h1>MySQL Process Manager</h1>

    <?php if (isset($message)): ?>
        <div class="message <?php echo ($mysqli->error ? 'error' : 'success'); ?>">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <table>
        <thead>
        <tr>
            <th>ID</th>
            <th>User</th>
            <th>Host</th>
            <th>DB</th>
            <th>Command</th>
            <th>Time</th>
            <th>State</th>
            <th>Info</th>
            <th>Action</th>
        </tr>
        </thead>
        <tbody>
        <?php while ($row = $result->fetch_assoc()): ?>
            <tr>
                <td><?php echo htmlspecialchars($row['Id']); ?></td>
                <td><?php echo htmlspecialchars($row['User']); ?></td>
                <td><?php echo htmlspecialchars($row['Host']); ?></td>
                <td><?php echo htmlspecialchars($row['db']); ?></td>
                <td><?php echo htmlspecialchars($row['Command']); ?></td>
                <td><?php echo htmlspecialchars($row['Time']); ?></td>
                <td><?php echo htmlspecialchars($row['State']); ?></td>
                <td><?php echo htmlspecialchars($row['Info']); ?></td>
                <td>
                    <form method="POST" onsubmit="return confirm('Are you sure you want to kill this process?');">
                        <input type="hidden" name="process_id" value="<?php echo $row['Id']; ?>">
                        <button type="submit" name="kill">Kill</button>
                    </form>
                </td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>

    <script>
        // Auto-refresh the page every 5 seconds
        setTimeout(function() {
            window.location.reload();
        }, 5000);
    </script>
    </body>
    </html>
<?php
$mysqli->close();
?>