yourls-domainlimit-plugin
=========================

This plugin for [YOURLS](https://github.com/YOURLS/YOURLS) limits the creation of shorturls to a list of domains that you define.

You might want to limit the domains allowed for shortlinks so that your brand is not misused by people linking to resources outside your domain and outside your control.

Installation
------------
1. Download the [latest release](https://github.com/nicwaller/yourls-domainlimit-plugin/tags)
1. Copy the plugin folder into your user/plugins folder for YOURLS.
1. Activate the plugin with the plugin manager in the admin interface.

Configuration
-------------
Define a list of allowed domains in your user/config.php. For example:
`$domainlimit_list = array( 'mydomain.com', 'otherdomain.com' );`
