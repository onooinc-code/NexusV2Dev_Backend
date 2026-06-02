<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

class SettingValueCast implements CastsAttributes
{
    /**
     * Cast the given value.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function get(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        if ($value === null) {
            return null;
        }

        if (isset($attributes['is_encrypted']) && $attributes['is_encrypted']) {
            try {
                $value = Crypt::decryptString($value);
            } catch (\Exception $e) {
                // Fallback to raw value if decryption fails
            }
        }

        $decoded = json_decode($value, true);
        return json_last_error() === JSON_ERROR_NONE ? $decoded : $value;
    }

    /**
     * Prepare the given value for storage.
     *
     * @param  array<string, mixed>  $attributes
     */
    public function set(Model $model, string $key, mixed $value, array $attributes): mixed
    {
        if ($value === null) {
            return null;
        }

        $encoded = is_string($value) ? $value : json_encode($value);

        // Sometimes json_encode is applied by caller, ensure we don't double encode
        if (!is_string($value)) {
            $encoded = json_encode($value);
        } else {
            // Check if it's already a valid json object/array
            json_decode($value);
            if (json_last_error() !== JSON_ERROR_NONE && $model->type === 'json') {
                $encoded = json_encode($value);
            }
        }
        
        // Actually to mimic laravel's json cast:
        $jsonValue = json_encode($value);

        if (isset($attributes['is_encrypted']) && $attributes['is_encrypted']) {
            return Crypt::encryptString($jsonValue);
        }

        return $jsonValue;
    }
}
