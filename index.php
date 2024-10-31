<?php
/**
 * Plugin Name: Notyf
 * Description: Créez et diffusez des popups, widgets et notifications à vos visiteurs.
 * Version: 0.2.0
 * Author: Notyf
 * Author URI: https://notyf.com
 */
register_uninstall_hook(__FILE__, 'notyf_plugin_cleanup');
add_action('admin_menu', 'notyf_create_menu');

function notyf_plugin_cleanup() {
  delete_option('notyf-script-token');
  delete_option('notyf-onboarding');
}

function notyf_create_menu() {
  add_menu_page(__('Notyf', 'Notyf'), __('Notyf', 'Notyf'), 'administrator', __FILE__, 'notyf_settings_page', plugins_url('assets/notyf-icon-only.svg', __FILE__));
  add_action('admin_init', 'notyf_register_settings');
  add_action('admin_init', 'notyf_onboarding');
}

function notyf_register_settings() {
  register_setting('notyf', 'notyf-script-token');
  add_option('notyf-onboarding', false);
}

function notyf_onboarding() {
  $onboarding = get_option('notyf-onboarding');
  $script_token = get_option('notyf-script-token');

  if ((empty($onboarding) || !$onboarding)) {
    wp_redirect('admin.php?page=' . plugin_basename(__FILE__));
    update_option('notyf-onboarding', true);
  }

  $request_urls = $_POST['request_urls'];
  $request_urls_type = $_POST['request_urls_type'];

  if ($request_urls && $request_urls_type) {
	dumpPreviousData();
	insertRequestUrls( $request_urls_type, $request_urls );
  }

}

function dumpPreviousData() {
	global $wpdb;
	$request_urls_options = $wpdb->get_results( "SELECT option_name FROM $wpdb->options WHERE option_name LIKE 'notyf_request_url_%'" );

	foreach( $request_urls_options as $option ) {
		delete_option( $option->option_name );
	}
}

function insertRequestUrls($type, $urls) {
	foreach ($urls as $key => $url) {
		$index = $key+1;
		if (!empty($url)) {
			$arr = array(
				'type' => $type[$key],
				'url' => $url
			);
			add_option( "notyf_request_url_$index", $arr );
		}
	}
}

