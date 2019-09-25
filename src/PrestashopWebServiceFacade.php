<?php

namespace DansMaCulotte\PrestashopWebService;

use Illuminate\Support\Facades\Facade;

class PrestashopWebServiceFacade extends Facade
{
    protected static function getFacadeAccessor()
    {
        return 'prestashop-webservice';
    }
}
