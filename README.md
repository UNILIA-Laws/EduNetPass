# EduNetPass

A web portal for IT staff to create network (eduroam / Wi-Fi) accounts in an LDAP directory and email the login details to students, one at a time or in bulk. Staff can also browse the users already in the directory, edit their details and reset their passwords. It was built for the Laws Campus of UNILIA (Malawi), but any school, campus or organisation with an LDAP directory can use it.

## What it does

- **Bulk upload.** Upload a CSV or Excel file and every person gets an account.
- **Single user.** Add one person through a web form.
- **LDAP first, email second.** The account is created (or updated if the username already exists) in LDAP. The credentials email is sent only if that succeeded.
- **Auto-generated passwords.** Leave the password blank and a secure one is generated.
- **Per-person results.** After each run you see what was created, updated, emailed or failed, with the reason.
- **Users page.** Browse everyone stored in LDAP, with search, campus filter and paging. It stays fast with thousands of users.
- **Edit users.** Change a user's name, email or campus.
- **Reset password.** Set a new password (or auto-generate one) and optionally email it to the student.
- **Audit log.** Each bulk run emails a log (without passwords) to an address you choose. Edits and password resets are written to the Laravel log with the staff member who made them.
- **Staff login.** Only logged-in staff can use the portal.

## Requirements

- PHP 8.2 or newer with the **ldap** extension
- Composer
- A database (SQLite works out of the box; MySQL/PostgreSQL also work). It stores staff logins, sessions and the cached user list.
- An LDAP server you can write to, and an SMTP account for sending email

Your LDAP directory should have:

- a container for users (default `ou=people,<base DN>`)
- the `inetOrgPerson` and `eduPerson` object classes
- an account allowed to add, read and modify entries under that container

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
| `LDAP_BIND_DN` | Admin account used to read and write LDAP | `cn=admin,dc=yourschool,dc=edu` |
| `LDAP_BIND_PASSWORD` | Password for that account | *(keep secret)* |
| `LDAP_PEOPLE_DN` | Where user entries are created | `ou=people,dc=yourschool,dc=edu` |
| `PROVISIONING_LOG_EMAIL` | Who receives the bulk-upload log | `ict@yourschool.edu` |
| `USERS_CACHE_MINUTES` | How long the Users page keeps its list before reloading from LDAP (`0` = never cache) | `10` |
| `MAIL_*` | SMTP settings for sending emails | your mail provider's details |

Never commit `.env`. It contains passwords.

Other options live in `config/ldap.php`:

| Option | Meaning | Default |
|---|---|---|
| `per_page` | Users shown per page on the Users page | 25 |
| `max_list` | Safety cap on how many users the Users page loads | 20000 |
| `page_size` | Entries requested from the LDAP server per round trip | 500 |
| `max_rows_per_upload` | Largest bulk file accepted | 2000 |

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

## Managing existing users

Open **Users** in the top bar to see everyone stored in LDAP.

- **Search** by username, name or email, and filter by campus.
- **Edit** a user's first name, surname, email and campus. The username cannot be changed, because it is part of the user's LDAP address.
- **Reset password** from the button on each row, or from the edit page. Leave the password blank to generate one, and tick the box to email it to the student. If the email is not sent or fails, the new password is shown once on screen so you can pass it on.

### How the user list stays fast

Loading thousands of users from a remote LDAP server takes several round trips, so the portal does it once and keeps the list in the Laravel cache. Searching, filtering and paging then happen in memory and take milliseconds.

- The list reloads automatically after `USERS_CACHE_MINUTES` (default 10).
- It is cleared immediately whenever you add or edit users through the portal.
- Users added outside the portal (directly in LDAP, or by a script) appear after the cache expires, or at once when you press **Refresh from LDAP**. The page shows when the list was last loaded.

**Optional: keep the cache warm** so nobody waits when it expires. Add this to `routes/console.php`:

```php
use Illuminate\Support\Facades\Schedule;

Schedule::command('eduroam:cache-users')->everyTenMinutes();
```

and add this line to the server's crontab (`crontab -e`):

```
* * * * * cd /path/to/edunetpass && php artisan schedule:run >> /dev/null 2>&1
```

You can also run `php artisan eduroam:cache-users` by hand at any time.

## Customising for your institution

**Campuses or departments.** Add entries in `config/ldap.php` under `campuses`. Each one appears in the Campus dropdowns and the Users filter:

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
| Credentials email: text, support contacts, user-guide link, sign-off | `resources/views/emails/student_credentials.blade.php` |
| Password-reset email text and sign-off | `resources/views/emails/password_reset.blade.php` |
| Log email text | `resources/views/emails/log_email.blade.php` |
| Email subject lines | `app/Mail/StudentCredentialsMail.php`, `app/Mail/PasswordResetMail.php`, `app/Mail/SendLogMail.php` |

**LDAP attributes.** Everything the portal reads and writes in LDAP is in `app/Services/LdapService.php`. `upsertStudent()` defines what is written to a new entry, and `updateStudent()` defines what the edit form changes. Edit these if your directory uses different object classes, attributes or password hashing (it currently uses salted SHA-1, `{SSHA}`). If you add attributes you want to show in the Users list, also update `searchStudents()` and `entryToArray()`.

**Roles other than students.** To create staff or guest accounts, change the `eduPersonAffiliation` value in `LdapService.php`.

## Security notes

- Use `ldaps://` or StartTLS. Over plain `ldap://`, admin and user passwords travel across the network unencrypted.
- Keep the LDAP admin password only in `.env`. Consider a dedicated LDAP account that can read and write only your people container, rather than the full admin.
- Serve the site over HTTPS.
- Every staff login can add users, edit them and reset any user's password. Create logins only for people who should have that power. Password resets and edits are recorded in `storage/logs/laravel.log` with the staff member's email.
- Credentials are sent by email in plain text, so tell students to change or protect them.
- A password shown on screen after a failed email is visible once and then discarded. Do not take screenshots of it.
- The cached user list contains names and email addresses (never passwords). If you use the database cache, it is stored in your application database, so protect that database accordingly.
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
| Users page shows "Could not read from LDAP" | Same causes as the connect/bind error; the bind account also needs read access |
| Users page is slow only after a while | The cache expired. Set up the scheduled `eduroam:cache-users` command, or raise `USERS_CACHE_MINUTES` |
| A user you just added elsewhere is missing | The list is cached. Press **Refresh from LDAP** |
| A "packet too large" error when loading Users | The cached list (about 1.3 MB per 5,000 users) exceeds an old database's packet limit. Set `CACHE_STORE=file` in `.env` |
| Users list stops at a round number | You reached `max_list` in `config/ldap.php`. Raise it, or use the search box |

Application errors are written to `storage/logs/laravel.log`.

## Tests

```bash
php artisan test
```

The standard tests use fake LDAP and mail, so no real server is needed.

There is also an optional set of integration tests that run the LDAP code against a real server. They are skipped unless you point them at a **throw-away test server** (never production) that has the `eduPerson` schema:

```bash
LDAP_TEST_URI=ldap://127.0.0.1:389 LDAP_TEST_PASSWORD=yourtestpass \
  php artisan test --filter=LdapIntegration
```

They create temporary users named `it-...` and delete them afterwards.

## License

MIT. Free to use and adapt.