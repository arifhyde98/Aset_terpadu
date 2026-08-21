<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Activity extends \Illuminate\Database\Eloquent\Model
{
    public const MODULE_ERANDIS = 1;
    public const MODULE_SIPAT = 2;
    public const MODULE_ELABEL = 3;

    protected $fillable = ['user_id', 'module_id', 'module_key', 'description', 'type'];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Mencatat aktivitas ke database.
     */
    public static function log(string $description, string $type = 'info', int $moduleId = self::MODULE_ERANDIS, string $moduleKey = 'erandis')
    {
        $payload = [
            'user_id' => auth()->id(),
            'description' => $description,
            'type' => $type,
        ];

        if (\Illuminate\Support\Facades\Schema::hasColumn('activities', 'module_id')) {
            $payload['module_id'] = $moduleId;
        }

        if (\Illuminate\Support\Facades\Schema::hasColumn('activities', 'module_key')) {
            $payload['module_key'] = $moduleKey;
        }

        return self::create($payload);
    }

    public static function logSipat(string $description, string $type = 'info')
    {
        return self::log($description, $type, self::MODULE_SIPAT, 'sipat');
    }
}
