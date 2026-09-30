# UNILIA Eduroam Provisioning Portal

Laravel web app for ICT staff to create eduroam accounts in LDAP and e-mail the credentials to students.

* **Bulk upload** – CSV or Excel (`givenName, sn, Reg, mail, uid, userPassword`)
* **Single student** – web form
* For each student: create/update in LDAP → e-mail credentials (only if LDAP succeeded) → per-student result table
* Bulk runs also e-mail a log (no passwords) to `PROVISIONING_LOG_EMAIL`
* Login required (staff accounts live in the `users` table)

## Setup

```bash
composer install
composer require phpoffice/phpspreadsheet      # only needed for .xlsx uploads
cp .env.example .env && php artisan key:generate
# edit .env: LDAP_BIND_PASSWORD, MAIL_* , APP_URL
php artisan migrate
php artisan eduroam:make-admin ict@unilia.ac.mw   # creates a login
php artisan serve            # or point Apache/Nginx at /public
```

Requirements: PHP 8.2+ with the **ldap** extension (`sudo apt install php-ldap`, then restart PHP/Apache).

## Adding a campus
Add an entry to `campuses` in `config/ldap.php`; it appears in the Campus dropdown.

## Tests
`php artisan test` (LDAP and mail are faked, no server needed).
