<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Log;

/**
 * «encrypted» kabi, lekin ochib bo'lmaydigan qiymat (boshqa APP_KEY bilan shifrlangan — masalan baza
 * boshqa serverdan ko'chirilgan yoki APP_KEY almashgan) sahifani yiqitmaydi: null qaytaradi va jurnalga yozadi.
 * Admin kalitni qayta yaratadi.
 */
class SafeEncrypted implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        try {
            return Crypt::decryptString($value);
        } catch (DecryptException) {
            Log::warning("{$model->getTable()}#{$model->getKey()}.{$key}: shifrni ochib bo'lmadi (boshqa APP_KEY). Kalitni qayta yarating.");

            return null;
        }
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        return $value === null || $value === '' ? null : Crypt::encryptString((string) $value);
    }
}
