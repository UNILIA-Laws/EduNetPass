<!DOCTYPE html>
<html>
<head>
    <title>Upload Student CSV</title>
</head>
<body>
    <h2>Upload CSV File to Send Credentials</h2>

    @if(session('success'))
        <p style="color:green;">{{ session('success') }}</p>
    @endif

    @if($errors->any())
        <ul style="color:red;">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    @endif

    <form action="{{ route('upload.csv') }}" method="POST" enctype="multipart/form-data">
        @csrf
        <input type="file" name="csv_file" required>
        <br><br>
        <button type="submit">Upload & Send Emails</button>
    </form>
</body>
</html>
