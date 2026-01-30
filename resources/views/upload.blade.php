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
        body { 
            background-color: #f4f7f9; 
            font-family: 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; 
            min-height: 100vh;
        }
        
        /* Navbar Styles */
        .navbar-custom {
            background-color: white;
            border-bottom: 2px solid #e9ecef;
            padding: 0.75rem 2rem;
        }
        .navbar-brand-custom { 
            font-weight: 800; 
            color: var(--eduroam-blue); 
            letter-spacing: -1px; 
            text-decoration: none; 
            font-size: 1.5rem; 
        }
        .logo-img { height: 45px; width: auto; border-radius: 4px; }

        /* Card & UI Elements */
        .card { border: none; border-radius: 16px; overflow: hidden; }
        .card-header { 
            background-color: var(--eduroam-blue) !important; 
            color: white; 
            padding: 1.2rem; 
            border: none; 
        }
        .btn-eduroam { 
            background-color: var(--eduroam-blue); 
            color: white; 
            border-radius: 8px; 
            padding: 12px; 
            transition: 0.3s; 
            border: none; 
            font-weight: 600;
        }
        .btn-eduroam:hover { 
            background-color: #002244; 
            color: white; 
            box-shadow: 0 4px 12px rgba(0,51,102,0.2); 
        }
        
        /* CSV Helper Box */
        .csv-helper-card { 
            background: white; 
            border-radius: 16px; 
            padding: 20px; 
            border: 1px solid #e9ecef;
        }
        .code-block {
            background: #f8f9fa;
            padding: 12px;
            border-radius: 8px;
            border: 1px solid #dee2e6;
            font-family: 'Courier New', Courier, monospace;
            color: var(--eduroam-blue);
            font-weight: bold;
            display: block;
            margin-top: 10px;
        }

        /* Upload Zone */
        .upload-zone { 
            border: 2px dashed #dee2e6; 
            border-radius: 12px; 
            padding: 40px 20px; 
            transition: 0.3s; 
            background: #fafafa; 
            cursor: pointer; 
        }
        .upload-zone:hover { 
            border-color: var(--eduroam-blue); 
            background: #f0f5ff; 
        }

        .spinner-border-sm {
            width: 1rem;
            height: 1rem;
            border-width: 0.2em;
        }
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
            <small><i class="fa-solid fa-shield-halved me-1"></i> Student Credential Portal</small>
        </div>
    </div>
