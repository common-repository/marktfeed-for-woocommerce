<?php

// ------------------------------------------------------------------------------------------------------
// Handle the plugin options in the Wordpress settings menu
// ------------------------------------------------------------------------------------------------------
//


add_action( 'admin_menu', 'marktfeed_menu' );
add_action( 'template_redirect', 'marktfeed_handleCallback' );

$plugin_slug = MARKTFEED_SLUG;


// Set a global var to track whether we are on our own options page
// This is used later to include CSS only on our own options page
function marktfeed_menu() {
  global $gMarktfeed_OptionsPage;
  $gMarktfeed_OptionsPage = add_options_page(
    'Options for '.MARKTFEED_BRAND_HUMAN, MARKTFEED_BRAND_HUMAN, 'manage_options', MARKTFEED_BRAND_LOWERCASE, 'marktfeed_options'
  );
}

// Load our css for admin pages
add_action( 'admin_enqueue_scripts', 'marktfeed_enqueue_admin_css' );
function marktfeed_enqueue_admin_css($hook) {
  // We only enqueue this css when we are on our own settings page
  // because we change css of Wordpress elements
  // We don't want to change the global look of the site
  global $gMarktfeed_OptionsPage;
  if   ( $hook == $gMarktfeed_OptionsPage ) {
    wp_register_style( 'marktfeed_admin_css', plugins_url('css/easyads.css', dirname(__FILE__)), false, '1.0.0' );
    wp_enqueue_style( 'marktfeed_admin_css' );
  }
}


// Return the options page
function marktfeed_options() {
	if ( !current_user_can( 'manage_options' ) )  {
		wp_die( __( 'You do not have sufficient permissions to access this page.' ) );
	}

	// Does the user want to connect, disconnect or get the status?
	$connect = $_GET['connect'] ? $_GET['connect'] : 'status';
	if ($connect == 'connect') {
  	marktfeed_handleConnect();
	} else if ($connect == 'waiting_for_callbak') {
    // Do nothing on purpose
	} else if ($connect == 'disconnect') {
  	marktfeed_handleDisconnect();
	} else if ($connect == 'status') {
    // Do nothing on purpose
	} else {
  	// Somebody is messing with the query.
  	// Fail silently and return status
    // Do nothing on purpose
	}

?>
<div class="wrap">
<div class="easyads-header-wrap">

<div class="easyads-header">
<?php
  $logo_file = dirname(__FILE__) . '/../img/logo.html';
  include $logo_file;
?>

<p class="easyads-intro"><?php echo MARKTFEED_HEADER_TEXT; ?></p>
</div><!-- .easyads-header -->
</div><!-- .easyads-header-wrap -->

<div class="easyads-body">

<?php
  $active_tab = isset( $_GET[ 'tab' ] ) ? $_GET[ 'tab' ] : 'status-tab';
  global $plugin_slug;
?>
<h2 class="nav-tab-wrapper">
    <a href="?page=<?php echo $plugin_slug; ?>&tab=status-tab" class="nav-tab <?php echo $active_tab == 'status-tab' ? 'nav-tab-active' : ''; ?>">Status</a>
</h2>

<form method="post" action="options.php">
<?php
  if ($active_tab == 'status-tab') {
    include('tabs/easyads-tab-status.php');
  }
  else if ($active_tab == 'about-tab') {
    include('tabs/easyads-tab-about.php');
  } else {
    echo 'Error: unknown tab.';
  }
?>


</form>
</div><!-- .easyads-body -->
</div><!-- .wrap -->
<?php
}


// marktfeed_handleConnect
// 1. Enable the WooCommerce API
// 2. Create the WordPress user that will get access to the WooCommerce API
// 3. Create the WooCommerce API keys for the WordPress user
// 4. Redirect to the EasyAds website

