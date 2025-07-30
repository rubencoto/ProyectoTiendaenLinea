<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Structure Fix</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header">
                        <h3>Database Structure Fix</h3>
                        <p class="mb-0">This will add missing columns to the clientes table</p>
                    </div>
                    <div class="card-body">
                        <button id="debugBtn" class="btn btn-info me-2">Debug Connection</button>
                        <button id="fixBtn" class="btn btn-primary">Fix Database Structure</button>
                        <div id="loading" class="d-none">
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
            document.getElementById('debugBtn').style.display = 'none';
            document.getElementById('fixBtn').style.display = 'none';
            document.getElementById('loadingText').textContent = text;
            document.getElementById('loading').classList.remove('d-none');
            document.getElementById('output').innerHTML = '';
        }
        
        function hideLoading() {
            document.getElementById('loading').classList.add('d-none');
            document.getElementById('debugBtn').style.display = 'inline-block';
            document.getElementById('fixBtn').style.display = 'inline-block';
        }
        
        function showError(message) {
            document.getElementById('output').innerHTML = '<div class="alert alert-danger">Error: ' + message + '</div>';
        }
        
        document.getElementById('debugBtn').addEventListener('click', function() {
            showLoading('Debugging connection...');
            
            fetch('debug_connection.php')
                .then(response => response.text())
                .then(text => {
                    hideLoading();
                    try {
                        const data = JSON.parse(text);
                        let html = '<div class="alert alert-info"><h5>Debug Results:</h5>';
                        html += '<pre>' + data.debug + '</pre>';
                        html += '</div>';
                        document.getElementById('output').innerHTML = html;
                    } catch (e) {
                        document.getElementById('output').innerHTML = '<div class="alert alert-danger"><h5>Raw Response:</h5><pre>' + text + '</pre></div>';
                    }
                })
                .catch(error => {
                    hideLoading();
                    showError(error.message);
                });
        });

        document.getElementById('fixBtn').addEventListener('click', function() {
            showLoading('Fixing database structure...');
            
            fetch('fix_database_api.php')
                .then(response => response.text())
                .then(text => {
                    hideLoading();
                    try {
                        const data = JSON.parse(text);
                        let html = '<div class="alert alert-info"><h5>Database Fix Results:</h5>';
                        data.output.forEach(line => {
                            if (line.includes('✅')) {
                                html += '<p class="text-success mb-1">' + line + '</p>';
                            } else if (line.includes('❌')) {
                                html += '<p class="text-danger mb-1">' + line + '</p>';
                            } else if (line.includes('===')) {
                                html += '<h6 class="mt-2">' + line + '</h6>';
                            } else {
                                html += '<p class="mb-1">' + line + '</p>';
                            }
                        });
                        html += '</div>';
                        
                        document.getElementById('output').innerHTML = html;
                        
                        if (data.status === 'completed') {
                            document.getElementById('output').innerHTML += '<div class="alert alert-success"><strong>Success!</strong> You can now test the registration form.</div>';
                        }
                    } catch (e) {
                        document.getElementById('output').innerHTML = '<div class="alert alert-danger"><h5>Raw Response:</h5><pre>' + text + '</pre></div>';
                    }
                })
                .catch(error => {
                    hideLoading();
                    showError(error.message);
                });
        });
    </script>
</body>
</html>
