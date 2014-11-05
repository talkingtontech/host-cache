# Host Cache

This plugin is designed to cache downloads of WordPress plugins, themes, and updates from the WordPress.org repo.

### Configuration Required

This plugin is designed to implemented by a sysadmin. It relies on a nginx proxy to be established to proxy requests through. This plugin simply assists in routing all the downloads through proxy host. The benefits of which include faster download speeds and reduced bandwidth costs across a network of servers.

This plugin expects a hostname of `wpcache.host` to be resolvable by the server. This is commonly done through modifications to the `/etc/hosts` file.

An example nginx proxy config can be found in [server.conf](https://github.com/ctalkington/host-cache/blob/master/server.conf).

### Origins

This plugin was created out of the need for faster, more stable downloads experience for clients in countries not served by a local downloads CDN (ie Australia).