function marktfeed_handleConnect() {
  try {
    marktfeed_enableWoocommerceAPI();
    $user_id = marktfeed_createWordPressUser();
    $api_key = marktfeed_addWoocommerceAPIkey($user_id);
    update_option(MARKTFEED_STATUS_OPTION_NAME, 'waiting_for_callback');
    marktfeed_redirectToEasyAds($api_key);
  }
  catch (\Exception $e) {
    echo 'Error in marktfeed_handleConnect: ' . $e->getMessage();
    exit;
  }
}


// marktfeed_handleCallback
// The EasyAds website calls us back with a GUID
// Once we received this call we are connected
// 1. Store the GUID
// 2. Update our status to connected

function marktfeed_handleCallback() {
  if (!isset($_REQUEST['marktfeed_callback'])) return;

  $guid = $_REQUEST['guid'];
//	if ($guid && $guid != '') {
    update_option(MARKTFEED_GUID_OPTION_NAME, $guid);
    update_option(MARKTFEED_STATUS_OPTION_NAME, 'connected');
    $result = array('result' => 'ok', 'error' => '');
    echo json_encode($result);
    exit;
//  } else {
//    $result = array('result' => 'error', 'error' => 'guid not found');
//    echo json_encode($result);
//    exit;
//  }
}


// marktfeed_handleDisconnect
// 1. Remove the stored GUID
// 2. Revoke the WooCommerce API keys

function marktfeed_handleDisconnect() {
  $guid = get_option(MARKTFEED_GUID_OPTION_NAME);
  delete_option(MARKTFEED_GUID_OPTION_NAME);
  marktfeed_deleteWoocommerceAPIkey();
  update_option(MARKTFEED_STATUS_OPTION_NAME, 'disconnected');
}


// marktfeed_getConnectionStatus
// Returns the current connection status

function marktfeed_getConnectionStatus() {
  $validStatuses = array('connected', 'waiting_for_callback', 'disconnected');
  $status = get_option(MARKTFEED_STATUS_OPTION_NAME);
  if (in_array($status, $validStatuses, true)) {
    return $status;
  } else {
    // Somebody messing with the status record in the database
    // Set it to 'disconnected'
    update_option(MARKTFEED_STATUS_OPTION_NAME, 'disconnected');
    return 'disconnected';
  }
  // We should never get here
  throw new \Exception('marktfeed_getConnectionStatus: An impossible error just occured.');
}



// marktfeed_createWordPressUser
// 1. Create the WordPress user
// 2. Set the role of the WordPress user to "Shop Manager"

function marktfeed_createWordPressUser() {

  // Create the user if needed
  $user_name = MARKTFEED_WP_USERNAME;
  $user_id = username_exists($user_name);
  if (!$user_id) {
  	$random_password = wp_generate_password($length=24, $include_standard_special_chars=false);
  	$user_id = wp_create_user($user_name, $random_password);
  }
  if (!$user_id) throw new \Exception("marktfeed_createWordPressUser: could not create user $user_name");

  // Ensure the user has role shop_manager
  $user = new WP_User( $user_id );
  $user->set_role('shop_manager');

  return $user_id;
}



// marktfeed_enableWoocommerceAPI
// Enable the WooCommerce API

function marktfeed_enableWoocommerceAPI() {
  // Note, I did not find a formal WooCommerce function to call
  // So we're doing this directly
  update_option('woocommerce_api_enabled', 'yes');
}



// marktfeed_addWoocommerceAPIkey
// Adds WooCommerce API keys for the user ($user_id)
// Note: there is no formal WooCommerce API call that can do this
// for us. Therefore we do it ourselves.
//
// See woocommerce/includes/Class-wc-ajax for an example of how
// WooCommerce generates these keys

