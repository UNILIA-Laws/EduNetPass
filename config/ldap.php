<?php

return [

    /*
    |--------------------------------------------------------------------------
    | LDAP connection
    |--------------------------------------------------------------------------
    | Credentials live in .env, never in code.
    | Use ldaps://host:636 (or LDAP_START_TLS=true) so the admin password and
    | the students' passwords are not sent over the network in clear text.
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
    */
    'people_dn' => env('LDAP_PEOPLE_DN', 'ou=people,dc=unilia,dc=ac,dc=mw'),

    // Campuses shown in the "Campus" dropdown. key => [label, eduPersonOrgUnitDN]
    'campuses' => [
        'laws' => [
            'label' => 'Laws Campus',
            'dn'    => 'ou=laws,ou=campuses,dc=unilia,dc=ac,dc=mw',
        ],
        // 'chanco' => ['label' => 'Chancellor College', 'dn' => 'ou=chanco,ou=campuses,dc=unilia,dc=ac,dc=mw'],
    ],

    'entitlements' => [
        'urn:mace:dir:entitlement:common-lib-terms',
        'urn:mace:terena.org:tcs:personal-user',
    ],

    // Who receives the bulk-upload log e-mail
    'log_recipient' => env('PROVISIONING_LOG_EMAIL', 'ictlaws@unilia.ac.mw'),

    'max_rows_per_upload' => 2000,


     
    'max_list' => 5000,
    'per_page' => 25,

    'page_size' => 500,
];
