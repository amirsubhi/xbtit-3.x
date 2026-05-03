<?php

namespace App\Services;

/**
 * Verifies passwords hashed by the legacy xbtit system.
 *
 * On a successful match the caller must rehash with Hash::make() and clear pass_type.
 * Type 7 was never implemented in the legacy codebase — those accounts must reset.
 */
class LegacyPasswordVerifier
{
    /**
     * @param  string      $plaintext  Password entered by the user
     * @param  string      $stored     The stored hash from users.password
     * @param  string      $passType   The users.pass_type enum value ('1'–'7')
     * @param  string      $salt       The users.salt value
     * @param  string      $username   Lowercase username (needed for type 5)
     * @param  string      $sitesecret The secsui_ss site secret (needed for type 4)
     */
    public function verify(
        string $plaintext,
        string $stored,
        string $passType,
        string $salt,
        string $username,
        string $sitesecret = ''
    ): bool {
        $computed = match ($passType) {
            '1' => $this->type1($plaintext),
            '2' => $this->type2($plaintext, $salt),
            '3' => $this->type3($plaintext, $salt),
            '4' => $this->type4($plaintext, $salt, $sitesecret),
            '5' => $this->type5($plaintext, $username),
            '6' => $this->type6($plaintext, $salt),
            '7' => null,  // Never implemented — account is permanently locked
            default => null,
        };

        if ($computed === null) {
            return false;
        }

        return hash_equals($stored, $computed);
    }

    /**
     * Type 7 has no hash implementation in the legacy code.
     * Any account with pass_type=7 cannot log in via password and must reset.
     */
    public function requiresPasswordReset(string $passType): bool
    {
        return $passType === '7';
    }

    // btit/xbtit/Torrent Trader/phpMyBitTorrent — no salt
    private function type1(string $pwd): string
    {
        return md5($pwd);
    }

    // TBDev/U-232/SZ Edition/Invision Power Board
    private function type2(string $pwd, string $salt): string
    {
        return md5(md5($salt) . md5($pwd));
    }

    // Free Torrent Source/Yuna Scatari/TorrentStrike/TSSE
    private function type3(string $pwd, string $salt): string
    {
        return md5($salt . $pwd . $salt);
    }

    // Gazelle
    private function type4(string $pwd, string $salt, string $sitesecret): string
    {
        return sha1(md5($salt) . $pwd . sha1($salt) . $sitesecret);
    }

    // Simple Machines Forum (SMF 1.x) — no salt, username-salted
    private function type5(string $pwd, string $username): string
    {
        return sha1(strtolower($username) . $pwd);
    }

    // xbtit custom hybrid
    private function type6(string $pwd, string $salt): string
    {
        $md5pwd = md5($pwd);
        $compound = substr($md5pwd, 0, 16) . '-' . md5($salt) . '-' . substr($md5pwd, 16, 16);

        return sha1($compound);
    }
}
