<?php

namespace App\Console\Commands;

use App\Models\BuktiPembelian;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class CleanupOldBukti extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'bukti:cleanup {--months=3 : Number of months after which files are deleted}';

    /**
     * The console command description.
     */
    protected $description = 'Delete bukti pembelian files older than specified months (default: 3)';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $months = $this->option('months');
        $cutoffDate = Carbon::now('Asia/Jakarta')->subMonths($months);
        
        $this->info("Cleaning up bukti files older than {$cutoffDate->toDateString()}...");
        
        // Find bukti with files older than cutoff date
        $oldBukti = BuktiPembelian::where('created_at', '<', $cutoffDate)
            ->whereNotNull('file_path')
            ->where('file_path', '!=', '')
            ->get();
        
        $deletedCount = 0;
        $errorCount = 0;
        
        foreach ($oldBukti as $bukti) {
            try {
                // Delete file from storage
                if (Storage::disk('public')->exists($bukti->file_path)) {
                    Storage::disk('public')->delete($bukti->file_path);
                }
                
                // Clear file_path but keep the record
                $bukti->update([
                    'file_path' => null,
                    'file_size' => 0,
                ]);
                
                $deletedCount++;
            } catch (\Exception $e) {
                $this->error("Failed to clean up bukti ID {$bukti->id}: {$e->getMessage()}");
                $errorCount++;
            }
        }
        
        $this->info("Cleanup completed: {$deletedCount} files deleted, {$errorCount} errors.");
        
        return Command::SUCCESS;
    }
}
