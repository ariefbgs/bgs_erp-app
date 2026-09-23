<?php

namespace App\Console\Commands;

use App\Models\DeliveryOrder;
use Carbon\Carbon;
use Illuminate\Console\Command;

class AutoDeliverDeliveryOrders extends Command
{
    protected $signature = 'delivery-orders:auto-deliver
                            {--dry-run : Show eligible Delivery Orders without updating them}';

    protected $description =
        'Automatically mark Pending Delivery Orders as Delivered 7 days after delivery date';

    public function handle(): int
    {
        $cutoff = Carbon::today()->subDays(7);

        $effectiveDate = Carbon::create(
            2026,
            9,
            3
        )->startOfDay();

        $query = DeliveryOrder::query()
            ->where('status', 'pending')
            ->whereDate(
                'delivery_date',
                '>=',
                $effectiveDate->toDateString()
            )
            ->whereDate(
                'delivery_date',
                '<=',
                $cutoff->toDateString()
            )
            ->orderBy('id');

        $count = (clone $query)->count();

        $this->info(
            'Effective delivery date: '.
            $effectiveDate->toDateString()
        );

        $this->info(
            'Cutoff delivery date: '.$cutoff->toDateString()
        );

        $this->info(
            'Eligible Pending Delivery Orders: '.$count
        );

        if ($this->option('dry-run')) {
            $query->get([
                'id',
                'do_number',
                'delivery_date',
                'status',
            ])->each(function ($do) {
                $this->line(
                    implode(' | ', [
                        'ID='.$do->id,
                        'DO='.$do->do_number,
                        'DELIVERY_DATE='.$do->delivery_date,
                        'STATUS='.$do->status,
                    ])
                );
            });

            $this->info('DRY_RUN=YES');

            return self::SUCCESS;
        }

        $updated = 0;

        $query->chunkById(
            100,
            function ($orders) use (&$updated) {
                foreach ($orders as $do) {
                    $do->status = 'delivered';
                    $do->save();

                    $updated++;
                }
            }
        );

        $this->info('UPDATED='.$updated);

        return self::SUCCESS;
    }
}
