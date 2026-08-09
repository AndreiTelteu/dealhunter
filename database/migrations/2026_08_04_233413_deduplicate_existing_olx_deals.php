<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::transaction(function (): void {
            $deals = DB::table('deals')
                ->select(['id', 'hunted_deal_id', 'url', 'external_id', 'last_seen_at'])
                ->orderBy('hunted_deal_id')
                ->orderByDesc('last_seen_at')
                ->orderByDesc('id')
                ->get();

            $retainedByListing = [];

            foreach ($deals as $deal) {
                $listingId = $this->listingId($deal->url);

                if ($listingId === null) {
                    continue;
                }

                $key = $deal->hunted_deal_id.':'.$listingId;

                if (! isset($retainedByListing[$key])) {
                    $retainedByListing[$key] = $deal->id;

                    DB::table('deals')->where('id', $deal->id)->update([
                        'external_id' => $listingId,
                        'url' => $this->canonicalUrl($deal->url),
                    ]);

                    continue;
                }

                $retainedId = $retainedByListing[$key];
                $favoriteUserIds = DB::table('favorites')->where('deal_id', $deal->id)->pluck('user_id');

                foreach ($favoriteUserIds as $userId) {
                    DB::table('favorites')->insertOrIgnore([
                        'user_id' => $userId,
                        'deal_id' => $retainedId,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ]);
                }

                DB::table('favorites')->where('deal_id', $deal->id)->delete();
                DB::table('deal_snapshots')->where('deal_id', $deal->id)->delete();
                DB::table('deals')->where('id', $deal->id)->delete();
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {}

    private function listingId(string $url): ?string
    {
        return preg_match('/-ID([A-Za-z0-9]+)\.html(?:[?#]|$)/', $url, $matches) === 1 ? $matches[1] : null;
    }

    private function canonicalUrl(string $url): string
    {
        $parts = parse_url($url);

        return sprintf('%s://%s%s', $parts['scheme'] ?? 'https', $parts['host'] ?? 'www.olx.ro', $parts['path'] ?? '/');
    }
};
