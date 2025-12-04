<?php
/**
 * Test Upload Configuration
 * Displays current PHP upload settings and tests image upload capability
 */

require_once 'auto-config.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Upload Configuration Test</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            max-width: 1000px;
            margin: 50px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        h1 { color: #2196F3; }
        .section {
            background: white;
            padding: 20px;
            margin: 20px 0;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #ddd;
        }
        th {
            background: #2196F3;
            color: white;
        }
        .ok { color: #4CAF50; font-weight: bold; }
        .warning { color: #ff9800; font-weight: bold; }
        .error { color: #f44336; font-weight: bold; }
        .test-upload {
            margin: 20px 0;
            padding: 20px;
            background: #e3f2fd;
            border-radius: 8px;
        }
        input[type="file"] {
            margin: 10px 0;
        }
        button {
            padding: 10px 20px;
            background: #2196F3;
            color: white;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }
        button:hover {
            background: #1976D2;
        }
        #result {
            margin-top: 20px;
            padding: 15px;
            border-radius: 5px;
            display: none;
        }
        #result.success {
            background: #e8f5e9;
            border-left: 4px solid #4CAF50;
        }
        #result.error {
            background: #ffebee;
            border-left: 4px solid #f44336;
        }
    </style>
</head>
<body>
    <h1>📤 Upload Configuration Test</h1>
    
    <div class="section">
        <h2>PHP Upload Settings</h2>
        <table>
            <tr>
                <th>Setting</th>
                <th>Current Value</th>
                <th>Recommended</th>
                <th>Status</th>
            </tr>
            <?php
            $settings = [
                'upload_max_filesize' => ['current' => ini_get('upload_max_filesize'), 'recommended' => '200M'],
                'post_max_size' => ['current' => ini_get('post_max_size'), 'recommended' => '500M'],
                'max_file_uploads' => ['current' => ini_get('max_file_uploads'), 'recommended' => '500'],
                'memory_limit' => ['current' => ini_get('memory_limit'), 'recommended' => '2048M'],
                'max_execution_time' => ['current' => ini_get('max_execution_time'), 'recommended' => '600'],
                'max_input_vars' => ['current' => ini_get('max_input_vars'), 'recommended' => '5000']
            ];
            
            foreach ($settings as $name => $values) {
                $current = $values['current'];
                $recommended = $values['recommended'];
                
                // Simple comparison (not perfect but good enough)
                $currentNum = (int)$current;
                $recommendedNum = (int)$recommended;
                
                if ($currentNum >= $recommendedNum) {
                    $status = '<span class="ok">✓ OK</span>';
                } elseif ($currentNum >= $recommendedNum * 0.5) {
                    $status = '<span class="warning">⚠ Low</span>';
                } else {
                    $status = '<span class="error">✗ Too Low</span>';
                }
                
                echo "<tr>";
                echo "<td><strong>$name</strong></td>";
                echo "<td>$current</td>";
                echo "<td>$recommended</td>";
                echo "<td>$status</td>";
                echo "</tr>";
            }
            ?>
        </table>
    </div>
    
    <div class="section">
        <h2>PHP Extensions</h2>
        <table>
            <tr>
                <th>Extension</th>
                <th>Status</th>
            </tr>
            <?php
            $extensions = ['gd', 'mbstring', 'fileinfo', 'zip'];
            foreach ($extensions as $ext) {
                $loaded = extension_loaded($ext);
                $status = $loaded ? '<span class="ok">✓ Loaded</span>' : '<span class="error">✗ Not Loaded</span>';
                echo "<tr><td><strong>$ext</strong></td><td>$status</td></tr>";
            }
            ?>
        </table>
    </div>
    
    <div class="section">
        <h2>Test Image Upload</h2>
        <div class="test-upload">
            <p>Upload a test image to verify the system is working:</p>
            <input type="file" id="testImage" accept="image/*">
            <button onclick="testUpload()">Test Upload</button>
            <div id="result"></div>
        </div>
    </div>
    
    <script>
        function testUpload() {
            const fileInput = document.getElementById('testImage');
            const result = document.getElementById('result');
            
            if (!fileInput.files || !fileInput.files[0]) {
                alert('Please select an image first');
                return;
            }
            
            const file = fileInput.files[0];
            const formData = new FormData();
            formData.append('image', file);
            formData.append('field_name', 'test_upload');
            formData.append('current_step', 1);
            
            result.style.display = 'block';
            result.className = '';
            result.innerHTML = '⏳ Uploading...';
            
            fetch('upload-image.php', {
                method: 'POST',
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    result.className = 'success';
                    result.innerHTML = `
                        <strong>✅ Upload Successful!</strong><br>
                        File Path: ${data.file_path}<br>
                        Size: ${(data.size / 1024).toFixed(2)} KB<br>
                        Dimensions: ${data.width} x ${data.height}<br>
                        Draft ID: ${data.draft_id}
                    `;
                } else {
                    result.className = 'error';
                    result.innerHTML = `<strong>❌ Upload Failed</strong><br>${data.message}`;
                }
            })
            .catch(error => {
                result.className = 'error';
                result.innerHTML = `<strong>❌ Error</strong><br>${error.message}`;
            });
        }
    </script>
</body>
</html>