function notyf_settings_page() {
  $email = urlencode(wp_get_current_user()->user_email);

  ?>
    <div class="card" style="max-width: 60%;">
      <a href="https://notyf.com?utm_source=wordpress" target="_blank" rel="noopener">
        <img style="margin-left: -13px;" src="<? echo plugins_url("assets/logo.png", __FILE__ ); ?>" width="180"/>
      </a>
      <? settings_errors(); ?>
      <h3>Installation</h3>
      <p>1. Créer un compte gratuitement sur <a href="https://notyf.com/register?utm_source=wordpress" target="_blank" rel="noopener">notyf.com</a></p>
      <p>2. Copier et coller le script d'installation ci-dessous :</p>

      <form action="options.php" method="POST">
        <?
          echo settings_fields('notyf');
          echo do_settings_sections('notyf');
          ?>
        <textarea name="notyf-script-token" id="notyf-script-token" cols="60" rows="20"><? echo esc_attr(get_option('notyf-script-token')) ?></textarea>
        <br>
		<hr style="margin:30px 0">
    <h3>Webhook(s)</h3>
      <p>1. Créer une notification de dernière conversion sur <a href="https://notyf.com/register?utm_source=wordpress" target="_blank" rel="noopener">notyf.com</a></p>
      <p>2. Dans le menu Capture de la notification, copier l'adresse URL du webhook et la coller ici.</p>
      <p>3. Choisir les informations à collecter. (Commandes, ajouts au panier ou inscriptions)</p>
		<table>
			<tbody id="notyf-url-fields">
			<tr>
				<td colspan="4">
					<button type="button" class="button button-primary alignright" onclick="addUrlFields();">
						<span class="dashicons dashicons-plus" style="vertical-align: middle;"></span> Ajouter un webhook
					</button>
				</td>
			</tr>
			<tr>
				<td>
					<select name="request_urls_type[]">
						<option value="order" selected="selected">Commandes</option>
						<option value="cart">Ajouts au panier</option>
						<option value="registration">Inscriptions</option>
					</select>
				</td>
				<td>
					<input type="text" name="request_urls[]" value="" placeholder="https://notyf.com/pixel-webhook/exemple" class="regular-text">
				</td>
				<td>
					<button class="button button-primary btn-notyf-remove-field" type="button" onclick="removeUrlField(this);">
						<span class="dashicons dashicons-trash" style="vertical-align: sub;"></span>
					</button>
				</td>
			</tr>


			<?
			global $wpdb;
			$request_urls_options = $wpdb->get_results( "SELECT option_name FROM $wpdb->options WHERE option_name LIKE 'notyf_request_url_%'" );

			foreach( $request_urls_options as $option ) {
				$item = get_option($option->option_name);
				?>
				<tr>
					<td>
						<select name="request_urls_type[]">
							<option value="order" <? if ($item['type'] == "order") { ?> selected="selected" <? } ?>>Commandes</option>
							<option value="cart" <? if ($item['type'] == "cart") { ?> selected="selected" <? } ?>>Ajouts au panier</option>
							<option value="registration" <? if ($item['type'] == "registration") { ?> selected="selected" <? } ?>>Inscriptions</option>
						</select>
					</td>
					<td>
						<input type="text" name="request_urls[]" value="<?  echo esc_attr($item['url']) ?>" placeholder="https://notyf.com/pixel-webhook/exemple" class="regular-text">
					</td>
					<td>
						<button class="button button-primary btn-notyf-remove-field" type="button" onclick="removeUrlField(this);">
							<span class="dashicons dashicons-trash" style="vertical-align: sub;"></span>
						</button>
					</td>
				</tr>
				<?
			}
			?>


			</tbody>
		</table>
        <? submit_button(); ?>
      </form>
    </div>
  <?
}

function notyf_javascript_block() {
  echo get_option('notyf-script-token');
}

add_action('wp_head', 'notyf_javascript_block', 1);

/**
 * ADMIN JS SCRIPTS
 */

add_action('admin_enqueue_scripts', 'enqueue');

function enqueue() {
	// enqueue all scripts
	wp_enqueue_script( 'notyfscript', plugins_url( 'assets/js/notyf.js', __FILE__ ), false, 1.0 );
}

/**
 * NEW USER REGISTRATION REQUEST
 */

add_action( 'user_register', 'new_user_callback' );

function new_user_callback( $id ) {
	$current_user = get_user_by( 'id', $id );

	$postRequest = array(
		'Customer_Id' => $id,
		'Customer_Email' => $current_user->user_email ,
		'Customer_Name' => $current_user->display_name,
	);

	global $wpdb;
	$request_urls_options = $wpdb->get_results( "SELECT option_name FROM $wpdb->options WHERE option_name LIKE 'notyf_request_url_%'" );

	foreach( $request_urls_options as $option ) {
		$item = get_option($option->option_name);
		if ( $item['type'] == "registration" ) {
			$cURLConnection = curl_init($item['url']);
			curl_setopt($cURLConnection, CURLOPT_POSTFIELDS, $postRequest);
			curl_setopt($cURLConnection, CURLOPT_RETURNTRANSFER, true);

			$apiResponse = curl_exec($cURLConnection);
			curl_close($cURLConnection);
		}
	}
}

/**
 * ORDER REQUEST
 */

add_action( 'woocommerce_checkout_order_processed', 'woocommerce_order_placed_callback' );

