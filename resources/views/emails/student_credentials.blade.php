<p>Dear {{ $student['givenName'] }} {{ $student['sn'] }},</p>

<p>Your system login details are:</p>

<ul>
    <li><strong>Registration Number:</strong> {{ $student['Reg'] }}</li>
    <li><strong>Username (UID):</strong> {{ $student['uid'] }}</li>
    <li><strong>Password:</strong> {{ $student['userPassword'] }}</li>
</ul>

<p>Please change your password after first login.</p>

<p>Regards,<br>UNILIA ICT Team</p>
