<?php

namespace App\Services;

/**
 * BitTorrent bencode encoder/decoder.
 * Ported from legacy include/BEncode.php and include/BDecode.php.
 */
class BEncodeService
{
    // -------------------------------------------------------------------------
    // Encoding
    // -------------------------------------------------------------------------

    public function encode(mixed $value): string
    {
        $out = '';
        $this->encodeValue($value, $out);

        return $out;
    }

    private function encodeValue(mixed $value, string &$out): void
    {
        if (is_bool($value)) {
            $out .= 'de';  // empty dict — legacy convention for boolean
            return;
        }

        if (is_int($value) || is_float($value)) {
            $out .= 'i' . (int) $value . 'e';
            return;
        }

        if (is_array($value)) {
            if (isset($value[0]) || empty($value)) {
                $this->encodeList($value, $out);
            } else {
                $this->encodeDict($value, $out);
            }
            return;
        }

        // String (including binary)
        $out .= strlen($value) . ':' . $value;
    }

    private function encodeList(array $list, string &$out): void
    {
        $out .= 'l';
        foreach ($list as $item) {
            $this->encodeValue($item, $out);
        }
        $out .= 'e';
    }

    private function encodeDict(array $dict, string &$out): void
    {
        $out .= 'd';
        ksort($dict);  // BEP spec requires lexicographic key order
        foreach ($dict as $key => $value) {
            $out .= strlen($key) . ':' . $key;
            $this->encodeValue($value, $out);
        }
        $out .= 'e';
    }

    // -------------------------------------------------------------------------
    // Decoding
    // -------------------------------------------------------------------------

    public function decode(string $data): mixed
    {
        $offset = 0;
        $result = $this->decodeValue($data, $offset);

        return $result;
    }

    private function decodeValue(string $data, int &$offset): mixed
    {
        if ($offset >= strlen($data)) {
            return null;
        }

        $ch = $data[$offset];

        if ($ch === 'i') {
            return $this->decodeInt($data, $offset);
        }

        if ($ch === 'l') {
            return $this->decodeList($data, $offset);
        }

        if ($ch === 'd') {
            return $this->decodeDict($data, $offset);
        }

        if ($ch >= '0' && $ch <= '9') {
            return $this->decodeString($data, $offset);
        }

        return null;
    }

    private function decodeInt(string $data, int &$offset): int|false
    {
        $offset++; // skip 'i'
        $end = strpos($data, 'e', $offset);
        if ($end === false) {
            return false;
        }
        $num = (int) substr($data, $offset, $end - $offset);
        $offset = $end + 1;

        return $num;
    }

    private function decodeString(string $data, int &$offset): string|false
    {
        $colon = strpos($data, ':', $offset);
        if ($colon === false) {
            return false;
        }
        $len = (int) substr($data, $offset, $colon - $offset);
        $offset = $colon + 1;
        $str = substr($data, $offset, $len);
        $offset += $len;

        return $str;
    }

    private function decodeList(string $data, int &$offset): array
    {
        $offset++; // skip 'l'
        $list = [];
        while ($offset < strlen($data) && $data[$offset] !== 'e') {
            $list[] = $this->decodeValue($data, $offset);
        }
        $offset++; // skip 'e'

        return $list;
    }

    private function decodeDict(string $data, int &$offset): array
    {
        $offset++; // skip 'd'
        $dict = [];
        while ($offset < strlen($data) && $data[$offset] !== 'e') {
            $key   = $this->decodeString($data, $offset);
            $value = $this->decodeValue($data, $offset);
            if ($key !== false) {
                $dict[$key] = $value;
            }
        }
        $offset++; // skip 'e'

        return $dict;
    }

    // -------------------------------------------------------------------------
    // Helpers
    // -------------------------------------------------------------------------

    /** Build a bencoded failure reason response — always HTTP 200 per BitTorrent spec. */
    public function failure(string $message): string
    {
        return 'd14:failure reason' . strlen($message) . ':' . $message . 'e';
    }

    /** Validate a hex info_hash or peer_id (must be exactly 40 lowercase hex chars). */
    public function isValidHash(string $hash): bool
    {
        return strlen($hash) === 40 && ctype_xdigit($hash);
    }
}
