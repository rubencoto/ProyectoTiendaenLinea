<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Final Database Fix</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card">
                    <div class="card-header bg-success text-white">
                        <h3>🎉 Almost Done! Final Database Fix</h3>
                        <p class="mb-0">One small fix needed for order processing to work correctly</p>
                    </div>
                    <div class="card-body">
                        <div class="alert alert-info">
                            <strong>Issue Found:</strong> The detalle_pedidos table has 'pedido_id' but your code expects 'orden_id'. 
                            This will fix that column name mismatch.
                        </div>
                        
                        <button id="fixBtn" class="btn btn-success">Apply Final Fix</button>
                        
                        <div id="loading" class="d-none mt-3">
                            <div class="spinner-border" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <span class="ms-2">Applying final fix...</span>
                        </div>
                        <div id="output" class="mt-3"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('fixBtn').addEventListener('click', function() {
            const btn = this;
            const loading = document.getElementById('loading');
            const output = document.getElementById('output');
            
            btn.style.display = 'none';
            loading.classList.remove('d-none');
            output.innerHTML = '';
            
            fetch('final_database_fix.php')
                .then(response => response.text())
                .then(text => {
                    loading.classList.add('d-none');
                    btn.style.display = 'inline-block';
                    
                    try {
                        const data = JSON.parse(text);
                        let html = '<div class="alert alert-success"><h5>Final Fix Results:</h5>';
                        data.output.forEach(line => {
                            if (line.includes('✅')) {
                                html += '<p class="text-success mb-1">' + line + '</p>';
                            } else if (line.includes('❌')) {
                                html += '<p class="text-danger mb-1">' + line + '</p>';
                            } else if (line.includes('🚀')) {
                                html += '<p class="text-primary mb-1"><strong>' + line + '</strong></p>';
                            } else if (line.startsWith('• ')) {
                                html += '<p class="mb-1 ms-3"><code>' + line + '</code></p>';
                            } else {
                                html += '<p class="mb-1">' + line + '</p>';
                            }
                        });
                        html += '</div>';
                        
                        document.getElementById('output').innerHTML = html;
                        
                        if (data.status === 'completed') {
                            document.getElementById('output').innerHTML += '<div class="alert alert-success"><strong>🎉 Success!</strong> Your database is now 100% ready! Go test your application!</div>';
                        }
                    } catch (e) {
                        document.getElementById('output').innerHTML = '<div class="alert alert-danger"><h5>Raw Response:</h5><pre>' + text + '</pre></div>';
                    }
                })
                .catch(error => {
                    loading.classList.add('d-none');
                    btn.style.display = 'inline-block';
                    document.getElementById('output').innerHTML = '<div class="alert alert-danger">Error: ' + error.message + '</div>';
                });
        });
    </script>
</body>
</html>
