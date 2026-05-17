<?php

namespace App\Services;

use App\Models\Income;
use App\Models\Order;
use App\Models\Sale;
use App\Models\Stock;
use Illuminate\Support\Carbon;

class WbSyncService
{
    public function __construct(private readonly WbApiClient $client) {}

    public function syncSales(?callable $onProgress = null): int
    {
        $total = 0;
        $params = $this->dateRangeParams();

        foreach ($this->client->fetchAll('sales', $params) as $page => $items) {
            if ($items === []) {
                continue;
            }
            $rows = array_map(fn (array $item) => $this->mapSale($item), $items);
            Sale::upsert($rows, ['sale_id'], $this->updateColumns($rows[0], ['sale_id']));
            $total += count($items);
            $onProgress && $onProgress('sales', $page, count($items), $total);
        }

        return $total;
    }

    public function syncOrders(?callable $onProgress = null): int
    {
        $total = 0;
        $params = $this->dateRangeParams();

        foreach ($this->client->fetchAll('orders', $params) as $page => $items) {
            if ($items === []) {
                continue;
            }
            $rows = array_map(fn (array $item) => $this->mapOrder($item), $items);
            Order::upsert($rows, ['g_number'], $this->updateColumns($rows[0], ['g_number']));
            $total += count($items);
            $onProgress && $onProgress('orders', $page, count($items), $total);
        }

        return $total;
    }

    public function syncStocks(?callable $onProgress = null): int
    {
        $total = 0;
        $params = ['dateFrom' => now()->format('Y-m-d')];

        foreach ($this->client->fetchAll('stocks', $params) as $page => $items) {
            if ($items === []) {
                continue;
            }
            $rows = array_map(fn (array $item) => $this->mapStock($item), $items);
            Stock::upsert(
                $rows,
                ['date', 'nm_id', 'warehouse_name', 'barcode'],
                $this->updateColumns($rows[0], ['date', 'nm_id', 'warehouse_name', 'barcode'])
            );
            $total += count($items);
            $onProgress && $onProgress('stocks', $page, count($items), $total);
        }

        return $total;
    }

    public function syncIncomes(?callable $onProgress = null): int
    {
        $total = 0;
        $params = $this->dateRangeParams();

        foreach ($this->client->fetchAll('incomes', $params) as $page => $items) {
            if ($items === []) {
                continue;
            }
            $rows = array_map(fn (array $item) => $this->mapIncome($item), $items);
            Income::upsert(
                $rows,
                ['income_id', 'barcode', 'supplier_article'],
                $this->updateColumns($rows[0], ['income_id', 'barcode', 'supplier_article'])
            );
            $total += count($items);
            $onProgress && $onProgress('incomes', $page, count($items), $total);
        }

        return $total;
    }

    private function dateRangeParams(): array
    {
        $dateTo = config('wb_api.date_to');

        return [
            'dateFrom' => config('wb_api.date_from'),
            'dateTo' => filled($dateTo) ? $dateTo : now()->format('Y-m-d'),
        ];
    }

    private function mapSale(array $item): array
    {
        return [
            'sale_id' => $item['sale_id'],
            'g_number' => $item['g_number'] ?? null,
            'date' => $this->toDate($item['date'] ?? null),
            'last_change_date' => $this->toDate($item['last_change_date'] ?? null),
            'supplier_article' => $item['supplier_article'] ?? null,
            'tech_size' => $item['tech_size'] ?? null,
            'barcode' => $item['barcode'] ?? null,
            'total_price' => $item['total_price'] ?? null,
            'discount_percent' => $item['discount_percent'] ?? null,
            'is_supply' => (bool) ($item['is_supply'] ?? false),
            'is_realization' => (bool) ($item['is_realization'] ?? false),
            'promo_code_discount' => $item['promo_code_discount'] ?? null,
            'warehouse_name' => $item['warehouse_name'] ?? null,
            'country_name' => $item['country_name'] ?? null,
            'oblast_okrug_name' => $item['oblast_okrug_name'] ?? null,
            'region_name' => $item['region_name'] ?? null,
            'income_id' => $item['income_id'] ?? null,
            'odid' => $item['odid'] ?? null,
            'spp' => $item['spp'] ?? null,
            'for_pay' => $item['for_pay'] ?? null,
            'finished_price' => $item['finished_price'] ?? null,
            'price_with_disc' => $item['price_with_disc'] ?? null,
            'nm_id' => $item['nm_id'] ?? null,
            'subject' => $item['subject'] ?? null,
            'category' => $item['category'] ?? null,
            'brand' => $item['brand'] ?? null,
            'is_storno' => $item['is_storno'] ?? null,
            'updated_at' => now(),
            'created_at' => now(),
        ];
    }

