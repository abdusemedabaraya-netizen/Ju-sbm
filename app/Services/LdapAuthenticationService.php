<?php

namespace App\Services;

class LdapAuthenticationService
{
    public function authenticate(string $username, string $password): ?array
    {
        if (! $this->enabled()) {
            return null;
        }

        $username = trim($username);
        $password = (string) $password;

        if ($username === '' || $password === '') {
            return null;
        }

        $connectionConfig = $this->connection();

        $ldap = $this->connect($connectionConfig);
        if (! $ldap) {
            return null;
        }

        if (! empty($connectionConfig['username'])) {
            if (! @ldap_bind($ldap, $connectionConfig['username'], $connectionConfig['password'])) {
                ldap_close($ldap);
                return null;
            }
        } elseif (! @ldap_bind($ldap)) {
            ldap_close($ldap);
            return null;
        }

        $usernameAttr = strtolower(config('ldap.username_attr'));
        $nameAttr = strtolower(config('ldap.name_attr'));
        $emailAttr = strtolower(config('ldap.email_attr'));

        $filter = '(' . config('ldap.username_attr') . '='
            . ldap_escape($username, '', LDAP_ESCAPE_FILTER) . ')';

        $search = @ldap_search(
            $ldap,
            config('ldap.user_ou') ?: $connectionConfig['base_dn'],
            $filter,
            [config('ldap.username_attr'), config('ldap.name_attr'), config('ldap.email_attr')],
            0,
            1
        );

        if (! $search) {
            ldap_close($ldap);
            return null;
        }

        $entries = ldap_get_entries($ldap, $search);
        ldap_close($ldap);

        if (($entries['count'] ?? 0) < 1) {
            return null;
        }

        $entry = $entries[0];
        $dn = $entry['dn'];

        $name = trim((string) ($entry[$nameAttr][0] ?? $username));
        $email = trim((string) ($entry[$emailAttr][0] ?? ''));

        if ($email === '') {
            $email = $username . '@ju.edu.et';
        }

        $verify = $this->connect($connectionConfig);
        if (! $verify) {
            return null;
        }

        $bound = @ldap_bind($verify, $dn, $password);
        ldap_close($verify);

        if (! $bound) {
            return null;
        }

        return [
            'dn' => $dn,
            'username' => (string) ($entry[$usernameAttr][0] ?? $username),
            'name' => $name,
            'email' => $email,
        ];
    }

    private function enabled(): bool
    {
        return true;
    }

    private function connection(): array
    {
        $name = config('ldap.default', 'default');
        return config('ldap.connections.' . $name, []);
    }

    private function connect(array $connectionConfig): mixed
    {
        $url = $this->buildUrl($connectionConfig);

        $ldap = @ldap_connect($url);
        if (! $ldap) {
            return null;
        }

        ldap_set_option($ldap, LDAP_OPT_PROTOCOL_VERSION, 3);
        ldap_set_option($ldap, LDAP_OPT_REFERRALS, 0);
        ldap_set_option($ldap, LDAP_OPT_NETWORK_TIMEOUT, (int) ($connectionConfig['timeout'] ?? 5));

        if (! empty($connectionConfig['tls'])) {
            if (! @ldap_start_tls($ldap)) {
                ldap_close($ldap);
                return null;
            }
        }

        return $ldap;
    }

    private function buildUrl(array $connectionConfig): string
    {
        $host = trim((string) ($connectionConfig['host'] ?? '127.0.0.1'));
        $port = (int) ($connectionConfig['port'] ?? 389);
        $ssl = (bool) ($connectionConfig['ssl'] ?? false);

        if (str_starts_with($host, 'ldap://') || str_starts_with($host, 'ldaps://')) {
            $parsedHost = parse_url($host, PHP_URL_HOST);
            $host = $parsedHost ?: preg_replace('#^ldaps?://#', '', $host);
        }

        if ($host === '') {
            $host = '127.0.0.1';
        }

        $scheme = $ssl ? 'ldaps' : 'ldap';

        return $scheme . '://' . $host . ':' . $port;
    }
}