<?php

namespace App\Models;

use Database\Factories\DeviceModelFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DeviceModel extends Model
{
    /** @use HasFactory<DeviceModelFactory> */
    use HasFactory;

    protected $fillable = ['name'];

    public function devices(): HasMany
    {
        return $this->hasMany(Device::class);
    }
}