    private function mapOrder(array $item): array
    {
        return [
            'g_number' => $item['g_number'],
            'date' => $this->toDateTime($item['date'] ?? null),
            'last_change_date' => $this->toDate($item['last_change_date'] ?? null),
            'supplier_article' => $item['supplier_article'] ?? null,
            'tech_size' => $item['tech_size'] ?? null,
            'barcode' => $item['barcode'] ?? null,
            'total_price' => $item['total_price'] ?? null,
            'discount_percent' => $item['discount_percent'] ?? null,
            'warehouse_name' => $item['warehouse_name'] ?? null,
            'oblast' => $item['oblast'] ?? null,
            'income_id' => $item['income_id'] ?? null,
            'odid' => $item['odid'] ?? null,
            'nm_id' => $item['nm_id'] ?? null,
            'subject' => $item['subject'] ?? null,
            'category' => $item['category'] ?? null,
            'brand' => $item['brand'] ?? null,
            'is_cancel' => (bool) ($item['is_cancel'] ?? false),
            'cancel_dt' => $this->toDateTime($item['cancel_dt'] ?? null),
            'updated_at' => now(),
            'created_at' => now(),
        ];
    }

    private function mapStock(array $item): array
    {
        return [
            'date' => $this->toDate($item['date'] ?? null),
            'last_change_date' => $this->toDate($item['last_change_date'] ?? null),
            'supplier_article' => $item['supplier_article'] ?? null,
            'tech_size' => $item['tech_size'] ?? null,
            'barcode' => $item['barcode'] ?? null,
            'quantity' => $item['quantity'] ?? 0,
            'is_supply' => (bool) ($item['is_supply'] ?? false),
            'is_realization' => (bool) ($item['is_realization'] ?? false),
            'quantity_full' => $item['quantity_full'] ?? 0,
            'warehouse_name' => $item['warehouse_name'],
            'in_way_to_client' => $item['in_way_to_client'] ?? 0,
            'in_way_from_client' => $item['in_way_from_client'] ?? 0,
            'nm_id' => $item['nm_id'],
            'subject' => $item['subject'] ?? null,
            'category' => $item['category'] ?? null,
            'brand' => $item['brand'] ?? null,
            'sc_code' => $item['sc_code'] ?? null,
            'price' => $item['price'] ?? null,
            'discount' => $item['discount'] ?? null,
            'updated_at' => now(),
            'created_at' => now(),
        ];
    }

    private function mapIncome(array $item): array
    {
        return [
            'income_id' => $item['income_id'],
            'number' => $item['number'] ?? null,
            'date' => $this->toDate($item['date'] ?? null),
            'last_change_date' => $this->toDate($item['last_change_date'] ?? null),
            'supplier_article' => $item['supplier_article'] ?? null,
            'tech_size' => $item['tech_size'] ?? null,
            'barcode' => $item['barcode'] ?? null,
            'quantity' => $item['quantity'] ?? 0,
            'total_price' => $item['total_price'] ?? null,
            'date_close' => $this->toDate($item['date_close'] ?? null),
            'warehouse_name' => $item['warehouse_name'] ?? null,
            'nm_id' => $item['nm_id'] ?? null,
            'updated_at' => now(),
            'created_at' => now(),
        ];
    }

    /** @param  array<string, mixed>  $row */
    private function updateColumns(array $row, array $uniqueKeys): array
    {
        return array_values(array_diff(array_keys($row), $uniqueKeys, ['created_at']));
    }

    private function toDate(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return Carbon::parse($value)->format('Y-m-d');
    }

    private function toDateTime(?string $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return Carbon::parse($value)->format('Y-m-d H:i:s');
    }
}
