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
                        <button id="fixBtn" class="btn btn-primary">Fix Database Structure</button>
                        <div id="loading" class="d-none">
                            <div class="spinner-border" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <span class="ms-2">Fixing database structure...</span>
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
            
            btn.disabled = true;
            btn.style.display = 'none';
            loading.classList.remove('d-none');
            output.innerHTML = '';
            
            fetch('fix_database_api.php')
                .then(response => response.json())
                .then(data => {
                    loading.classList.add('d-none');
                    
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
                    
                    output.innerHTML = html;
                    
                    // Add success message if completed
                    if (data.status === 'completed') {
                        output.innerHTML += '<div class="alert alert-success"><strong>Success!</strong> You can now test the registration form.</div>';
                    }
                })
                .catch(error => {
                    loading.classList.add('d-none');
                    output.innerHTML = '<div class="alert alert-danger">Error: ' + error.message + '</div>';
                    btn.disabled = false;
                    btn.style.display = 'block';
                });
        });
    </script>
</body>
</html>
