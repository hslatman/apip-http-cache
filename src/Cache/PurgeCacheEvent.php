<?php
/**
 * Author: Herman Slatman
 * Date: 2019-05-08
 * Time: 08:51
 */

namespace App\Cache;

use FOS\HttpCache\Event;

class PurgeCacheEvent extends Event
{
    public const PURGE = 'http_cache.purge';

}