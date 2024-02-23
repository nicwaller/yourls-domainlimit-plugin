<?php
/*
Plugin Name: Domain Limiter
Plugin URI: https://github.com/nicwaller/yourls-domainlimit-plugin
Description: Only allow URLs from admin-specified domains
Version: 1.2.0
Author: nicwaller
Author URI: https://github.com/nicwaller
*/

// No direct call
if( !defined( 'YOURLS_ABSPATH' ) ) die();

yourls_add_filter( 'shunt_add_new_link', 'domainlimit_link_filter' );

function domainlimit_link_filter( $original_return, $url, $keyword = '', $title = '' ) {
	domainlimit_environment_check();

	// If the user is exempt, don't even bother checking.
	global $domainlimit_exempt_users;
	if ( defined( 'YOURLS_USER' ) && is_array( $domainlimit_exempt_users ) ) {
		if ( in_array( YOURLS_USER, $domainlimit_exempt_users ) ) {
			return $original_return;
		}
	}

	$domain_allowlist = $GLOBALS['domainlimit_list'];
	$domain_denylist = $GLOBALS['domainlimit_denylist'];

	// if we have no allowlist or denylist then don't block anything ("fail open")
	if ( count($domain_denylist) == 0 && count($domain_allowlist) == 0 ) {
		return $original_return;
	}

	// The plugin hook gives us the raw URL input by the user, but
	// it needs some cleanup before it's suitable for parse_url().
	$url = yourls_sanitize_url_safe( $url );
	if ( !$url || $url == 'http://' || $url == 'https://' ) {
		$return['status']    = 'fail';
		$return['code']      = 'error:nourl';
		$return['message']   = yourls__( 'Missing or malformed URL' );
		$return['errorCode'] = '400';
		return yourls_apply_filter( 'add_new_link_fail_nourl', $return, $url, $keyword, $title );
	}

	// deny-by-default is the original mode
	$allowed = false;

	if ( count($domain_denylist) > 0 && count($domain_allowlist) == 0 ) {
		// if we have a denylist, but no allowlist, then change the default to allow
		$allowed = true;
	}
	
	$requested_domain = parse_url($url, PHP_URL_HOST);

	if ( is_array( $domain_allowlist ) ) {
		foreach ( $domain_allowlist as $domain_permitted ) {
			if ( domainlimit_is_subdomain( $requested_domain, $domain_permitted ) ) {
				$allowed = true;
				break;
			}
		}
	}

	if ( is_array( $domain_denylist ) ) {
		foreach ( $domain_denylist as $domain_denied ) {
			if ( domainlimit_is_subdomain( $requested_domain, $domain_denied ) ) {
				$return = array();
				$return['status'] = 'fail';
				$return['code'] = 'error:disallowedhost';
				$return['message'] = "Creating links to $domain_denied is not permitted";
				$return['errorCode'] = '400';
				return $return;
			}
		}
	}

	if ( $allowed ) {
		return $original_return;
	}

	$return = array();
	$return['status'] = 'fail';
	$return['code'] = 'error:disallowedhost';
	$return['message'] = 'URL must be in ' . implode(', ', $domain_allowlist);
	$return['errorCode'] = '400';
	return $return;
}

/*
 * Determine whether test_domain is controlled by $parent_domain
 */
function domainlimit_is_subdomain( $test_domain, $parent_domain ) {
	if ( $test_domain == $parent_domain ) {
		return true;
	}

	// note that "notunbc.ca" is NOT a subdomain of "unbc.ca"
	// We CANNOT just compare the rightmost characters
	// unless we add a period in there first
	if ( substr( $parent_domain, 1, 1) != '.' ) {
		$parent_domain = '.' . $parent_domain;
	}

	$chklen = strlen($parent_domain);
	return ( $parent_domain == substr( $test_domain, 0-$chklen ) );
}

function domainlimit_environment_check() {
	$ensureArray = function($name) {
		if ( !isset( $GLOBALS[$name] ) || !is_array( $GLOBALS[$name] ) ) {
			$GLOBALS[$name] = array();
		}
	};
	$ensureArray('$domainlimit_exempt_users');
	$ensureArray('domainlimit_list');
	$ensureArray('domainlimit_denylist');
}
