<?php

namespace App\Console\Commands;

use DansMaCulotte\PrestashopWebService\Exceptions\PrestashopWebServiceException;
use DansMaCulotte\PrestashopWebService\PrestashopWebService;
use Illuminate\Console\Command;

abstract class PrestashopSuppliersImportCommand extends Command
{
    const PRESTASHOP_RESOURCE_NAME = 'suppliers';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'prestashop:import-suppliers {--id=* : The Prestashop ID of the supplier}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import and sync vendors with Prestashop suppliers database';

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

        $this->info('Importing suppliers');

        $suppliers = count($ids) ? $ids : $this->getSuppliers();
        $bar = $this->output->createProgressBar(count($suppliers));

        foreach ($suppliers as $supplierId) {
            try {
                $rawSupplier = $this->getSupplier($supplierId);
                $supplier = $this->importSupplier($rawSupplier);

                $bar->advance();

                if ($debug) {
                    $this->info("Prestashop: Imported supplier {$supplier->name}");
                }
            } catch (\Exception $e) {
                $this->error("Prestashop: Failed to request supplier {$rawSupplier->id}");
            }
        }

        $bar->finish();

        return true;
    }

    /**
     * @return array
     */
    private function getSuppliers()
    {
        try {
            $xml = $this->prestashop->get([
                'resource' => self::PRESTASHOP_RESOURCE_NAME,
            ]);
        } catch (PrestashopWebServiceException $e) {
            $this->error('Prestashop: Failed to request suppliers');
            return [];
        }

        $ids = [];

        foreach ($xml->suppliers->children() as $supplier) {
            foreach ($supplier->attributes() as $key => $value) {
                if ($key === 'id') {
                    array_push($ids, (string) $value);
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
    public function getSupplier(string $id)
    {
        $xml = $this->prestashop->get([
            'resource' => self::PRESTASHOP_RESOURCE_NAME,
            'id' => $id,
        ]);

        return $xml->supplier;
    }

    /**
     * @param \SimpleXMLElement $supplier
     */
    abstract public function importSupplier(\SimpleXMLElement $supplier);
}
