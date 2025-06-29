<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class UpdateTechnicianRatings extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'technicians:update-ratings';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update average ratings for all technicians based on their ratings';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Starting to update technician ratings...');

        try {
            $affected = DB::statement('
                UPDATE technicians t
                SET average_rating = (
                    SELECT ROUND(AVG(r.rating), 1)
                    FROM ratings r
                    WHERE r.technician_id = t.id
                )
            ');

            $this->info('Successfully updated technician ratings!');

            // Show some statistics
            $stats = DB::table('technicians')
                ->select(
                    DB::raw('COUNT(*) as total_technicians'),
                    DB::raw('AVG(average_rating) as average_rating'),
                    DB::raw('MIN(average_rating) as min_rating'),
                    DB::raw('MAX(average_rating) as max_rating')
                )
                ->first();

            $this->table(
                ['Total Technicians', 'Average Rating', 'Min Rating', 'Max Rating'],
                [[
                    $stats->total_technicians,
                    number_format($stats->average_rating, 1),
                    number_format($stats->min_rating, 1),
                    number_format($stats->max_rating, 1)
                ]]
            );
        } catch (\Exception $e) {
            $this->error('Error updating technician ratings: ' . $e->getMessage());
            return 1;
        }

        return 0;
    }
}