function woocommerce_order_placed_callback( $id_order ) {

	if ( ! $id_order ) {
		return;
	}

	// Get $order object from order ID
	$order = wc_get_order( $id_order );

	$postRequest = array(
		'Order_Id' => $order->get_id(),
		'Order_total_paid' => $order->get_total(),
		'Order_total_discounts' => $order->get_discount_total(),
		'Order_total_shipping' => $order->get_shipping_total(),
		'Order_Date' => $order->get_date_created(),
		'Order_State' => $order->get_status(),
		'Order_Currency' => $order->get_currency(),
		'User_Id' => ( $order->get_user_id() != 0 ) ? ($order->get_user_id()) : '---',
		'Customer_Id' => ( $order->get_customer_id() != 0 ) ? ($order->get_customer_id()) : '---',
		'Customer_Email' => $order->get_billing_email(),
		'Customer_Shipping_First_Name' => $order->get_shipping_first_name(),
		'Customer_Shipping_Last_Name' => $order->get_shipping_last_name(),
		'Address_Delivery_1' => $order->get_shipping_address_1(),
		'Address_Delivery_2' => !empty($order->get_shipping_address_2()) ? ($order->get_shipping_address_2()) : '---'
	);

	// Get and Loop Over Order Items
	$index = 1;
	foreach ( $order->get_items() as $key => $item ) {
		$postRequest['Product_'.$index.'_ID'] = $item->get_product_id();
		$postRequest['Product_'.$index.'_Name'] = $item->get_name();
		$postRequest['Product_'.$index.'_Quantity'] = $item->get_quantity();
		$postRequest['Product_'.$index.'_Price'] = $item->get_subtotal();

		$product   = wc_get_product( $item->get_product_id() );
		$image_id  = $product->get_image_id();
		$image_url = wp_get_attachment_image_url( $image_id, 'full' );
		$postRequest['Product_'.$index.'_ImageURL'] = $image_url;
		$index++;
	}


	global $wpdb;
	$request_urls_options = $wpdb->get_results( "SELECT option_name FROM $wpdb->options WHERE option_name LIKE 'notyf_request_url_%'" );

	foreach( $request_urls_options as $option ) {
		$item = get_option($option->option_name);
		if ( $item['type'] == "order" ) {
			$cURLConnection = curl_init($item['url']);
			curl_setopt($cURLConnection, CURLOPT_POSTFIELDS, $postRequest);
			curl_setopt($cURLConnection, CURLOPT_RETURNTRANSFER, true);

			$apiResponse = curl_exec($cURLConnection);
			curl_close($cURLConnection);
		}
	}
}

/**
 * ADD TO CART REQUEST
 */

add_action( 'woocommerce_add_to_cart', 'woocommerce_add_to_cart_callback' );

function woocommerce_add_to_cart_callback($key) {

	if ( ! $key ) {
		return;
	}

	$qty = (int) $_POST['quantity'];
	$cart = WC()->cart->get_cart_item( $key );
	$id_product = $cart['product_id'];
	$product = wc_get_product( $id_product );
	$product_url = get_permalink( $id_product );
	$image_id  = $product->get_image_id();
	$image_url = wp_get_attachment_image_url( $image_id, 'full' );

	$postRequest = array(
		'Product_Id'                => $id_product,
		'Product_Name'              => $product->get_name(),
		'Product_Image_Url'         => $image_url,
		'Product_Url'               => $product_url,
		'Product_Added_Quantity'    => $qty,
	);

	global $wpdb;
	$request_urls_options = $wpdb->get_results( "SELECT option_name FROM $wpdb->options WHERE option_name LIKE 'notyf_request_url_%'" );

	foreach( $request_urls_options as $option ) {
		$item = get_option($option->option_name);
		if ( $item['type'] == "cart" ) {
			$cURLConnection = curl_init($item['url']);
			curl_setopt($cURLConnection, CURLOPT_POSTFIELDS, $postRequest);
			curl_setopt($cURLConnection, CURLOPT_RETURNTRANSFER, true);

			$apiResponse = curl_exec($cURLConnection);
			curl_close($cURLConnection);
		}
	}
}
