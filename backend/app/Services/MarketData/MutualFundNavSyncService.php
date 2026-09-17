<?php

namespace App\Services\MarketData;

use App\Contracts\MarketData\MutualFundDataProviderInterface;
use App\Models\MutualFund;
use App\Models\MutualFundNav;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Exception;

class MutualFundNavSyncService
{
    protected $provider;

    public function __construct(MutualFundDataProviderInterface $provider)
    {
        $this->provider = $provider;
    }

    public function sync(): void
    {
        try {
            $data = $this->provider->fetchLatestNavs();

            if (empty($data)) {
                Log::warning('MutualFundNavSyncService: Empty data received');
                return;
            }

            DB::beginTransaction();

            $fundsToUpsert = [];
            foreach ($data as $row) {
                $fundsToUpsert[] = [
                    'amfi_code'   => $row['amfi_code'],
                    'isin'        => $row['isin'] ?: null,
                    'amc_name'    => $row['amc_name'] ?? 'Unknown',
                    'scheme_name' => $row['scheme_name'],
                    'plan_type'   => $row['plan_type'],
                    'option_type' => $row['option_type'],
                    'category'    => $row['category'] ?? null,
                ];
            }

            foreach (array_chunk($fundsToUpsert, 1000) as $chunk) {
                MutualFund::upsert($chunk, ['amfi_code'], ['isin', 'amc_name', 'scheme_name', 'plan_type', 'option_type', 'category']);
            }

            $amfiCodes = array_column($data, 'amfi_code');
            $funds = MutualFund::whereIn('amfi_code', $amfiCodes)->pluck('id', 'amfi_code');

            $navsToUpsert = [];
            foreach ($data as $row) {
                if (!isset($funds[$row['amfi_code']])) {
                    continue;
                }
                $navsToUpsert[] = [
                    'mutual_fund_id' => $funds[$row['amfi_code']],
                    'nav_date'       => $row['nav_date'],
                    'nav'            => $row['nav'],
                ];
            }

            foreach (array_chunk($navsToUpsert, 1000) as $chunk) {
                MutualFundNav::upsert($chunk, ['mutual_fund_id', 'nav_date'], ['nav']);
            }

            DB::commit();
            Log::info('MutualFundNavSyncService: Sync completed successfully', ['count' => count($data)]);

        } catch (Exception $e) {
            DB::rollBack();
            Log::error('MutualFundNavSyncService: Sync failed', ['error' => $e->getMessage()]);
            throw $e;
        }
    }
}
