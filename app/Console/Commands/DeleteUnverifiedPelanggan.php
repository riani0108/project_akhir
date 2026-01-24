<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Pelanggan;
use Carbon\Carbon;

class DeleteUnverifiedPelanggan extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:delete-unverified-pelanggan';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */

    public function handle()
    {
        $expired = Carbon::now()->subDays(3); // 3 hari

        $deleted = Pelanggan::whereNull('email_verified_at')
            ->where('created_at', '<', $expired)
            ->delete();

        $this->info("Deleted {$deleted} unverified pelanggan");
    }
}
