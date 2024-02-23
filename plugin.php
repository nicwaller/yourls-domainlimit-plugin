<?php
/*
Plugin Name: Domain Limiter
Plugin URI: https://github.com/nicwaller/yourls-domainlimit-plugin
Description: Only allow URLs from admin-specified domains
Version: 1.1.2
Author: nicwaller
Author URI: https://github.com/nicwaller
*/

// No direct call
if( !defined( 'YOURLS_ABSPATH' ) ) die();

yourls_add_filter( 'shunt_add_new_link', 'domainlimit_link_filter' );

function domainlimit_link_filter( $original_return, $url, $keyword = '', $title = '' ) {
	if ( domainlimit_environment_check() != true ) {
		$err = array();
		$err['status'] = 'fail';
		$err['code'] = 'error:configuration';
		$err['message'] = 'Problem with domain limit configuration. Check PHP error log.';
		$err['errorCode'] = '500';
		return $err;
	}

	// If the user is exempt, don't even bother checking.
	global $domainlimit_exempt_users;
	if ( defined( 'YOURLS_USER' ) && is_array( $domainlimit_exempt_users ) ) {
		if ( in_array( YOURLS_USER, $domainlimit_exempt_users ) ) {
			return $original_return;
		}
	}

	global $domainlimit_list;
	$domain_whitelist = $domainlimit_list;

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

	$allowed = false;

	if (!preg_match('/^https?:/', $url)) {
		// if there is no scheme prefix then parse_url() won't give us a hostname
		// also, YOURLS redirects don't work correctly if the scheme prefix is missing
		$return = array();
		$return['status'] = 'fail';
		$return['code'] = 'error:missingScheme';
		$return['message'] = 'URL must have http:// or https:// scheme prefix';
		$return['errorCode'] = '400';
		return $return;
	}

	$requested_domain = parse_url($url, PHP_URL_HOST);
	foreach ( $domain_whitelist as $domain_permitted ) {
		if ( domainlimit_is_subdomain( $requested_domain, $domain_permitted ) ) {
			$allowed = true;
			break;
		}
	}

	if ( $allowed == true ) {
		return $original_return;
	}

	$return = array();
	$return['status'] = 'fail';
	$return['code'] = 'error:disallowedhost';
	$return['message'] = 'URL must be in ' . implode(', ', $domain_whitelist);
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

// returns true if everything is configured right
function domainlimit_environment_check() {
	global $domainlimit_list;
	if ( !isset( $domainlimit_list ) ) {
		error_log('Missing definition of $domainlimit_list in user/config.php');
		return false;
	} else if ( isset( $domainlimit_list ) && !is_array( $domainlimit_list ) ) {
		// be friendly and allow non-array definitions
		$domain = $domainlimit_list;
		$domainlimit_list = array( $domain );
		return true;
	}
	return true;
}
