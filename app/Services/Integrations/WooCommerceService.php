<?php

namespace App\Services\Integrations;

use Illuminate\Support\Facades\Http;

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
        return Http::timeout(15) //  timeout general
            ->retry(2, 1000) // reintento en caso de fallo (2 veces, 1 segundo de espera)
            ->withOptions([
                'connect_timeout' => 10, // timeout de conexión
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

            if (empty($products)) {
                return null;
            }

            $p = $products[0];

            // CASO 1: ES VARIATION (ESTO ES LO QUE TE FALTABA)
            if ($p['type'] === 'variation') {
                return [
                    'type' => 'variation',
                    'id' => $p['id'],
                    'parent_id' => $p['parent_id'],
                ];
            }

            // CASO 2: ES SIMPLE
            if ($p['type'] === 'simple') {
                return [
                    'type' => 'simple',
                    'id' => $p['id'],
                ];
            }

            // CASO 3: ES VARIABLE (PADRE)
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

        } catch (\Exception $e) {

            Log::error('ERROR BUSCANDO SKU', [
                'sku' => $sku,
                'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    // ACTUALIZAR SEGÚN TIPO
    public function updateStock(array $woo, int $stock)
    {
        $payload = [
            'manage_stock' => true,
            'stock_quantity' => $stock,
            'stock_status' => $stock > 0 ? 'instock' : 'outofstock',
            'status' => $stock > 0 ? 'publish' : 'draft',
        ];

        try {

            // VARIATION
            if ($woo['type'] === 'variation') {
                return $this->client()->put(
                    "{$this->url}/wp-json/wc/v3/products/{$woo['parent_id']}/variations/{$woo['id']}",
                    array_merge($payload, [
                        'consumer_key' => $this->key,
                        'consumer_secret' => $this->secret,
                    ])
                );
            }

            // SIMPLE
            return $this->client()->put(
                "{$this->url}/wp-json/wc/v3/products/{$woo['id']}",
                array_merge($payload, [
                    'consumer_key' => $this->key,
                    'consumer_secret' => $this->secret,
                ])
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
