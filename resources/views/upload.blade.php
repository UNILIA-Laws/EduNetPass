<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>eduroam | Bulk Provisioning</title>
    
    <link rel="icon" type="image/jpeg" href="{{ asset('unilia.jpg') }}">
    
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.2/css/all.min.css">
    <style>
        :root {
            --eduroam-blue: #003366;
            --eduroam-accent: #f39200;
        }
        body { background-color: #f4f7f9; font-family: 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; }
        
        /* Navbar Styles */
        .navbar-custom {
            background-color: white;
            border-bottom: 2px solid #e9ecef;
            padding: 0.75rem 2rem;
        }
        .navbar-brand-custom { font-weight: 800; color: var(--eduroam-blue); letter-spacing: -1px; text-decoration: none; font-size: 1.5rem; }
        .logo-img { height: 45px; width: auto; border-radius: 4px; }

        /* Content Styles */
        .card { border: none; border-radius: 16px; overflow: hidden; }
        .card-header { background-color: var(--eduroam-blue) !important; color: white; padding: 1.2rem; border: none; }
        .btn-eduroam { background-color: var(--eduroam-blue); color: white; border-radius: 8px; padding: 12px; transition: 0.3s; border: none; }
        .btn-eduroam:hover { background-color: #002244; color: white; box-shadow: 0 4px 12px rgba(0,51,102,0.2); }
        
        .btn-sample { border: 1px solid var(--eduroam-blue); color: var(--eduroam-blue); background: transparent; font-size: 0.8rem; border-radius: 6px; padding: 5px 10px; transition: 0.3s; text-decoration: none; }
        .btn-sample:hover { background: var(--eduroam-blue); color: white; }
        
        .csv-helper { font-size: 0.85rem; background: #fff3cd; border-radius: 8px; padding: 15px; border-left: 4px solid #ffc107; }
        .upload-zone { border: 2px dashed #dee2e6; border-radius: 12px; padding: 30px 20px; transition: 0.3s; background: #fafafa; cursor: pointer; }
        .upload-zone:hover { border-color: var(--eduroam-blue); background: #f0f5ff; }
    </style>
</head>
<body>

<nav class="navbar navbar-custom sticky-top">
    <div class="container-fluid">
        <div class="d-flex align-items-center">
            <img src="{{ asset('unilia.jpg') }}" alt="UNILIA Logo" class="logo-img me-3">
            <a href="#" class="navbar-brand-custom">
                <i class="fa-solid fa-wifi me-2"></i>Laws Eduroam
            </a>
        </div>
        <div class="text-muted d-none d-md-block">
            <small>Portal for sending student credentials</small>
        </div>
    </div>
</nav>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-11">
            
            <div class="row g-5">
                <div class="col-md-5">
                    <h5 class="fw-bold">Bulk User Enrollment</h5>
                    <p class="text-secondary small">Upload student rosters to automatically:</p>
                    
                    <ul class="list-unstyled small text-secondary">
                        <li class="mb-2"><i class="fa-solid fa-check-circle text-success me-2"></i>Generate unique RADIUS credentials</li>
                        <li class="mb-2"><i class="fa-solid fa-check-circle text-success me-2"></i>Queue secure configuration emails</li>
                        <li class="mb-2"><i class="fa-solid fa-check-circle text-success me-2"></i>Register devices for WPA2-Enterprise</li>
                    </ul>

                    <div class="csv-helper mt-4 shadow-sm border-0 bg-white p-3 rounded-4">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <div class="d-flex align-items-center">
                                <div class="icon-box bg-warning bg-opacity-10 text-warning rounded-circle p-2 me-3">
                                    <i class="fa-solid fa-file-csv fs-5"></i>
                                </div>
                                <div>
                                    <h6 class="mb-0 fw-bold text-dark">CSV Configuration</h6>
                                    <small class="text-muted">Required column mapping</small>
                                </div>
                            </div>
                            <a href="{{ route('download.sample') }}" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                                <i class="fa-solid fa-download me-1"></i> Sample CSV
                            </a>
                        </div>

                        <div class="bg-light p-3 rounded-3 border">
                            <div class="d-flex justify-content-between small text-uppercase text-muted fw-bold mb-2" style="font-size: 0.65rem; letter-spacing: 1px;">
                                <span>Structure</span>
                                <span class="text-primary">Header Row Required</span>
                            </div>
                            <code class="text-primary fw-bold" style="font-size: 0.9rem;">name, email, student_id, department</code>
                        </div>
                        
                        <p class="mb-0 mt-2 text-muted" style="font-size: 0.75rem;">
                            <i class="fa-solid fa-circle-exclamation me-1"></i> Ensure emails are valid institution addresses.
                        </p>
                    </div>
                </div>

                <div class="col-md-7">
                    <div class="card shadow-lg">
                        <div class="card-header">
                            <h5 class="mb-0 fw-bold"><i class="fa-solid fa-file-import me-2"></i>Import Student Batch</h5>
                        </div>
                        <div class="card-body p-4">
                            
                            @if(session('success'))
                                <div class="alert alert-success d-flex align-items-center mb-4">
                                    <i class="fa-solid fa-circle-check me-3 fs-4"></i>
                                    <div><strong>Success!</strong> {{ session('success') }}</div>
                                </div>
                            @endif

                            @if(session('report'))
                                <div class="row g-2 mb-4">
                                    <div class="col-6">
                                        <div class="p-3 border rounded text-center bg-light">
                                            <div class="small text-muted text-uppercase">Sent</div>
                                            <div class="h4 fw-bold text-success mb-0">{{ session('report.sent') }}</div>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="p-3 border rounded text-center bg-light">
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
                                        <i class="fa-solid fa-paper-plane me-2"></i> Process and Send Credentials
                                    </button>
                                </div>
                            </form>
                            
                            <div class="mt-4 pt-3 border-top text-center">
                                <p class="text-muted" style="font-size: 0.75rem;">
                                    <i class="fa-solid fa-shield-halved me-1"></i> 
                                    UNILIA Eduroam Portal &copy; 2026. All rights reserved.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div> </div>
    </div>
</div>

<script>
    document.getElementById('csv_file').onchange = function () {
        if (this.files.length > 0) {
            document.getElementById('file-name').innerHTML = '<i class="fa-solid fa-file-circle-check"></i> ' + this.files[0].name;
        }
    };
</script>

</body>
</html>