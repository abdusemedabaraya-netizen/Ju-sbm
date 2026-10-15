<?php

return [
    'logging' => (bool) env('LDAP_LOGGING', false),
    'default' => env('LDAP_CONNECTION', 'default'),

    'connections' => [
        'default' => [
            'host' => env('LDAP_HOST', '127.0.0.1'),
            'port' => (int) env('LDAP_PORT', 389),
            'base_dn' => env('LDAP_BASE_DN', 'dc=ju,dc=edu,dc=et'),
            'username' => env('LDAP_USERNAME'),
            'password' => env('LDAP_PASSWORD'),
            'ssl' => (bool) env('LDAP_SSL', false),
            'tls' => (bool) env('LDAP_TLS', false),
            'timeout' => (int) env('LDAP_TIMEOUT', 5),
        ],
    ],

    'user_ou' => env('LDAP_USER_OU', 'ou=people,dc=ju,dc=edu,dc=et'),
    'username_attr' => env('LDAP_USERNAME_ATTR', 'uid'),
    'name_attr' => env('LDAP_NAME_ATTR', 'cn'),
    'email_attr' => env('LDAP_EMAIL_ATTR', 'mail'),
    'default_role' => env('LDAP_DEFAULT_ROLE', 'User'),
];