<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>eduroam | Bulk Provisioning</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        :root {
            --eduroam-blue: #003366; /* Classic institutional blue */
            --eduroam-accent: #f39200; /* Subtle accent */
        }
        body { background-color: #f4f7f9; font-family: 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; }
        .navbar-brand-custom { font-weight: 800; color: var(--eduroam-blue); letter-spacing: -1px; }
        .card { border: none; border-radius: 16px; overflow: hidden; }
        .card-header { background-color: var(--eduroam-blue) !important; color: white; padding: 1.5rem; border: none; }
        .btn-eduroam { background-color: var(--eduroam-blue); color: white; border-radius: 8px; padding: 12px; transition: 0.3s; }
        .btn-eduroam:hover { background-color: #002244; color: white; box-shadow: 0 4px 12px rgba(0,51,102,0.2); }
        .csv-helper { font-size: 0.85rem; background: #fff3cd; border-radius: 8px; padding: 10px; border-left: 4px solid #ffc107; }
        .upload-zone { border: 2px dashed #dee2e6; border-radius: 12px; padding: 40px 20px; transition: 0.3s; background: #fafafa; cursor: pointer; }
        .upload-zone:hover { border-color: var(--eduroam-blue); background: #f0f5ff; }
    </style>
</head>
<body>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-10">
            
            <div class="row g-4">
                <div class="col-md-5">
                    <div class="mb-4">
                        <h2 class="navbar-brand-custom mb-1"><i class="fa-solid fa-wifi me-2"></i>UNILIA Eduroam portal</h2>
                        <p class="text-muted">Global Wi-Fi Roaming for Academics</p>
                    </div>

                    <h5 class="fw-bold mt-4">Bulk User Enrollment</h5>
                    <p class="text-secondary small">Use this portal to upload student rosters. System will automatically:</p>
                    
                    <ul class="list-unstyled small text-secondary">
                        <li class="mb-2"><i class="fa-solid fa-check-circle text-success me-2"></i>Generate unique RADIUS credentials</li>
                        <li class="mb-2"><i class="fa-solid fa-check-circle text-success me-2"></i>Queue secure configuration emails</li>
                        <li class="mb-2"><i class="fa-solid fa-check-circle text-success me-2"></i>Register devices for WPA2-Enterprise</li>
                    </ul>

                    <div class="csv-helper mt-4">
                        <div class="fw-bold text-dark mb-1 small"><i class="fa-solid fa-circle-info me-1"></i> Required CSV Format:</div>
                        <code>name, email, student_id, department</code>
                    </div>
                </div>

                <div class="col-md-7">
                    <div class="card shadow-lg">
                        <div class="card-header">
                            <h4 class="mb-0 fw-bold">Import Student Batch</h4>
                        </div>
                        <div class="card-body p-4 p-lg-5">
                            

                        
                            @if(session('success'))
                                <div class="alert alert-success d-flex align-items-center mb-4">
                                    <i class="fa-solid fa-circle-check me-3 fs-4"></i>
                                    <div><strong>Success!</strong> {{ session('success') }}</div>
                                </div>
                            @endif

                            @if(session('report'))
                                <div class="row g-2 mb-4">
                                    <div class="col-6">
                                        <div class="p-3 border rounded text-center">
                                            <div class="small text-muted text-uppercase">Provisioned</div>
                                            <div class="h4 fw-bold text-success mb-0">{{ session('report.sent') }}</div>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="p-3 border rounded text-center">
                                            <div class="small text-muted text-uppercase">Errors</div>
                                            <div class="h4 fw-bold text-danger mb-0">{{ session('report.failed') }}</div>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            <form action="{{ route('upload.csv') }}" method="POST" enctype="multipart/form-data">
                                @csrf
                                
                                <div class="mb-4">
                                    <label class="form-label d-block text-center upload-zone" for="csv_file">
                                        <i class="fa-solid fa-file-csv fa-3x text-muted mb-3"></i>
                                        <span class="d-block fw-bold text-dark">Click to select CSV file</span>
                                        <span class="text-muted small">or drag and drop student list here</span>
                                        <input type="file" name="csv_file" id="csv_file" class="form-control mt-3" required hidden>
                                        <div id="file-name" class="mt-2 text-primary fw-bold small"></div>
                                    </label>
                                </div>

                                <div class="d-grid">
                                    <button type="submit" class="btn btn-eduroam shadow-sm">
                                        <i class="fa-solid fa-user-plus me-2"></i> Upload Users
                                    </button>
                                </div>
                            </form>
                            
                            <div class="mt-4 pt-3 border-top text-center">
                                <p class="text-muted x-small mb-0" style="font-size: 0.75rem;">
                                    <i class="fa-solid fa-shield-halved me-1"></i> 
                                    UNILIA Eduroam Portal &copy; 2025. All rights reserved.
                                </p>
                            </div>

                        </div>
                    </div>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
    // Simple script to show selected filename
    document.getElementById('csv_file').onchange = function () {
        document.getElementById('file-name').innerHTML = '<i class="fa-solid fa-file-circle-check"></i> ' + this.files[0].name;
    };
</script>

</body>
</html>