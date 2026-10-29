<?php

return [

    /*
    |--------------------------------------------------------------------------
    | LDAP connection
    |--------------------------------------------------------------------------
    | Credentials live in .env, never in code.
    | Use ldaps://host:636 (or LDAP_START_TLS=true) so the admin password and
    | the users' passwords are not sent over the network in clear text.
    */
    'uri'           => env('LDAP_URI', 'ldap://41.70.8.28:389'),
    'start_tls'     => env('LDAP_START_TLS', false),
    'bind_dn'       => env('LDAP_BIND_DN', 'cn=admin,dc=unilia,dc=ac,dc=mw'),
    'bind_password' => env('LDAP_BIND_PASSWORD'),
    'timeout'       => env('LDAP_TIMEOUT', 10),

    /*
    |--------------------------------------------------------------------------
    | Directory layout
    |--------------------------------------------------------------------------
    | All user types (student, staff, ...) live under people_dn and are told
    | apart by eduPersonAffiliation.
    |
    | Campuses are NOT listed here any more: every organizationalUnit directly
    | under campuses_dn is a campus, read live from the LDAP server.
    | Add a campus by creating an OU there (Organizational units page).
    |
    | Optional keys (derived from people_dn when omitted):
    |   'base_dn'   => 'dc=unilia,dc=ac,dc=mw'
    |   'groups_dn' => 'ou=groups,dc=unilia,dc=ac,dc=mw'
    */
    'people_dn'   => env('LDAP_PEOPLE_DN', 'ou=people,dc=unilia,dc=ac,dc=mw'),
    'campuses_dn' => env('LDAP_CAMPUSES_DN', 'ou=campuses,dc=unilia,dc=ac,dc=mw'),

    // Appended to the OU name to build the dropdown label: ou=laws -> "Laws Campus"
    'campus_label_suffix' => ' Campus',

    'entitlements' => [
        'urn:mace:dir:entitlement:common-lib-terms',
        'urn:mace:terena.org:tcs:personal-user',
    ],

    /*
    |--------------------------------------------------------------------------
    | User types
    |--------------------------------------------------------------------------
    | - The FIRST affiliation of each type is its "primary" one. It is used to
    |   filter searches and to detect the type of an existing account, so every
    |   type needs a different primary affiliation.
    | - 'entitlements' => []  means "use the global entitlements above".
    | - Optional per type:
    |     'uses_reg' => true|false   show the registration-number field (default: students only)
    |     'uid_hint' => '...'        placeholder shown in the username box
    */
    'user_types' => [
        'student' => [
            'label'        => 'Student',
            'affiliations' => ['student', 'member'],
            'entitlements' => [],
            'required'     => ['givenName', 'sn', 'uid', 'mail'],
            'uses_reg'     => true,
            'uid_hint'     => 'beh-01-001-24',
        ],
        'staff' => [
            'label'        => 'Staff',
            'affiliations' => ['staff', 'employee', 'member'],
            'entitlements' => [],
            'required'     => ['givenName', 'sn', 'uid', 'mail'],
            'uses_reg'     => false,
            'uid_hint'     => 'jsmith',
        ],
        'faculty' => [
            'label'        => 'Lecturer / Faculty',
            'affiliations' => ['faculty', 'employee', 'member'],
            'entitlements' => [],
            'required'     => ['givenName', 'sn', 'uid', 'mail'],
            'uses_reg'     => false,
            'uid_hint'     => 'jsmith',
        ],
        'guest' => [
            'label'        => 'Guest / Visitor',
            'affiliations' => ['affiliate'],
            'entitlements' => [],
            'required'     => ['givenName', 'sn', 'uid', 'mail'],
            'uses_reg'     => false,
            'uid_hint'     => 'guest.name',
        ],
    ],

    // Who receives the bulk-upload log e-mail
    'log_recipient' => env('PROVISIONING_LOG_EMAIL', 'ictlaws@unilia.ac.mw'),

    'max_rows_per_upload' => 2000,

    'max_list' => 5000,
    'per_page' => 25,

    'page_size' => 500,
];