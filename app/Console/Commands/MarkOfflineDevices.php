<?php

namespace App\Console\Commands;

use App\Models\Device;
use App\Models\User;
use App\Notifications\DeviceWentOffline;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;

#[Signature('app:mark-offline-devices {--minutes=15 : Minutos sin reportar lecturas para considerar un dispositivo offline}')]
#[Description('Marca como offline los dispositivos que dejaron de reportar lecturas recientemente')]
class MarkOfflineDevices extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $threshold = Carbon::now()->subMinutes((int) $this->option('minutes'));

        $devices = Device::query()
            ->where('status', '!=', 'maintenance')
            ->where('status', '!=', 'offline')
            ->where(function ($query) use ($threshold) {
                $query->where(function ($q) use ($threshold) {
                    $q->whereNull('last_reading_at')->where('created_at', '<', $threshold);
                })->orWhere('last_reading_at', '<', $threshold);
            })
            ->get();

        if ($devices->isNotEmpty()) {
            Device::query()->whereIn('id', $devices->pluck('id'))->update(['status' => 'offline']);

            $users = User::all();

            foreach ($devices as $device) {
                Notification::send($users, new DeviceWentOffline($device));
            }
        }

        $this->info('Se marcaron '.$devices->count().' dispositivo(s) como offline.');

        return self::SUCCESS;
    }
}
