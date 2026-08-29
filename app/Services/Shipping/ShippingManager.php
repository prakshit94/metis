<?php

namespace App\Services\Shipping;

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
     * @return \App\Services\Shipping\Providers\IndiaPostProvider
     */
    public function createIndiaPostDriver()
    {
        return new Providers\IndiaPostProvider();
    }
}
