<?php

namespace App\Console\Commands;

use App\Services\WbSyncService;
use Illuminate\Console\Command;

class SyncWbDataCommand extends Command
{
    protected $signature = 'wb:sync
                            {--only=* : sales, orders, stocks, incomes}
                            {--entity= : alias for --only}';

    protected $description = 'Загрузить данные из WB API и сохранить в MySQL';

    public function handle(WbSyncService $sync): int
    {
        if (empty(config('wb_api.key'))) {
            $this->error('WB_API_KEY не задан в .env');

            return self::FAILURE;
        }

        $entities = collect($this->option('only') ?: [])
            ->flatMap(fn ($value) => preg_split('/\s*,\s*/', (string) $value, -1, PREG_SPLIT_NO_EMPTY))
            ->filter()
            ->values()
            ->all();

        if ($entity = $this->option('entity')) {
            $entities[] = $entity;
        }

        if ($entities === []) {
            $entities = ['sales', 'orders', 'stocks', 'incomes'];
        }

        $progress = function (string $name, int $page, int $batch, int $total) {
            $this->line(sprintf('[%s] page %d: +%d (total %d)', $name, $page, $batch, $total));
        };

        $summary = [];

        foreach ($entities as $entity) {
            $this->info("Syncing {$entity}...");
            $start = microtime(true);

            $count = match ($entity) {
                'sales' => $sync->syncSales($progress),
                'orders' => $sync->syncOrders($progress),
                'stocks' => $sync->syncStocks($progress),
                'incomes' => $sync->syncIncomes($progress),
                default => null,
            };

            if ($count === null) {
                $this->warn("Unknown entity: {$entity}");

                continue;
            }

            $elapsed = round(microtime(true) - $start, 1);
            $summary[$entity] = $count;
            $this->info("{$entity}: {$count} rows in {$elapsed}s");
        }

        $this->newLine();
        $this->table(['Entity', 'Rows'], collect($summary)->map(fn ($c, $e) => [$e, $c])->values());

        return self::SUCCESS;
    }
}
