<?php /** @noinspection PhpDefineCanBeReplacedWithConstInspection */

function yourls_sanitize_url_safe($url) {
	return $url;
}

$did_register = false;
function yourls_add_filter() {
	global $did_register;
	$did_register = true;
}

function yourls__($v) {
	return $v;
}

function yourls_apply_filter($name, $return, $url, $keyword, $title) {
	return $return;
}

// set up a mock environment like YOURLS
function mockEnvironment() {
	define( "YOURLS_ABSPATH", true );
	global $domainlimit_list;
	$domainlimit_list = array('allowed.example.com');
}

mockEnvironment();
include('plugin.php');

$results = array(
	false => 0,
	true => 0,
);

function expect($url, $expected) /* bool */ {
	echo "Testing '$url' ... ";

	$okResult = array(
		'status' => 'success',
	);
	$actual = domainlimit_link_filter( $okResult, $url);
	$passing = true;
	$errors = array();
	foreach ( $expected as $k => $v ) {
		if (!array_key_exists($k, $actual)) {
			$errors[] = "missing key $k";
			$passing = false;
			continue;
		}
		if ($actual[$k] != $expected[$k]) {
			$errors[] = "expected $v but got $actual[$k]";
			$passing = false;
		}
	}

	global $results;
	$results[$passing]++;
	echo ($passing ? "pass" : "FAILED") . "\n";

	if (!$passing) {
		print_r($errors);
	}

	return $passing;
}

function testSuite() {
	expect("", array(
		"status" => "fail",
		"code" => "error:nourl",
		"errorCode" => "400",
	));

	expect("example.com", array(
		"status" => "fail",
		"code" => "error:missingScheme",
		"errorCode" => "400",
	));

	expect("https://example.com", array(
		"status" => "fail",
		"code" => "error:disallowedhost",
		"errorCode" => "400",
	));

	expect("https://allowed.example.com", array(
		"status" => "success",
	));

	expect("https://sub.allowed.example.com", array(
		"status" => "success",
	));

	expect("https://denied.example.com", array(
		"status" => "fail",
		"code" => "error:disallowedhost",
		"errorCode" => "400",
	));

	expect("https://sub.denied.example.com", array(
		"status" => "fail",
		"code" => "error:disallowedhost",
		"errorCode" => "400",
	));
}

// Note: YOURLS only defines YOURLS_USER after verifying authentication
// but the plugin should continue to work, regardless
echo "Scenario: YOURLS_USER is undefined\n";
testSuite();
echo "Scenario: YOURLS_USER is defined\n";
define('YOURLS_USER', 'anonymous');
testSuite();

// Test what happens if $domainlimit_list is not populated in config
unset($domainlimit_list);

expect("https://example.com", array(
	"status" => "fail",
	"code" => "error:configuration",
	"errorCode" => "500",
));

$total = $results[false] + $results[true];
$passed = $results[true];
echo "$passed/$total tests passed\n";

if ($results[false] > 0) {
	die(1);
}
