/**
 * Stud.IP: Caching in JavaScript
 *
 * Uses session storage or local storage for persistent storage across
 * browser sessions for items with a given expiry.
 *
 * Example:
 *
 *     var cache = STUDIP.Cache.getInstance(),
 *         foo   = cache.get('foo');
 *     if (typeof foo === undefined) {
 *         foo = 'bar';
 *         cache.set('foo', foo);
 *     }
 *
 * Pass set() an expiry duration in seconds to allow persistent storage
 * across browser sessions.
 *
 * Example:
 *
 *     var cache = STUDIP.Cache.getInstance(),
 *         tmp;
 *     cache.set('foo', 'bar', 5);
 *     tmp = cache.get('foo');
 *     setTimeout(function () {
 *         console.log([tmp, cache.get('foo')]);
 *     }, 6000);
 *     // Will result in ['bar', undefined] after 6 seconds have passed
 *
 * You may pass get() a creator function as an optional second parameter
 * so the value will be generated on the fly if not found in cache.
 *
 * Example:
 *
 *     var cache = STUDIP.Cache.getInstance(),
 *         creator = function (index) { return 'Hello ' + index; };
 *     cache.remove('World');
 *     console.log(cache.get('World', creator));
 *     // Will result in 'Hello World' both on the console and in cache
 * 
 * Cache instances may use prefixes to avoid conflicts with other js
 * functions (this is the single reason why the lib was designed to use a
 * getInstance() method).
 *
 * Example:
 *
 *     var cache0 = STUDIP.Cache.getInstance(''),
 *         cache1 = STUDIP.Cache.getInstance('foo');
 *     cache0.set('foobar', 'baz');
 *     console.log([cache0.get('bar'), cache1.get('bar')]);
 *     // Will result in [undefined, 'baz']
 *
 * If the browser does not support any of the storage types, a dummy polyfill
 * will be used that doesn't actually store data.
 *
 * Internally, all items are prefixed with a 'studip.' in order to avoid
 * clashes.
 *
 * @author    Jan-Hendrik Willms <tleilax+studip@gmail.com>
 * @license   GPL2 or any later version
 * @copyright Stud.IP core group
 * @since     Stud.IP 3.2
 */
(function () {
    var caches = {
        local: window.localStorage || false,
        session: window.sessionStorage || false
    };

    // Create polyfills if neccessary
    if (!caches.local || !caches.session) {
        (function () {
            var DummyStorage = function () {
                return {
                    length: 0,
                    clear: function () {},
                    getItem: function () {
                        return undefined;
                    },
                    key: function () {
                        return undefined;
                    },
                    removeItem: function () {},
                    setItem: function () {}
                };
            };
            if (!caches.local) {
                caches.local = new DummyStorage();
            }
            if (!caches.session) {
                caches.session = new DummyStorage();
            }
        }());
    }

    /**
     * The main cache class' prototype.
     *
     * @param String prefix Optional prefix for the cache
     */
    function Cache(prefix) {
        this.prefix = 'studip.' + (prefix || '');
    }

    /**
     * Locates an item in the caches.
     *
     * @param String index Key of the item to look up
     * @return mixed false if item is not found, item's value otherwise
     */
    Cache.prototype.locate = function (index) {
        // Prefix index
        index = this.prefix + index;

        var now = (new Date()).getTime(),
            type,
            item;
        // Locate item in caches
        for (type in caches) {
            if (caches[type].hasOwnProperty(index)) {
                // Fetch item and decode it
                item = JSON.parse(caches[type].getItem(index));
                // Check expiration
                if (!item.expires || item.expires > now) {
                    return item.value;
                }
                // Expired, invalidate
                caches[type].removeItem(index);
                return undefined;
            }
        }
        // Item not found
        return undefined;
    };

    /**
     * Store an item in the cache.
     *
     * @param String index   Key used to store the item
     * @param mixed  value   Value of the item
     * @param mixed  expires Optional storage duration in seconds
     */
    Cache.prototype.set = function (index, value, expires) {
        // Remove old entry since we don't know where it might
        // be stored (no prefix since locate() will add it)
        this.remove(index);

        // Prefix index
        index = this.prefix + index;

        // Determine which cache to use and store the value
        var type = expires ? 'local' : 'session';
        caches[type].setItem(index, JSON.stringify({
            value: value,
            expires: expires ? (new Date()).getTime() + expires * 1000 : false
        }));
    };

    /**
     * Returns whether the cache has an item stored for the given key.
     *
     * @param String index Key used to store the item
     * @return bool
     */
    Cache.prototype.has = function (index) {
        return this.locate(index) !== false;
    };

    /**
     * Retrieves an object from the cache for the given key.
     * You may provide an additional creator function if the
     * value was not found to immediately create and set it.
     * The function will be passed the index as it's only argument.
     *
     * @param String index   Key used to store the item
     * @param mixed  creator Optional creator function for the value
     * @param mixed  expires Optional storage duration in seconds
     * @return mixed Value of the item or undefined if not found.
     */
    Cache.prototype.get = function (index, setter, expires) {
        var result = this.locate(index);
        if (result === undefined && setter && typeof setter === 'function') {
            result = setter(index);
            this.set(index, result, expires);
        }
        return result;
    };

    /**
     * Removes an item from the cache.
     *
     * @param String index Key used to store the item
     */
    Cache.prototype.remove = function (index) {
        var cache = this.locate(index);
        if (cache) {
            cache.removeItem(this.prefix + index);
        }
    };

    /**
     * Clears the cache completely. Respects the prefix, so only
     * the prefixed items will be removed.
     */
    Cache.prototype.prune = function () {
        var type,
            key;
        for (type in caches) {
            if (this.prefix) {
                for (key in caches[type]) {
                    if (key.indexOf(this.prefix) === 0) {
                        caches[type].removeItem(key);
                    }
                }
            } else {
                caches[type].clear();
            }
        }
    };

    /**
     * Expose the Cache object with it's getInstance method to the global
     * STUDIP object.
     */
    STUDIP.Cache = {
        getInstance: function (prefix) {
            return new Cache(prefix);
        }
    };

}());