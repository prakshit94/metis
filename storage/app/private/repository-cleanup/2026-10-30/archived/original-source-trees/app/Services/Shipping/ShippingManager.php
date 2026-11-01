<?php

namespace App\Services\Shipping;

use App\Services\Shipping\Providers\IndiaPostProvider;
use Illuminate\Support\Manager;

class ShippingManager extends Manager
{
    /**
     * Get the default driver name.
     *
     * @return string
     */
    public function getDefaultDriver()
    {
        return $this->config->get('shipping.default', 'india_post');
    }

    /**
     * Create an instance of the India Post driver.
     *
     * @return IndiaPostProvider
     */
    public function createIndiaPostDriver()
    {
        return new IndiaPostProvider;
    }
}
