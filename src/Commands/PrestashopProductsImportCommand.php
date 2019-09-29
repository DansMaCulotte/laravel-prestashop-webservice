<?php

namespace App\Console\Commands;

use DansMaCulotte\PrestashopWebService\Exceptions\PrestashopWebServiceException;
use DansMaCulotte\PrestashopWebService\PrestashopWebService;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;

abstract class PrestashopProductsImportCommand extends Command
{
    const PRESTASHOP_RESOURCE_NAME = 'products';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'prestashop:import-products {--id=* : The Prestashop ID of the product}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import or sync products with Prestashop products database';

    /**
     * The prestashop singleton from the service provider
     * @var PrestashopWebService
     */
    protected $prestashop;

    /**
     * Create a new command instance.
     *
     * @param PrestashopWebService $prestashop
     */
    public function __construct(PrestashopWebService $prestashop)
    {
        parent::__construct();

        $this->prestashop = $prestashop;
    }

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $ids = $this->option('id');
        $debug = $this->getOutput()->isDebug();

        $this->info('Importing products');

        $products = count($ids) ? $ids : $this->getProducts();
        $bar = $this->output->createProgressBar(count($products));

        $skippedProducts = new Collection();

        foreach ($products as $productId) {
            try {
                $rawProduct = $this->getProduct($productId);
                $product = $this->importProduct($rawProduct);

                if (!$product) {
                    $skippedProducts->add(new Collection([
                        'id' => (string) $rawProduct->id,
                        'reference' => (string) $rawProduct->reference,
                    ]));

                    if ($debug) {
                        $this->info("\nPrestashop: Skipped product [{$rawProduct->id}] $rawProduct->reference");
                    }
                } else {
                    if ($debug) {
                        $this->info("\nPrestashop: Imported product [{$rawProduct->id}] {$rawProduct->reference}");
                    }
                }

                $bar->advance();
            } catch (\Exception $e) {
                if ($debug) {
                    $this->info($e->getMessage());
                }
                $this->error("\nPrestashop: Failed to request product " . (string) $productId);
            }
        }

        $bar->finish();

        $skippedProductsCount = count($skippedProducts);
        if ($skippedProductsCount) {
            $this->info("\nPrestashop: Skipped {$skippedProductsCount} products");
            $this->table(['id', 'reference'], $skippedProducts->toArray());
        }

        return true;
    }

    /**
     * @return array
     */
    public function getProducts()
    {
        try {
            $xml = $this->prestashop->get([
                'resource' => self::PRESTASHOP_RESOURCE_NAME,
            ]);
        } catch (PrestashopWebServiceException $e) {
            $this->error('Prestashop: Failed to request products');
            return [];
        }

        $ids = [];

        foreach ($xml->products->children() as $product) {
            foreach ($product->attributes() as $key => $value) {
                if ($key === 'id') {
                    array_push($ids, (string)$value);
                }
            }
        }

        return $ids;
    }

    /**
     * @param string $id
     * @return \SimpleXMLElement
     * @throws \DansMaCulotte\PrestashopWebService\Exceptions\PrestashopWebServiceException
     */
    public function getProduct(string $id)
    {
        $xml = $this->prestashop->get([
            'resource' => self::PRESTASHOP_RESOURCE_NAME,
            'id' => $id,
        ]);

        return $xml->product;
    }

    /**
     * @param \SimpleXMLElement $product
     */
    abstract public function importProduct(\SimpleXMLElement $product);
}
