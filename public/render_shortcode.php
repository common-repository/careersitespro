<div id="careersitespro">
<?php 
	$careersitespro_tokens = [];
	for($i = 0; $i < 5; $i++) {
		$token_name = "careersitespro_token_{$i}";
		$token = get_option($token_name);
		if(!empty($token))
			$careersitespro_tokens[] = $token;
	}
	$careersitespro_token = implode(',', $careersitespro_tokens);
	//$careersitespro_token = get_option('careersitespro_token');
	$careersitespro_token_array = explode('_', $careersitespro_token);
		
	global $filename;
	$filename = 'https://feeds.applicantpro.com/feeds/wordpress.xml?wordpress_token='.$careersitespro_token;

	$applicant_logo = plugin_dir_url( __FILE__ ).'images/applicant-pro.png';
	
	//$city = get_query_var( 'city' );
	//$src = get_query_var( 'src');
	//$keyword = get_query_var( 'keyword');
	//$state = get_query_var( 'state');
	$reference = get_query_var( 'referencenumber');
	
	if ($reference && is_numeric($reference)) {
		include(  plugin_dir_path( __FILE__ ) . 'render_detail_page.php' );		
	} else {
		include(  plugin_dir_path( __FILE__ ) . 'render_listing_page.php' );		
	}
?>
</div>