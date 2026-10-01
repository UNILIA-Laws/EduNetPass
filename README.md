# EduNetPass

A web portal for IT staff to create network (eduroam / Wi-Fi) accounts in an LDAP directory and email the login details to students, one at a time or in bulk. It was built for the Laws Campus of UNILIA (Malawi), but any school, campus or organisation with an LDAP directory can use it.

## What it does

- **Bulk upload.** Upload a CSV or Excel file and every person gets an account.
- **Single user.** Add one person through a web form.
- **LDAP first, email second.** The account is created (or updated if the username already exists) in LDAP. The credentials email is sent only if that succeeded.
- **Auto-generated passwords.** Leave the password blank and a secure one is generated.
- **Per-person results.** After each run you see what was created, updated, emailed or failed, with the reason.
- **Audit log.** Each bulk run emails a log (without passwords) to an address you choose.
- **Staff login.** Only logged-in staff can use the portal.

## Requirements

- PHP 8.2 or newer with the **ldap** extension
- Composer
- A database (SQLite works out of the box; MySQL/PostgreSQL also work)
- An LDAP server you can write to, and an SMTP account for sending email

Your LDAP directory should have:

- a container for users (default `ou=people,<base DN>`)
- the `inetOrgPerson` and `eduPerson` object classes
- an account allowed to add and modify entries under that container

## Installation

```bash
git clone <your-repo-url> edunetpass
cd edunetpass

composer install
composer require phpoffice/phpspreadsheet    # only needed for .xlsx uploads

cp .env.example .env
php artisan key:generate
```

Edit `.env` (see the table below), then:

```bash
php artisan migrate
php artisan eduroam:make-admin staff@yourschool.edu   # creates a staff login
php artisan serve                                      # for testing
```

For production, point Apache or Nginx at the `public/` folder.

Enable the LDAP extension if needed, for example on Ubuntu:

```bash
sudo apt install php-ldap
sudo systemctl restart apache2   # or php-fpm
```

## Configuration (`.env`)

| Setting | Meaning | Example |
|---|---|---|
| `LDAP_URI` | LDAP server address | `ldaps://ldap.yourschool.edu:636` |
| `LDAP_START_TLS` | Upgrade a plain `ldap://` connection to TLS | `true` |
| `LDAP_BIND_DN` | Admin account used to write to LDAP | `cn=admin,dc=yourschool,dc=edu` |
| `LDAP_BIND_PASSWORD` | Password for that account | *(keep secret)* |
| `LDAP_PEOPLE_DN` | Where user entries are created | `ou=people,dc=yourschool,dc=edu` |
| `PROVISIONING_LOG_EMAIL` | Who receives the bulk-upload log | `ict@yourschool.edu` |
| `MAIL_*` | SMTP settings for sending emails | your mail provider's details |

Never commit `.env`. It contains passwords.

## File format for bulk upload

The first row must contain these headers (order does not matter):

| Column | Required | Notes |
|---|---|---|
| `givenName` | yes | First name |
| `sn` | yes | Surname |
| `mail` | yes | Where the credentials are sent |
| `uid` | yes | Username. Letters, numbers, `.` `-` `_` only |
| `userPassword` | no | Leave blank to auto-generate |
| `Reg` | no | Registration/staff number, shown in the email |

Use the **Sample** button in the portal to download a ready-made file.

## Customising for your institution

**Campuses or departments.** Add entries in `config/ldap.php` under `campuses`. Each one appears in the Campus dropdown:

```php
'campuses' => [
    'main'  => ['label' => 'Main Campus',  'dn' => 'ou=main,ou=campuses,dc=yourschool,dc=edu'],
    'north' => ['label' => 'North Campus', 'dn' => 'ou=north,ou=campuses,dc=yourschool,dc=edu'],
],
```

**Entitlements.** Change `entitlements` in `config/ldap.php`.

**Branding and wording.** These are plain text and image files; edit them directly:

| What | File |
|---|---|
| Logo and favicon | `public/unilia.jpg` (replace the image, or change the path in `resources/views/layouts/app.blade.php`) |
| Site name in the header ("Laws Eduroam") | `resources/views/layouts/app.blade.php` |
| Email text, support contacts, user-guide link, sign-off | `resources/views/emails/student_credentials.blade.php` |
| Log email text | `resources/views/emails/log_email.blade.php` |
| Email subject lines | `app/Mail/StudentCredentialsMail.php`, `app/Mail/SendLogMail.php` |

**LDAP attributes.** What gets written to each LDAP entry is in one place: the `upsertStudent()` method in `app/Services/LdapService.php`. Edit it if your directory uses different object classes, attributes or password hashing (it currently uses salted SHA-1, `{SSHA}`).

**Roles other than students.** To create staff or guest accounts, change the `eduPersonAffiliation` value in `LdapService.php`.

## Security notes

- Use `ldaps://` or StartTLS. Over plain `ldap://`, admin and user passwords travel across the network unencrypted.
- Keep the LDAP admin password only in `.env`. Consider a dedicated LDAP account that can write only to your people container, rather than the full admin.
- Serve the site over HTTPS.
- Create staff logins only for people who should be able to create network accounts.
- Credentials are sent by email in plain text, so tell students to change or protect them.
- Emails are sent while the page loads. For very large batches, switch to a queue worker (`QUEUE_CONNECTION=database` and `php artisan queue:work`).

## Troubleshooting

| Problem | Likely cause |
|---|---|
| "The PHP LDAP extension is not enabled" | Install and enable `php-ldap`, then restart PHP |
| "Could not connect/bind to LDAP" | Wrong `LDAP_URI`, bind DN or password; firewall blocking the LDAP port |
| "LDAP add failed: Insufficient access" | The bind account can't write to `LDAP_PEOPLE_DN` |
| "LDAP add failed: Object class violation" | Your directory is missing the `eduPerson` schema, or required attributes differ |
| Accounts created but no emails | Check the `MAIL_*` settings; failed emails show in the results table |
| "Excel support is not installed" | Run `composer require phpoffice/phpspreadsheet` |

Application errors are written to `storage/logs/laravel.log`.

## Tests

```bash
php artisan test
```

The tests use fake LDAP and mail, so no real server is needed.

## License

MIT. Free to use and adapt.