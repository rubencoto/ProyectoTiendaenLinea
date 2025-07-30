<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Database Structure Check</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <div class="container mt-5">
        <div class="row justify-content-center">
            <div class="col-md-10">
                <div class="card">
                    <div class="card-header">
                        <h3>Current Database Structure</h3>
                        <p class="mb-0">This will show all tables and columns in your JAWSDB database</p>
                    </div>
                    <div class="card-body">
                        <button id="checkBtn" class="btn btn-primary">Check Database Structure</button>
                        <div id="loading" class="d-none">
                            <div class="spinner-border" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <span class="ms-2">Checking database...</span>
                        </div>
                        <div id="output" class="mt-3"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        document.getElementById('checkBtn').addEventListener('click', function() {
            const btn = this;
            const loading = document.getElementById('loading');
            const output = document.getElementById('output');
            
            btn.style.display = 'none';
            loading.classList.remove('d-none');
            output.innerHTML = '';
            
            fetch('check_all_tables.php')
                .then(response => response.text())
                .then(text => {
                    loading.classList.add('d-none');
                    btn.style.display = 'inline-block';
                    
                    try {
                        const data = JSON.parse(text);
                        let html = '<div class="alert alert-info"><h5>Database Structure:</h5>';
                        data.output.forEach(line => {
                            if (line.includes('===')) {
                                html += '<h6 class="mt-3 text-primary">' + line + '</h6>';
                            } else if (line.startsWith('- ')) {
                                html += '<p class="mb-1 ms-3"><code>' + line + '</code></p>';
                            } else {
                                html += '<p class="mb-1">' + line + '</p>';
                            }
                        });
                        html += '</div>';
                        output.innerHTML = html;
                    } catch (e) {
                        output.innerHTML = '<div class="alert alert-danger"><h5>Raw Response:</h5><pre>' + text + '</pre></div>';
                    }
                })
                .catch(error => {
                    loading.classList.add('d-none');
                    btn.style.display = 'inline-block';
                    output.innerHTML = '<div class="alert alert-danger">Error: ' + error.message + '</div>';
                });
        });
    </script>
</body>
</html>
