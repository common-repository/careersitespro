<?php

/**
 *
 * @wordpress-plugin
 * Plugin Name:       CareerSitesPro
 * Plugin URI:        https://github.com/careersitespro
 * Description:       CareerSitesPro’s plugin provides a simple way to visualize your open positions on your website.
 * Version:           1.3.8
 * Author:            CareerSitesPro
 * Text Domain:       careersitespro
 * Domain Path:       /languages
 */
// If this file is called directly, abort.
if (!defined('WPINC')) {
	die;
}

// Current plugin version and name.
define('CAREERSITESPRO_VERSION', '1.3.8');
define('CAREERSITESPRO_PLUGIN_NAME', 'CareerSitesPro');

// The class responsible for defining all actions that occur in the admin area.
require_once plugin_dir_path(__FILE__) . 'admin/class_admin.php';

// The class responsible for defining all actions that occur in the public-facing.
require_once plugin_dir_path(__FILE__) . 'public/class_public.php';

/**
 * Begins execution of the plugin.
 */
function run_csp() {
	$plugin_admin = new csp_Admin(CAREERSITESPRO_PLUGIN_NAME, CAREERSITESPRO_VERSION);
	$plugin_public = new csp_Public(CAREERSITESPRO_PLUGIN_NAME, CAREERSITESPRO_VERSION);
}

function csp_check_for_shortcode($posts) {
	if (empty($posts))
		return $posts;

	// false because we have to search through the posts first
	$found = false;

	// search through each post
	foreach ($posts as $post) {
		// check the post content for the short code
		if (stripos($post->post_content, '[career_sites_pro]'))
		// we have found a post with the short code
			$found = true;
		// stop the search
		break;
	}

	if ($found) {
		$url = plugin_dir_url(__FILE__) . 'public/';
		// wp_enqueue_style(CAREERSITESPRO_PLUGIN_NAME, $url . 'css/public.css', array(), CAREERSITESPRO_VERSION, 'all');
		// wp_enqueue_style(CAREERSITESPRO_PLUGIN_NAME . '-bootstrap', $url . 'css/bootstrap.min.css', array(), CAREERSITESPRO_VERSION, 'all');
		// wp_enqueue_style(CAREERSITESPRO_PLUGIN_NAME . '-font-awesome', $url . 'css/font-awesome.min.css', array(), CAREERSITESPRO_VERSION, 'all');
		// wp_enqueue_style(CAREERSITESPRO_PLUGIN_NAME . '-fonts', $url . 'css/fonts.css', array(), CAREERSITESPRO_VERSION, 'all');
		wp_enqueue_style(CAREERSITESPRO_PLUGIN_NAME . '-styles', $url . 'css/styles.css', array(), CAREERSITESPRO_VERSION, 'all');
		wp_enqueue_style(CAREERSITESPRO_PLUGIN_NAME . '-responsive', $url . 'css/responsive.css', array(), CAREERSITESPRO_VERSION, 'all');
		wp_enqueue_style(CAREERSITESPRO_PLUGIN_NAME . '-fonts', $url . 'css/fonts.css', array(), CAREERSITESPRO_VERSION, 'all');
		wp_enqueue_style(CAREERSITESPRO_PLUGIN_NAME . '-fa', $url . 'css/font-awesome.min.css', array(), CAREERSITESPRO_VERSION, 'all');
		wp_enqueue_script(CAREERSITESPRO_PLUGIN_NAME, $url . 'js/careersitespro.js', array('jquery'), CAREERSITESPRO_VERSION, true);
		wp_enqueue_script('jquery');
	}
	return $posts;
}

// perform the check when the_posts() function is called
add_action('the_posts', 'csp_check_for_shortcode');


run_csp();

function get_csp_xml_data() {
	global $filename;
	$key = 'csp_data';
	//delete_transient($key);
	$data = get_transient($key);
	if ($data === false) {
		$feed = wp_remote_retrieve_body(wp_remote_get($filename));
		if ($feed != '' && $feed != false) {
			$xml = simplexml_load_string($feed, "SimpleXMLElement", LIBXML_NOCDATA);
			$json = json_encode($xml);
			$job_data = json_decode($json, TRUE);
			$job_data['job'] = cspParseCSJobDataArr($job_data['job']);
			array_walk_recursive($job_data, function(&$v) { $v = trim($v); });
		} else {
			$job_data = [];
		}
		set_transient($key, $job_data, 600);
		$data = $job_data;
	}
	return $data;
}

function cspParseCSJobDataArr($arr) {
	$is_assoc = cspIsAssocArr($arr);
	$result = [];
	if ($is_assoc === true) {
		$result[0] = $arr;
	} else {
		$result = $arr;
	}
	return $result;
}

function cspIsAssocArr(array $arr) {
	if (array() === $arr)
		return false;
	return array_keys($arr) !== range(0, count($arr) - 1);
}

function csp_register_query_vars( $vars ) {
	$vars[] = 'city';
	$vars[] = 'src';
	$vars[] = 'keyword';
	$vars[] = 'state';
	$vars[] = 'referencenumber';
	$vars[] = 'job';
	$vars[] = 'csp_slug';
	$vars[] = 'classification';
	$vars[] = 'employment';
	return $vars;
}
add_filter( 'query_vars', 'csp_register_query_vars' );

function csp_rewrite_job_tags() {
	add_rewrite_tag('%city%', '([^&]+)');
	add_rewrite_tag('%src%', '([^&]+)');
	add_rewrite_tag('%keyword%', '([^&]+)');
	add_rewrite_tag('%state%', '([^&]+)');
	add_rewrite_tag('%referencenumber%', '([^&]+)');
	add_rewrite_tag('%csp_slug%', '([^&]+)');
}
add_action('init', 'csp_rewrite_job_tags', 10, 0);

function csp_rewrite_job_rules() {
	$url = home_url($_SERVER['REQUEST_URI']);
	$slug_array = explode('/', $url);
	$job_val = array_search('job', $slug_array);
	if($job_val > 0) {
		$post_name = $slug_array[$job_val - 1]; // this will be the new slug
	} else {
		$post_name = $slug_array[$job_val]; // this will be the new slug
	}
	
	add_rewrite_rule(
		$post_name . '/job/?([^/]*)/?([^/]*)/?([^/]*)/([^/]+)/?$',
		'index.php?pagename=' . $post_name . '&referencenumber=$matches[4]',
		'top'
	);
	flush_rewrite_rules();
}
add_action('init', 'csp_rewrite_job_rules', 10, 0);

function csp_admin_script() {
	wp_enqueue_script(CAREERSITESPRO_PLUGIN_NAME, plugin_dir_url(__FILE__) . 'admin/js/careersitespro_admin.js', array('jquery'), CAREERSITESPRO_VERSION, true);
}
add_action('admin_enqueue_scripts','csp_admin_script');
