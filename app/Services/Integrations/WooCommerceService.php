<?php

namespace App\Services\Integrations;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class WooCommerceService
{
    protected $url;

    protected $key;

    protected $secret;

    public function __construct()
    {
        $this->url = rtrim(config('services.woocommerce.url'), '/');
        $this->key = config('services.woocommerce.key');
        $this->secret = config('services.woocommerce.secret');
    }

    private function client()
    {
        return Http::timeout(15)
            ->retry(2, 1000)
            ->withOptions([
                'connect_timeout' => 10,
            ]);
    }

    // BUSCAR SKU REAL (SIMPLE O VARIATION)
    public function findProductRealBySku(string $sku): ?array
    {
        try {

            $res = $this->client()->get("{$this->url}/wp-json/wc/v3/products", [
                'consumer_key' => $this->key,
                'consumer_secret' => $this->secret,
                'per_page' => 100,
                'sku' => $sku,
            ]);

            if ($res->failed()) {
                return null;
            }

            $products = $res->json();

            // 🔥 BUSCAR EN VARIACIONES SI NO ENCUENTRA
            if (empty($products)) {

                $resVar = $this->client()->get("{$this->url}/wp-json/wc/v3/products/variations", [
                    'consumer_key' => $this->key,
                    'consumer_secret' => $this->secret,
                    'per_page' => 100,
                    'sku' => $sku,
                ]);

                if ($resVar->successful()) {

                    $vars = $resVar->json();

                    if (! empty($vars)) {
                        $v = $vars[0];

                        return [
                            'type' => 'variation',
                            'id' => $v['id'],
                            'parent_id' => $v['parent_id'],
                        ];
                    }
                }

                return null;
            }

            $p = $products[0];

            if ($p['type'] === 'variation') {
                return [
                    'type' => 'variation',
                    'id' => $p['id'],
                    'parent_id' => $p['parent_id'],
                ];
            }

            if ($p['type'] === 'simple') {
                return [
                    'type' => 'simple',
                    'id' => $p['id'],
                ];
            }

            if ($p['type'] === 'variable') {

                $vars = $this->client()->get("{$this->url}/wp-json/wc/v3/products/{$p['id']}/variations", [
                    'consumer_key' => $this->key,
                    'consumer_secret' => $this->secret,
                    'per_page' => 100,
                ]);

                if ($vars->failed()) {
                    return null;
                }

                foreach ($vars->json() as $v) {
                    if (trim($v['sku']) === $sku) {
                        return [
                            'type' => 'variation',
                            'id' => $v['id'],
                            'parent_id' => $p['id'],
                        ];
                    }
                }
            }

            return null;

        } catch (\Exception $e) {

            Log::error('ERROR BUSCANDO SKU', [
                'sku' => $sku,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    // 🔥 ACTUALIZAR STOCK (FIX REAL AQUÍ)
    public function updateStock(array $woo, int $stock)
    {
        $payload = [
            'manage_stock' => true,
            'stock_quantity' => $stock,
            'stock_status' => $stock > 0 ? 'instock' : 'outofstock',
            'status' => $stock > 0 ? 'publish' : 'draft',
        ];

        try {

            // 🔥 AUTH EN QUERY STRING (CLAVE DEL FIX)
            $params = http_build_query([
                'consumer_key' => $this->key,
                'consumer_secret' => $this->secret,
            ]);

            // VARIATION
            if ($woo['type'] === 'variation') {
                return $this->client()->put(
                    "{$this->url}/wp-json/wc/v3/products/{$woo['parent_id']}/variations/{$woo['id']}?{$params}",
                    $payload
                );
            }

            // SIMPLE
            return $this->client()->put(
                "{$this->url}/wp-json/wc/v3/products/{$woo['id']}?{$params}",
                $payload
            );

        } catch (\Exception $e) {

            Log::error('ERROR UPDATE EXCEPTION', [
                'id' => $woo['id'],
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }
}
