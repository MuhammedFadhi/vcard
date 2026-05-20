<?php
/**
 * Database Connection Diagnostic Script
 * Created for testing remote MySQL database connectivity.
 */

// Database Credentials
define('DB_HOST', '69.72.248.201');
define('DB_USER', 'effedoco_v_card');
define('DB_PASS', 'e4YU(;NHLa]%broR');
define('DB_NAME', 'effedoco_v_card');
define('DB_PORT', 3306);

// Initialize test variables
$connectionSuccess = false;
$errorMessage = '';
$errorCode = 0;
$latency = 0;
$serverInfo = '';
$clientInfo = '';
$tableCount = 0;
$tables = [];

// Start time measurement
$startTime = microtime(true);

// Disable error reporting temporarily to handle it gracefully in our custom UI
mysqli_report(MYSQLI_REPORT_OFF);

// Attempt connection
$conn = @mysqli_connect(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);

// End time measurement
$endTime = microtime(true);
$latency = round(($endTime - $startTime) * 1000, 2); // Convert to milliseconds

if ($conn) {
    $connectionSuccess = true;
    $serverInfo = mysqli_get_server_info($conn);
    $clientInfo = mysqli_get_client_info();
    
    // Fetch some table info as proof of active queries
    $result = @mysqli_query($conn, "SHOW TABLES");
    if ($result) {
        $tableCount = mysqli_num_rows($result);
        while ($row = mysqli_fetch_row($result)) {
            $tables[] = $row[0];
        }
        mysqli_free_result($result);
    }
    
    mysqli_close($conn);
} else {
    $errorMessage = mysqli_connect_error();
    $errorCode = mysqli_connect_errno();
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>MySQL Remote Connection Diagnostics</title>
    <!-- Modern Premium Font -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <style>
        :root {
            --bg-gradient: linear-gradient(135deg, #0f172a 0%, #1e1b4b 100%);
            --card-bg: rgba(30, 41, 59, 0.7);
            --card-border: rgba(255, 255, 255, 0.08);
            --accent-success: #10b981;
            --accent-success-bg: rgba(16, 185, 129, 0.1);
            --accent-error: #f43f5e;
            --accent-error-bg: rgba(244, 63, 94, 0.1);
            --text-primary: #f8fafc;
            --text-secondary: #94a3b8;
            --primary-blue: #6366f1;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Outfit', sans-serif;
            background: var(--bg-gradient);
            color: var(--text-primary);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 2rem 1rem;
            overflow-x: hidden;
        }

        .container {
            width: 100%;
            max-width: 750px;
            z-index: 10;
        }

        .card {
            background: var(--card-bg);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid var(--card-border);
            border-radius: 24px;
            padding: 2.5rem;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            animation: fadeIn 0.8s ease-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .header {
            text-align: center;
            margin-bottom: 2.5rem;
        }

        .header h1 {
            font-size: 2rem;
            font-weight: 700;
            letter-spacing: -0.025em;
            margin-bottom: 0.5rem;
            background: linear-gradient(to right, #a5b4fc, #818cf8);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }

        .header p {
            color: var(--text-secondary);
            font-size: 1rem;
        }

        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.75rem;
            padding: 1rem 2rem;
            border-radius: 9999px;
            font-weight: 600;
            font-size: 1.1rem;
            margin: 0 auto 2rem auto;
            width: fit-content;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        }

        .status-badge.success {
            background-color: var(--accent-success-bg);
            color: var(--accent-success);
            border: 1px solid rgba(16, 185, 129, 0.2);
        }

        .status-badge.error {
            background-color: var(--accent-error-bg);
            color: var(--accent-error);
            border: 1px solid rgba(244, 63, 94, 0.2);
        }

        .status-badge svg {
            width: 24px;
            height: 24px;
            fill: none;
            stroke: currentColor;
            stroke-width: 2.5;
        }

        .grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1.25rem;
            margin-bottom: 2rem;
        }

        @media (max-width: 600px) {
            .grid {
                grid-template-columns: 1fr;
            }
        }

        .info-tile {
            background: rgba(15, 23, 42, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.03);
            padding: 1.25rem;
            border-radius: 16px;
            transition: all 0.3s ease;
        }

        .info-tile:hover {
            border-color: rgba(99, 102, 241, 0.3);
            transform: translateY(-2px);
        }

        .info-tile .label {
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.05em;
            color: var(--text-secondary);
            margin-bottom: 0.35rem;
        }

        .info-tile .value {
            font-size: 1.1rem;
            font-weight: 600;
            word-break: break-all;
        }

        .error-panel {
            background: var(--accent-error-bg);
            border: 1px solid rgba(244, 63, 94, 0.2);
            border-radius: 16px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            animation: shake 0.5s ease-in-out;
        }

        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            20%, 60% { transform: translateX(-6px); }
            40%, 80% { transform: translateX(6px); }
        }

        .error-panel h3 {
            color: var(--accent-error);
            font-size: 1.1rem;
            margin-bottom: 0.75rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .error-panel p {
            font-family: monospace;
            font-size: 0.95rem;
            background: rgba(0, 0, 0, 0.2);
            padding: 0.75rem;
            border-radius: 8px;
            color: #fda4af;
            border: 1px solid rgba(244, 63, 94, 0.1);
        }

        .tips-list {
            margin-top: 1.5rem;
        }

        .tips-list h4 {
            color: var(--text-primary);
            font-size: 0.95rem;
            margin-bottom: 0.5rem;
            font-weight: 600;
        }

        .tips-list ul {
            list-style-type: none;
        }

        .tips-list li {
            position: relative;
            padding-left: 1.5rem;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
            color: var(--text-secondary);
        }

        .tips-list li::before {
            content: "→";
            position: absolute;
            left: 0;
            color: var(--accent-error);
            font-weight: bold;
        }

        .success-panel {
            background: var(--accent-success-bg);
            border: 1px solid rgba(16, 185, 129, 0.2);
            border-radius: 16px;
            padding: 1.5rem;
            margin-bottom: 2rem;
        }

        .success-panel h3 {
            color: var(--accent-success);
            font-size: 1.1rem;
            margin-bottom: 0.75rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .success-panel ul {
            list-style: none;
            max-height: 180px;
            overflow-y: auto;
            padding-right: 0.5rem;
        }

        /* Custom Scrollbar for modern feel */
        .success-panel ul::-webkit-scrollbar {
            width: 6px;
        }
        .success-panel ul::-webkit-scrollbar-track {
            background: rgba(0, 0, 0, 0.1);
            border-radius: 3px;
        }
        .success-panel ul::-webkit-scrollbar-thumb {
            background: rgba(16, 185, 129, 0.3);
            border-radius: 3px;
        }

        .success-panel li {
            padding: 0.35rem 0.75rem;
            background: rgba(0, 0, 0, 0.15);
            border-radius: 6px;
            margin-bottom: 0.35rem;
            font-family: monospace;
            font-size: 0.85rem;
            display: flex;
            justify-content: space-between;
        }

        .footer {
            text-align: center;
            margin-top: 2rem;
        }

        .btn-retry {
            background: linear-gradient(135deg, var(--primary-blue) 0%, #4f46e5 100%);
            border: none;
            color: white;
            padding: 0.85rem 2rem;
            border-radius: 12px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            box-shadow: 0 10px 15px -3px rgba(99, 102, 241, 0.3);
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-block;
        }

        .btn-retry:hover {
            transform: translateY(-2px);
            box-shadow: 0 20px 25px -5px rgba(99, 102, 241, 0.4);
            filter: brightness(1.1);
        }

        .btn-retry:active {
            transform: translateY(0);
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="card">
            <div class="header">
                <h1>Database Connection Diagnostics</h1>
                <p>Checking live connection to the remote MySQL database</p>
            </div>

            <div style="display: flex; justify-content: center;">
                <?php if ($connectionSuccess): ?>
                    <div class="status-badge success">
                        <svg viewBox="0 0 24 24">
                            <path d="M5 13l4 4L19 7" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        Connection Successful
                    </div>
                <?php else: ?>
                    <div class="status-badge error">
                        <svg viewBox="0 0 24 24">
                            <path d="M18 6L6 18M6 6l12 12" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        Connection Failed
                    </div>
                <?php endif; ?>
            </div>

            <!-- Configuration used -->
            <div class="grid">
                <div class="info-tile">
                    <div class="label">Host</div>
                    <div class="value"><?php echo htmlspecialchars(DB_HOST); ?></div>
                </div>
                <div class="info-tile">
                    <div class="label">Port</div>
                    <div class="value"><?php echo htmlspecialchars(DB_PORT); ?></div>
                </div>
                <div class="info-tile">
                    <div class="label">Username</div>
                    <div class="value"><?php echo htmlspecialchars(DB_USER); ?></div>
                </div>
                <div class="info-tile">
                    <div class="label">Database Name</div>
                    <div class="value"><?php echo htmlspecialchars(DB_NAME); ?></div>
                </div>
                <div class="info-tile">
                    <div class="label">Latency</div>
                    <div class="value" style="color: <?php echo $latency > 500 ? '#f43f5e' : ($latency > 200 ? '#fbbf24' : '#10b981'); ?>;">
                        <?php echo $latency; ?> ms
                    </div>
                </div>
                <div class="info-tile">
                    <div class="label">MySQL Client Version</div>
                    <div class="value"><?php echo htmlspecialchars($clientInfo); ?></div>
                </div>
            </div>

            <?php if (!$connectionSuccess): ?>
                <div class="error-panel">
                    <h3>
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align: middle;">
                            <path d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        Diagnostic Error [Code <?php echo $errorCode; ?>]
                    </h3>
                    <p><?php echo htmlspecialchars($errorMessage); ?></p>

                    <div class="tips-list">
                        <h4>Possible Solutions:</h4>
                        <ul>
                            <li><strong>Remote Access Bindings:</strong> The database host <code><?php echo DB_HOST; ?></code> might not have port <code>3306</code> exposed, or the database service is bound strictly to <code>localhost</code> (127.0.0.1) on that server.</li>
                            <li><strong>MySQL User Privileges:</strong> Ensure the user <code><?php echo DB_USER; ?></code> is permitted to connect from **any host** (<code>%</code>) or from this local server's IP address.</li>
                            <li><strong>Firewall Rules:</strong> Ensure the target server's firewall (AWS Security Group, UFW, etc.) allows incoming TCP traffic on port <code>3306</code>.</li>
                            <li><strong>Network Blocking:</strong> Verify if your local Laragon/PHP server's outbound firewall allows outgoing traffic on port <code>3306</code>.</li>
                        </ul>
                    </div>
                </div>
            <?php else: ?>
                <div class="success-panel">
                    <h3>
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align: middle;">
                            <path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" stroke-linecap="round" stroke-linejoin="round"/>
                        </svg>
                        Database Server Details
                    </h3>
                    <div style="margin-bottom: 1.25rem;">
                        <span style="color: var(--text-secondary); font-size: 0.9rem;">MySQL Server Version: </span>
                        <strong style="color: var(--text-primary); font-family: monospace; font-size: 1rem;"><?php echo htmlspecialchars($serverInfo); ?></strong>
                    </div>
                    
                    <div style="color: var(--text-secondary); font-size: 0.9rem; margin-bottom: 0.5rem; font-weight: 500;">
                        Available Tables (Total: <?php echo $tableCount; ?>):
                    </div>
                    <?php if ($tableCount > 0): ?>
                        <ul>
                            <?php foreach ($tables as $index => $tableName): ?>
                                <li>
                                    <span style="color: var(--text-secondary);"><?php echo $index + 1; ?>.</span>
                                    <strong><?php echo htmlspecialchars($tableName); ?></strong>
                                </li>
                            <?php endforeach; ?>
                        </ul>
                    <?php else: ?>
                        <p style="font-size: 0.9rem; color: var(--text-secondary); font-style: italic;">Connection succeeded, but no tables were found in the <code><?php echo htmlspecialchars(DB_NAME); ?></code> database.</p>
                    <?php endif; ?>
                </div>
            <?php endif; ?>

            <div class="footer">
                <a href="" class="btn-retry">Run Connection Test Again</a>
            </div>
        </div>
    </div>
</body>
</html>
