# APIP-HTTP-CACHE

This project is a small POC for using the Symfony HttpCache proxy as a caching proxy for API Platform.

## Description

The HttpCache component has been integrated using the [FOSHttpCacheBundle](https://github.com/FriendsOfSymfony/FOSHttpCacheBundle).
The FOSHttpCacheBundle offers many utilities for managing FOSHttpCache, which also supports Symfony HttpCache to a certain extent.
This POC makes use of the EventDispatchingHttpCache, meaning that we're wrapping the original Symfony kernel with a caching kernel using HttpCache.


## Why

API Platform offers a cache implementation out of the box using Varnish.
The implementation is based on a custom VarnishPurger and relies on a running Varnish instance.
Although Varnish is a great caching server, sometimes it can be overkill to run an additional server just for caching.
That's why we opted for integrating HttpCache with API Platform.

## Cache Invalidation

Cache invalidation is mainly performed by subscribing the PurgeListener to purge events in our custom Cache implementation, which results in the 
FOSHttpCache CacheManager invalidating the cache based on events fired from the kernel, instead of having to dispatch HTTP requests
to an external caching server, like Varnish or Nginx.

### Methods

This repository currently contains three different methods for triggering cache invalidation.
We've implemented each of the methods to be used with a specific Entity:

* Issue (Rule-based)
* Item (Tag-based)
* Bug (Custom)

Each of those will be described in the following subsections.

What is true for those three methods, is that they all use the default cache invalidation configuration too.
The default cache invalidation is based on having a distinct URI (the key) and a HTTP method.
In case a request comes in with a safe HTTP method, the response might be in the cache.
When a request with a mutating HTTP method comes in, the response will be passed on to the Symfony kernel, potentially triggering an invalidate for the URI in the request.

In addition to this default behavior, some applications might some more elaborate cache invalidation strategies, which is what the below three methods aim to do.
The most important goal to accomplish is to invalidate the cache when an existing entity is updated or deleted, because these do not trigger the default cache invalidation behavior.

#### Issue

The Issue entity is configured with rule-based cache invalidation.
When triggering certain routes, the cache is invalidated by events being fired from the kernel.
The routes are automatically generated by API Platform, but currently need to be added manually in the cache configuration.
These events are managed by the default implementation in FOSHttpCacheBundle.

The current implementation does not work completely as expected, as described in the fos_http_cache.yaml file.
We could also improve this method by automatically registering the rules that should trigger cache invalidation.


#### Item

The Item entity is configured with tag-based cache invalidation.
For each entity type for which tag-based cache invalidation should work, two rules should be created.
The rules affect the default collection as well as item routes provided by API Platform.

This method is not optimal, because there's some manual work involved with creating the tag-based rules.
We could improve by creating a way for automatically registering the rules, like above.

#### Bug

The Bug entity is configured with a custom cache invalidation method.
The method relies on the API Platform events system.
We've implemented the CacheInvalidationSubscriber, which checks whether an entity item route is called as well as whether the method is mutating.
If this is the case, the corresponding collection route for the entity is constructed, after which the CacheManager is instructed to invalidate that route.

The CacheInvalidationSubscriber is quite generic and does not need manual rules to be added to the configuration.
For the sake of this POC it has been implemented to only trigger on the Bug entity, but it could well be put to use to all entity types, depending on the application in use.

## TODO / Improvements

* Look into cache refresh
* Look into integration with API Platform using PurgerInterface
* Add UserContext and handling thereof in the cache
* Add some custom commands for managing the HttpCache
* Extend documentation