function marktfeed_addWoocommerceAPIkey($user_id) {
  if (!$user_id) throw new \Exception('marktfeed_addWoocommerceAPIkeys: User invalid');

	// Always generate a new key.
	// Keys are stored encrypted, so sending the existing key again is useless as we can't decypher it, nor use it.

  // Create the keys
  $description = MARKTFEED_WOO_API_DESCRIPTION;
  $permissions = 'read';
	$consumer_key    = 'ck_' . wc_rand_hash();
	$consumer_secret = 'cs_' . wc_rand_hash();

	$data = array(
		'user_id'         => $user_id,
		'description'     => $description,
		'permissions'     => $permissions,
	  'consumer_key'    => wc_api_hash( $consumer_key ),
		'consumer_secret' => $consumer_secret,
		'truncated_key'   => substr( $consumer_key, -7 )
	);

	global $wpdb;
	$wpdb->insert(
		$wpdb->prefix . 'woocommerce_api_keys',
		$data,
		array(
			'%d',
			'%s',
			'%s',
			'%s',
			'%s',
			'%s'
		)
	);
  return array('consumer_key' => $consumer_key, 'consumer_secret' => $consumer_secret);
}


// marktfeed_deleteWoocommerceAPIkey
// Delete the WooCommerce API keys

function marktfeed_deleteWoocommerceAPIkey() {
  global $wpdb;
  $user_name = MARKTFEED_WP_USERNAME;
  $user_id = username_exists($user_name);
  if ($user_id) {
    $wpdb->query("DELETE FROM " . $wpdb->prefix . "woocommerce_api_keys WHERE user_id = $user_id");
  }
}


// marktfeed_redirectToEasyAds
// Redirect the user to the following page:
// http://www.easyadswebsite.nl/link/woocommerce?ck=<woocommerce consumer key>&cs=<woocommerce consumer secret>&cb=<urlencoded
// callback url>

function marktfeed_redirectToEasyAds($api_key) {
  wp_redirect(marktfeed_getEasyAdsLinkUrl($api_key));
  exit;
}


// marktfeed_getEasyAdsLinkUrl
// Returns the link url for the Easyads website

function marktfeed_getEasyAdsLinkUrl($apiKeys = false) {
	if (!$apiKeys)
		return;

  global $wp_version;

  //$user_name = MARKTFEED_WP_USERNAME;
  //$user_id = username_exists($user_name);
  //if (!$user_id) throw new \Exception("marktfeed_getCallbackUrl: user " . MARKTFEED_WP_USERNAME . " does not exist");
  //global $wpdb;
  //$api_key = $wpdb->get_row("SELECT * FROM " . $wpdb->prefix . "woocommerce_api_keys WHERE user_id = $user_id");
  //if (!$api_key) throw new \Exception("marktfeed_getCallbackUrl: api Key does not exist");

  // Parameters for the link url
  // ck = consumer key
  // cs = consumer secret
  // cb = callback url
  // pv = plugin version
  // wpv = wordpress version
  // wcv = woocommerce version

  $userData = wp_get_current_user();

  $ck = urlencode($apiKeys['consumer_key']);
  $cs = urlencode($apiKeys['consumer_secret']);
  $cb = urlencode(marktfeed_getCallbackUrl());
  $pv = urlencode(MARKTFEED_PLUGIN_VERSION);
  $wpv = urlencode($wp_version);
  $wcv = urlencode(get_option( 'woocommerce_version', 'unknown'));
  $e = urlencode($userData->data->user_email);

  $easyads_url = MARKTFEED_LINK_URL . "?ck=$ck&cs=$cs&cb=$cb&pv=$pv&wpv=$wpv&wcv=$wcv&e=$e";
  return $easyads_url;
}


// marktfeed_getEasyAdsUnlinkUrl
// Returns the unlink url for EasyAds website

function marktfeed_getEasyAdsUnlinkUrl() {
  $guid = get_option(MARKTFEED_GUID_OPTION_NAME);
  $url = MARKTFEED_UNLINK_URL . "?guid=$guid";
  return $url;
}



function marktfeed_getCallbackUrl() {
  $callback_url = get_site_url() . "?marktfeed_callback=1";
  return $callback_url;
}
