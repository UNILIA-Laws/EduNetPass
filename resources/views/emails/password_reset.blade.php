<p>Dear {{ $student['givenName'] }} {{ $student['sn'] }},</p>

<p>The password for your <strong>eduroam</strong> account has been reset by the ICT Office. Please use the new details below to connect to the school Wi-Fi.</p>

<ul>
    <li><strong>Username (UID):</strong> {{ $student['uid'] }}</li>
    <li><strong>New password:</strong> {{ $student['userPassword'] }}</li>
</ul>

<p><strong>Security Tip:</strong> Keep your login details private and do not share them with anyone. Your old password no longer works, so remember to update it on your devices.</p>

<p>If you did not ask for this change, or you need help, please contact the <strong>ICT Office</strong>:<br>
Email: ictlaws@unilia.ac.mw<br>
Phone: +265 882 795 006</p>

<p>Regards,<br>
Laws Campus ICT Team</p>