</nav>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-11">
            
            <div class="row g-5">
                <div class="col-md-5">
                    <h4 class="fw-bold text-dark mb-3">Bulk User Enrollment</h4>
                    <p class="text-secondary">Automate the distribution of eduroam network credentials to your student body.</p>
                    
                    <ul class="list-unstyled small text-secondary mb-4">
                        <li class="mb-3 d-flex align-items-start">
                            <i class="fa-solid fa-circle-check text-success me-2 mt-1"></i>
                            <span>Generate unique WPA2-Enterprise RADIUS credentials.</span>
                        </li>
                        <li class="mb-3 d-flex align-items-start">
                            <i class="fa-solid fa-circle-check text-success me-2 mt-1"></i>
                            <span>Email secure configuration profiles to students.</span>
                        </li>
                    </ul>

                    <div class="csv-helper-card shadow-sm">
                        <div class="d-flex align-items-center justify-content-between mb-3">
                            <div class="d-flex align-items-center">
                                <div class="bg-warning bg-opacity-10 text-warning rounded-circle p-2 me-3">
                                    <i class="fa-solid fa-file-csv fs-5"></i>
                                </div>
                                <div>
                                    <h6 class="mb-0 fw-bold">Data Format</h6>
                                    <small class="text-muted">Required CSV headers</small>
                                </div>
                            </div>
                            <a href="{{ route('download.sample') }}" class="btn btn-sm btn-outline-primary rounded-pill px-3">
                                <i class="fa-solid fa-download me-1"></i> Sample
                            </a>
                        </div>
                        <div class="code-block">
                            name, email, student_id, department
                        </div>
                        <p class="small text-muted mt-3 mb-0">
                            <i class="fa-solid fa-circle-info me-1"></i> Please ensure the first row contains these exact headers.
                        </p>
                    </div>
                </div>

                <div class="col-md-7">
                    <div class="card shadow-lg">
                        <div class="card-header d-flex align-items-center">
                            <i class="fa-solid fa-file-import me-3 fs-4"></i>
                            <h5 class="mb-0 fw-bold">Import Student Batch</h5>
                        </div>
                        <div class="card-body p-4 p-lg-5">
                            
                            @if(session('success'))
                                <div class="alert alert-success d-flex align-items-center mb-4 border-0 shadow-sm">
                                    <i class="fa-solid fa-circle-check me-3 fs-4"></i>
                                    <div><strong>Success!</strong> {{ session('success') }}</div>
                                </div>
                            @endif

                            @if(session('report'))
                                <div class="row g-2 mb-4">
                                    <div class="col-6">
                                        <div class="p-3 border rounded-3 text-center bg-light">
                                            <div class="small text-muted text-uppercase fw-bold" style="font-size: 0.7rem;">Sent</div>
                                            <div class="h4 fw-bold text-success mb-0">{{ session('report.sent') }}</div>
                                        </div>
                                    </div>
                                    <div class="col-6">
                                        <div class="p-3 border rounded-3 text-center bg-light">
                                            <div class="small text-muted text-uppercase fw-bold" style="font-size: 0.7rem;">Errors</div>
                                            <div class="h4 fw-bold text-danger mb-0">{{ session('report.failed') }}</div>
                                        </div>
                                    </div>
                                </div>
                            @endif

                            <form action="{{ route('upload.csv') }}" method="POST" enctype="multipart/form-data" id="uploadForm">
                                @csrf
                                <div class="mb-4 text-center">
                                    <label class="upload-zone d-block" for="csv_file">
                                        <i class="fa-solid fa-cloud-arrow-up fa-3x text-muted mb-3"></i>
                                        <span class="d-block fw-bold text-dark fs-5">Choose CSV File</span>
                                        <span class="text-muted small">or drag and drop the file here</span>
                                        <input type="file" name="csv_file" id="csv_file" class="form-control mt-3" required hidden>
                                        <div id="file-name" class="mt-3 text-primary fw-bold small"></div>
                                    </label>
                                </div>

                                <div class="d-grid">
                                    <button type="submit" id="submitBtn" class="btn btn-eduroam shadow-sm">
                                        <span id="btnText">
                                            <i class="fa-solid fa-paper-plane me-2"></i> Process and Send Credentials
                                        </span>
                                        <span id="btnLoading" class="d-none">
                                            <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>
                                            Sending Emails... Please Wait
                                        </span>
                                    </button>
                                </div>
                            </form>
                            
                            <div class="mt-5 pt-3 border-top text-center">
                                <p class="text-muted mb-0" style="font-size: 0.75rem;">
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
    const csvInput = document.getElementById('csv_file');
    const fileNameDisplay = document.getElementById('file-name');
    const uploadForm = document.getElementById('uploadForm');
    const submitBtn = document.getElementById('submitBtn');
    const btnText = document.getElementById('btnText');
    const btnLoading = document.getElementById('btnLoading');

    // Display selected file name
    csvInput.onchange = function () {
        if (this.files.length > 0) {
            fileNameDisplay.innerHTML = `
                <div class="alert alert-info py-2 mb-0">
                    <i class="fa-solid fa-file-circle-check me-2"></i>${this.files[0].name}
                </div>`;
        }
    };

    // Handle Processing/Loading State
    uploadForm.onsubmit = function() {
        // Disable button
        submitBtn.disabled = true;
        submitBtn.style.opacity = "0.8";

        // Toggle visibility of text and spinner
        btnText.classList.add('d-none');
        btnLoading.classList.remove('d-none');

        return true; 
    };
</script>

</body>
</html>