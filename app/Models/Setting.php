<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $primaryKey = 'key';
    protected $keyType    = 'string';
    public    $incrementing = false;
    public    $timestamps   = false;

    protected $fillable = ['key', 'value', 'type', 'group', 'label'];

    /** Cast the stored string value to its declared PHP type. */
    public function getTypedValue(): mixed
    {
        return match ($this->type) {
            'bool'  => in_array($this->value, ['true', '1', 'yes'], true),
            'int'   => (int) $this->value,
            'json'  => json_decode($this->value, true) ?? [],
            default => $this->value,
        };
    }

    /** Serialize a PHP value back to the string form stored in the DB. */
    public static function serializeValue(mixed $value, string $type): string
    {
        return match ($type) {
            'bool'  => $value ? 'true' : 'false',
            'int'   => (string)(int)$value,
            'json'  => json_encode($value),
            default => (string)$value,
        };
    }
}
