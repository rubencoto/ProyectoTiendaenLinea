<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>JAWSDB Setup</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-10">
                <div class="card">
                    <div class="card-header">
                        <h3>JAWSDB Database Setup</h3>
                        <p class="mb-0">This will create all necessary tables with the correct structure for your application</p>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-warning">
                            <strong>Important:</strong> This will create tables: clientes, vendedores, productos, ordenes, detalle_pedidos
                        </div>
                        
                        <button id="analyzeBtn" class="btn btn-warning me-2">Analyze Issues</button>
                        <button id="checkBtn" class="btn btn-info me-2">Check Current Structure</button>
                        <button id="setupBtn" class="btn btn-primary">Setup Database</button>
                        
                        <div id="loading" class="d-none mt-3">
                            <div class="spinner-border" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <span class="ms-2" id="loadingText">Processing...</span>
                        </div>
                        <div id="output" class="mt-3"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        function showLoading(text) {
            document.getElementById('analyzeBtn').style.display = 'none';
            document.getElementById('checkBtn').style.display = 'none';
            document.getElementById('setupBtn').style.display = 'none';
            document.getElementById('loadingText').textContent = text;
            document.getElementById('loading').classList.remove('d-none');
            document.getElementById('output').innerHTML = '';
        }
        
        function hideLoading() {
            document.getElementById('loading').classList.add('d-none');
            document.getElementById('analyzeBtn').style.display = 'inline-block';
            document.getElementById('checkBtn').style.display = 'inline-block';
            document.getElementById('setupBtn').style.display = 'inline-block';
        }

        document.getElementById('analyzeBtn').addEventListener('click', function() {
            showLoading('Analyzing database issues...');
            
            fetch('analyze_database_issues.php')
                .then(response => response.text())
                .then(text => {
                    hideLoading();
                    try {
                        const data = JSON.parse(text);
                        let html = '<div class="alert alert-warning"><h5>Database Analysis Results:</h5>';
                        data.output.forEach(line => {
                            if (line.includes('✅')) {
                                html += '<p class="text-success mb-1">' + line + '</p>';
                            } else if (line.includes('❌')) {
                                html += '<p class="text-danger mb-1">' + line + '</p>';
                            } else if (line.includes('Recommendations:')) {
                                html += '<h6 class="mt-2 text-info">' + line + '</h6>';
                            } else {
                                html += '<p class="mb-1">' + line + '</p>';
                            }
                        });
                        html += '</div>';
                        document.getElementById('output').innerHTML = html;
                    } catch (e) {
                        document.getElementById('output').innerHTML = '<div class="alert alert-danger"><h5>Raw Response:</h5><pre>' + text + '</pre></div>';
                    }
                })
                .catch(error => {
                    hideLoading();
                    document.getElementById('output').innerHTML = '<div class="alert alert-danger">Error: ' + error.message + '</div>';
                });
        });

        document.getElementById('checkBtn').addEventListener('click', function() {
            showLoading('Checking current structure...');
            
            fetch('check_all_tables.php')
                .then(response => response.text())
                .then(text => {
                    hideLoading();
                    try {
                        const data = JSON.parse(text);
                        let html = '<div class="alert alert-info"><h5>Current Database Structure:</h5>';
                        data.output.forEach(line => {
                            if (line.includes('===')) {
                                html += '<h6 class="mt-2 text-primary">' + line + '</h6>';
                            } else if (line.startsWith('- ')) {
                                html += '<p class="mb-1 ms-3"><code>' + line + '</code></p>';
                            } else {
                                html += '<p class="mb-1">' + line + '</p>';
                            }
                        });
                        html += '</div>';
                        document.getElementById('output').innerHTML = html;
                    } catch (e) {
                        document.getElementById('output').innerHTML = '<div class="alert alert-danger"><h5>Raw Response:</h5><pre>' + text + '</pre></div>';
                    }
                })
                .catch(error => {
                    hideLoading();
                    document.getElementById('output').innerHTML = '<div class="alert alert-danger">Error: ' + error.message + '</div>';
                });
        });

        document.getElementById('setupBtn').addEventListener('click', function() {
            if (!confirm('This will create/update database tables. Continue?')) {
                return;
            }
            
            showLoading('Setting up database structure...');
            
            fetch('setup_jawsdb.php')
                .then(response => response.text())
                .then(text => {
                    hideLoading();
                    try {
                        const data = JSON.parse(text);
                        let html = '<div class="alert alert-info"><h5>Database Setup Results:</h5>';
                        data.output.forEach(line => {
                            if (line.includes('✅')) {
                                html += '<p class="text-success mb-1">' + line + '</p>';
                            } else if (line.includes('❌')) {
                                html += '<p class="text-danger mb-1">' + line + '</p>';
                            } else if (line.includes('===')) {
                                html += '<h6 class="mt-2 text-primary">' + line + '</h6>';
                            } else if (line.startsWith('- ')) {
                                html += '<p class="mb-1 ms-3"><code>' + line + '</code></p>';
                            } else {
                                html += '<p class="mb-1">' + line + '</p>';
                            }
                        });
                        html += '</div>';
                        
                        document.getElementById('output').innerHTML = html;
                        
                        if (data.status === 'completed') {
                            document.getElementById('output').innerHTML += '<div class="alert alert-success"><strong>Success!</strong> Database setup completed. You can now test all functionality.</div>';
                        }
                    } catch (e) {
                        document.getElementById('output').innerHTML = '<div class="alert alert-danger"><h5>Raw Response:</h5><pre>' + text + '</pre></div>';
                    }
                })
                .catch(error => {
                    hideLoading();
                    document.getElementById('output').innerHTML = '<div class="alert alert-danger">Error: ' + error.message + '</div>';
                });
        });
    </script>
</body>
</html>
