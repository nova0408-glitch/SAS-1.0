<?php
$hash = '';
$password = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = trim($_POST['password'] ?? '');
    
    if (empty($password)) {
        $error = 'Please enter a password.';
    } elseif (strlen($password) < 6) {
        $error = 'Password must be at least 6 characters.';
    } else {
        $hash = password_hash($password, PASSWORD_DEFAULT);
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Bcrypt Password Hash Generator</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }
        
        .container {
            background: white;
            border-radius: 10px;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.2);
            padding: 40px;
            max-width: 500px;
            width: 100%;
        }
        
        h1 {
            color: #333;
            margin-bottom: 10px;
            font-size: 28px;
            text-align: center;
        }
        
        .subtitle {
            text-align: center;
            color: #666;
            font-size: 14px;
            margin-bottom: 30px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            color: #333;
            font-weight: 500;
            margin-bottom: 8px;
        }
        
        input[type="password"],
        input[type="text"] {
            width: 100%;
            padding: 12px;
            border: 2px solid #e0e0e0;
            border-radius: 6px;
            font-size: 16px;
            transition: border-color 0.3s;
        }
        
        input[type="password"]:focus,
        input[type="text"]:focus {
            outline: none;
            border-color: #667eea;
        }
        
        button {
            width: 100%;
            padding: 12px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s;
        }
        
        button:hover {
            transform: translateY(-2px);
        }
        
        button:active {
            transform: translateY(0);
        }
        
        .error {
            background: #fee;
            color: #c33;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        
        .success {
            background: #efe;
            color: #3c3;
            padding: 12px;
            border-radius: 6px;
            margin-bottom: 20px;
            font-size: 14px;
        }
        
        .result-box {
            background: #f5f5f5;
            padding: 15px;
            border-radius: 6px;
            margin-top: 20px;
            border: 2px solid #e0e0e0;
        }
        
        .result-label {
            color: #666;
            font-size: 12px;
            text-transform: uppercase;
            margin-bottom: 8px;
        }
        
        .result-hash {
            background: white;
            padding: 12px;
            border-radius: 4px;
            word-break: break-all;
            font-family: 'Courier New', monospace;
            font-size: 12px;
            color: #333;
            line-height: 1.4;
        }
        
        .copy-btn {
            width: 100%;
            padding: 10px;
            background: #667eea;
            color: white;
            border: none;
            border-radius: 6px;
            font-size: 14px;
            cursor: pointer;
            margin-top: 10px;
        }
        
        .copy-btn:hover {
            background: #5568d3;
        }
        
        .info {
            background: #f0f7ff;
            border-left: 4px solid #667eea;
            padding: 12px;
            border-radius: 4px;
            margin-top: 20px;
            font-size: 13px;
            color: #333;
            line-height: 1.6;
        }
        
        .info strong {
            color: #667eea;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔐 Password Hash Generator</h1>
        <p class="subtitle">Generate Bcrypt hashes for SAS database</p>
        
        <?php if ($error): ?>
            <div class="error">⚠️ <?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label for="password">Enter Password:</label>
                <input 
                    type="password" 
                    id="password" 
                    name="password" 
                    placeholder="e.g., MyPassword123" 
                    required
                >
            </div>
            <button type="submit">Generate Hash</button>
        </form>
        
        <?php if ($hash): ?>
            <div class="success">✅ Hash generated successfully!</div>
            
            <div class="result-box">
                <div class="result-label">Bcrypt Hash (Copy this):</div>
                <div class="result-hash" id="hashResult"><?php echo htmlspecialchars($hash); ?></div>
                <button class="copy-btn" onclick="copyToClipboard()">📋 Copy Hash</button>
            </div>
            
            <div class="info">
                <strong>How to use:</strong><br>
                1. Copy the hash above<br>
                2. Go to phpMyAdmin → sas_db → users<br>
                3. Edit user row → Paste in password_hash field<br>
                4. Click Go to save
            </div>
        <?php endif; ?>
    </div>
    
    <script>
        function copyToClipboard() {
            const hashElement = document.getElementById('hashResult');
            const text = hashElement.textContent;
            
            navigator.clipboard.writeText(text).then(() => {
                alert('Hash copied to clipboard!');
            }).catch(err => {
                alert('Failed to copy. Please copy manually.');
            });
        }
        
        // Focus on password field when page loads
        window.onload = () => {
            document.getElementById('password').focus();
        };
    </script>
</body>
</html>