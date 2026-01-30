<p>Dear {{ $student['givenName'] }} {{ $student['sn'] }}, ({{ $student['Reg'] }})</p>

<p>Your <strong>eduroam</strong> login details have been generated. Please use the credentials below to access the network:</p>

<ul>
    <li><strong>eduroam Identity (UID):</strong> {{ $student['uid'] }}</li>
    <li><strong>Password:</strong> {{ $student['userPassword'] }}</li>
</ul>

<p><strong>⚠️ Security Notice:</strong> For security reasons, please <strong>do not share these credentials</strong> with anyone else. Your account is for your personal use only.</p>

<p>Please find the attached <strong>User Guide</strong> for setup instructions. If you encounter any issues, please contact the <strong>ICT Office</strong>:</p>

<ul>
    <li><strong>Email:</strong> ictlaws@unilia.ac.mw</li>
    <li><strong>Phone:</strong> +265 882 795 006</li>
</ul>

<p>Regards,<br>
Laws Campus ICT Team</p>