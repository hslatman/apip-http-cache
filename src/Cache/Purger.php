<?php
/**
 * Author: Herman Slatman
 * Date: 2019-05-07
 * Time: 20:42
 */

namespace App\Cache;

use ApiPlatform\Core\HttpCache\PurgerInterface;

class Purger implements PurgerInterface
{
//    public function purge(array $iris)
//    {
//        // TODO: Implement purge() method.
//    }

//    private $clients;
//
//    /**
//     * @param ClientInterface[] $clients
//     */
//    public function __construct(array $clients)
//    {
//        $this->clients = $clients;
//    }
//    public function __construct()
//    {
//        dd('here');
//    }
//

    /**
     * {@inheritdoc}
     */
    public function purge(array $iris)
    {

        if (!$iris) {
            return;
        }

        // Create the regex to purge all tags in just one request
        $parts = array_map(function ($iri) {
            return sprintf('(^|\,)%s($|\,)', preg_quote($iri));
        }, $iris);

        $regex = \count($parts) > 1 ? sprintf('(%s)', implode(')|(', $parts)) : array_shift($parts);

//        foreach ($this->clients as $client) {
//            $client->request('BAN', '', ['headers' => ['ApiPlatform-Ban-Regex' => $regex]]);
//        }

        // TODO: purge the FosHttpCache
        // Is the key correct, we're using?

    }

}