<?php
  /*

  Include Class for WP Comment List-Builder
  Developer: Tobias B. Conrad (company)
  Version: 3.6.2
  Datum: 03.05.2016
  */
  if (basename($_SERVER['SCRIPT_FILENAME']) == 'klick-tipp-listbuilding.class.php') { die ("Please do not access this file directly. Thanks!"); }

  define('LISTBUILDER_VERSION', '4.0.0');  

  if(!class_exists("klicktippListbuilding")) {

    

	class clb_klicktippListbuilding {

      

		var $adminOptionsName = "Optionswplistbuilder";

		var $showBox = true;

		var $sPluginName = 'Comment ListBuilder';

		var $klickTippL = null;

		var $klickTippP = null;

		private $klickTippConnector = null;

		private $klickTippIsLoggedIn = false;

		

		public function __construct() {

			$this->handleAjax();

			

			add_filter('load_textdomain_mofile', array($this, 'clb_setDefaultTranslationFile'), 17, 2);

				

		  add_action('admin_menu', array($this, 'clb_onAdminInit'));

	

		  add_action('comment_post', array($this, 'clb_on_comment_post'),8,2);

		  add_filter('comment_form_field_comment',array($this, 'clb_add_to_comments_field'),17); //filter for WP > v3.0

		  add_filter('comment_form',array($this, 'clb_add_to_comments_with_script'),17); //filter for WP < v3.0

	

		  add_filter('wp_set_comment_status',array($this, 'clb_on_comment_approved'),17,2);

		  add_action('admin_notices', array($this, 'clb_AdminNotices'));

	

		  add_filter( 'plugin_action_links_' . CLB_LISTBUILDER_PLUGIN_BASENAME, array($this, 'clb_setAdminActionPluginLinks'), 17, 5 );



		  add_action( 'add_meta_boxes', array($this, 'clb_add_kt_lb_meta_box_settings'),5,2);

		  add_filter( 'manage_posts_columns', array($this, 'clb_add_kt_lb_posts_columns'), 10, 2 );

		  add_filter( 'manage_pages_columns', array($this, 'clb_add_kt_lb_pages_columns'), 10, 2 );

		  add_action( 'bulk_edit_custom_box', array($this, 'clb_add_kt_lb_bulk_edit_custom_box'), 10, 2 );
		
		  add_action( 'quick_edit_custom_box', array($this, 'clb_add_kt_lb_quick_edit_custom_box'), 10, 2 );

		  add_action( 'manage_posts_custom_column', array($this, 'clb_add_kt_lb_manage_posts_custom_column'), 10, 2 );

		  add_action( 'manage_pages_custom_column', array($this, 'clb_add_kt_lb_manage_posts_custom_column'), 10, 2 );

		  add_action( 'wp_ajax_clb_save_bulk_edit', array($this, 'clb_save_bulk_edit' ) );

		  add_action( 'save_post', array($this, 'clb_save_kt_lb_meta_boxes_data'), 5, 2 );

		  add_action( 'admin_enqueue_scripts', array($this, 'clb_register_admin_assets' ) );

		  add_action( 'wp_enqueue_scripts', array($this, 'clb_register_frontend_assets' ) );

		  add_action( 'admin_print_scripts-edit.php', array($this, 'clb_enqueue_edit_scripts' ) );

		  add_action( 'admin_footer', array($this, 'clb_defaults_html_footer') );

		}	

		public function __destruct() {

			if (!is_null($this->klickTippConnector)) {

				$this->klickTippConnector->logout();

			}

		}

		public function clb_register_admin_assets( ) {
			wp_enqueue_script('listbuilder-arcode-js', plugins_url('js/arcode.js', CLB_LISTBUILDER_PLUGIN_BASENAME), array(), '1.0' );
		  	wp_enqueue_script('listbuilder-meta-kl-box-js', plugins_url('js/meta_kl_box.js', CLB_LISTBUILDER_PLUGIN_BASENAME), array(), '1.0' );
		  	wp_enqueue_style('listbuilder-admin-css', plugins_url('assets/css/admin-style.css', CLB_LISTBUILDER_PLUGIN_BASENAME), array(), null, 'all');
		}

		public function clb_enqueue_edit_scripts() {
		   wp_enqueue_script( 'listbuilder-quick-edit-js', plugins_url('js/quick_edit.js', CLB_LISTBUILDER_PLUGIN_BASENAME), array( 'jquery' ), '', true );
		}

		public function clb_register_frontend_assets( ) {
			wp_enqueue_style('listbuilder-general-css', plugins_url('assets/css/custom-style.css', CLB_LISTBUILDER_PLUGIN_BASENAME), array(), null, 'all');
		}

		public function is_edit_page($new_edit = null){

		    global $pagenow;

		    //make sure we are on the backend

		    if (!is_admin()) return false;





		    if($new_edit == "edit")

		        return in_array( $pagenow, array( 'post.php',  ) );

		    elseif($new_edit == "new") //check for new post page

		        return in_array( $pagenow, array( 'post-new.php' ) );

		    else //check for either new or edit

		        return in_array( $pagenow, array( 'post.php', 'post-new.php' ) );

		}

		

		function clb_setDefaultTranslationFile($mofile, $domain) {

			if ($domain == 'comment-list-builder' && (strstr($mofile, 'de_DE') !== false)) { 

				return $mofile;

			}

			if ($domain == 'comment-list-builder' && !is_readable($mofile)) { 

				extract(pathinfo($mofile)); 

				$pos = strrpos($filename, '_'); 

		

				if ($pos !== false) {

					$filename = "comment-list-builder-en_US";

					$mofile = $dirname . '/' . $filename . '.' . $extension; 

				} 

			}

			

			return $mofile; 

		} 

		

		/**

		 * Connect to the KlickTipp API

		 * @return object<KTConnector>

		 */

		private function connectToKlickTipp() {

			if (is_null($this->klickTippConnector)) {

				$aOptions = $this->getAdminOptions();

				

				$apiValue = $aOptions['klicktippApi'];

				if ($apiValue == 'br') {

					$apiUrl = 'http://api.klickmail.com.br';

				} else {

					$apiUrl = 'http://api.klick-tipp.com';

				}

				

				$this->klickTippConnector = new CB_KlicktippConnector($apiUrl);

				$this->klickTippIsLoggedIn = $this->klickTippConnector->login($aOptions['klicktippUser'], $aOptions['klicktippPassword']);

			}

			return $this->klickTippConnector;

		}

		

		private function handleAjax() {

			if (isset($_GET) && array_key_exists('ajax', $_GET) && $_GET['ajax'] == 'dismiss') {

				$aOptions = $this->getAdminOptions();

				$aOptions['listbuilder-dismiss-trial-notice'] = true;

				update_option($this->adminOptionsName, $aOptions);

				die();

			}

		}

	

		private function getAdminOptions() {

			

			$adminOptions = array();

			$andiOpts = get_option($this->adminOptionsName);

			if(!empty($andiOpts)) {

			  

			  foreach($andiOpts as $key => $option) {

				$adminOptions[$key] = $option;

			  }

			}

			

			update_option($this->adminOptionsName, $adminOptions);

			

			return $adminOptions;

		}

		

		

		public function clb_setAdminActionPluginLinks( $actions, $plugin_file ) {

			return array_merge(array('settings' => '<a href="options-general.php?page=listbuilding">' . __('Einstellungen', 'comment-list-builder') . '</a>'), $actions);;

		}

		

		

		/**

		 * Check if the plugin version is active

		 *

		 * code 0 = plugin is inactive

		 * code 1 = plugin is active

		 * code 2 = plugin will be inactive

		 * 

		 * @return int Status code if plugin is active

		 */

		private function checkPluginVersion() {

			$url = 'http://saleswonder.biz/klicktip-capi/check_version.php?plugin=listbuilder&version=' . LISTBUILDER_VERSION;

			if (function_exists('curl_version')) {

				$curl = curl_init();

				curl_setopt($curl, CURLOPT_URL, $url);

				curl_setopt($curl,CURLOPT_USERAGENT,'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.17 (KHTML, like Gecko) Chrome/24.0.1312.52 Safari/537.17');

				curl_setopt($curl, CURLOPT_AUTOREFERER, true); 

				curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

				curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);

				curl_setopt($curl, CURLOPT_VERBOSE, 1);

				$response = trim(curl_exec($curl));

				curl_close($curl);

				

			} else if (file_get_contents(__FILE__) && ini_get('allow_url_fopen')) {

				$response = trim(file_get_contents($url));

				

			}

			if ($response == '1') {

				return 1;

			} else if ($response == '2') {

				return 2;

			}

			return 0;

		}

		

		

		public function klicktippListbuilding_admin() {

			?>

			<div class="wrap">

				<h2><?php echo $this->sPluginName; ?></h2>

				

				<?php

					if (isset($_GET['tab']) && ($_GET['tab'] == 'autoresponder')) {

						$this->adminPageAutoresponder();

					} else if (isset($_GET['tab']) && ($_GET['tab'] == 'klicktipp')) {

						$this->adminPageKlicktipp();

					} else if (isset($_GET['tab']) && ($_GET['tab'] == 'license')) {

						$this->adminPageLicense();

					} else {

						$this->adminPageDefault();

					}

				?>

				

			</div>

			<?php

		}

		

		private function adminTabBar() {

			$activeTab = '';

			if (array_key_exists('tab', $_GET)) {

				if ($_GET['tab'] == 'autoresponder') {

					$activeTab = 'autoresponder';

				} else if ($_GET['tab'] == 'klicktipp') {

					$activeTab = 'klicktipp';

				} else if ($_GET['tab'] == 'license') {

					$activeTab = 'license';

				}

			} ?>

			<h2 class="nav-tab-wrapper">

				<a class="nav-tab <?php if (empty($activeTab)) { echo 'nav-tab-active'; } ?>" href="<?php echo admin_url() ?>/options-general.php?page=listbuilding"><?php _e('Einstellung', 'comment-list-builder'); ?></a>

				<a class="nav-tab <?php if ($activeTab == 'klicktipp') { echo 'nav-tab-active'; } ?>" href="<?php echo admin_url() ?>/options-general.php?page=listbuilding&tab=klicktipp"><?php _e('Klick-Tipp API', 'comment-list-builder'); ?></a>

				<a class="nav-tab <?php if ($activeTab == 'autoresponder') { echo 'nav-tab-active'; } ?>" href="<?php echo admin_url() ?>/options-general.php?page=listbuilding&tab=autoresponder"><?php _e('Autoresponder', 'comment-list-builder'); ?></a>

				<a class="nav-tab <?php if ($activeTab == 'license') { echo 'nav-tab-active'; } ?>" href="<?php echo admin_url() ?>/options-general.php?page=listbuilding&tab=license"><?php _e('Lizenz', 'comment-list-builder'); ?></a>

			</h2> <?php

		}

		

		private function getListbuilderPromoDownloadUrl() {

			if (defined('COMMENT_LIST_BUILDER_AFFILIATE')) {

				$promoUrl = 'http://saleswonder.biz/plugins_download_server/download.php?plugin=listbuilder&affiliate=' . COMMENT_LIST_BUILDER_AFFILIATE;

			} else {

				$promoUrl = 'http://saleswonder.biz/plugins_download_server/download.php?plugin=listbuilder&affiliate=';

			}

			if (defined('COMMENT_LIST_BUILDER_CAMPAIGNKEY')) {

				$promoUrl .= '&campaign=' . COMMENT_LIST_BUILDER_CAMPAIGNKEY;

			}

			return $promoUrl;

		}

		

		public function getAffiliateParameterString() {

			$parameters = '';

			

			if (defined('COMMENT_LIST_BUILDER_AFFILIATE')) {

				$parameters .= '&aff=' . COMMENT_LIST_BUILDER_AFFILIATE . '&affiliate=' . COMMENT_LIST_BUILDER_AFFILIATE;

			}

			if (defined('COMMENT_LIST_BUILDER_CAMPAIGNKEY')) {

				$parameters .= '&campaign=' . COMMENT_LIST_BUILDER_CAMPAIGNKEY;

			}

			

			return $parameters;

		}

		

		private function getListbuilderPromoUrl() {

			$splittestUrl = "http://api.splittest-club.com/splittest.php?test=" . __('15036', 'comment-list-builder') . "&format=clean&ip=" . $_SERVER["REMOTE_ADDR"];

			if (function_exists('curl_version')) {

				$curl = curl_init();

				curl_setopt($curl, CURLOPT_URL, $splittestUrl);

				curl_setopt($curl,CURLOPT_USERAGENT,'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.17 (KHTML, like Gecko) Chrome/24.0.1312.52 Safari/537.17');

				curl_setopt($curl, CURLOPT_AUTOREFERER, true); 

				curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

				curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);

				curl_setopt($curl, CURLOPT_VERBOSE, 1);

				$promoProductId = curl_exec($curl);

				curl_close($curl);

				

			} else if (file_get_contents(__FILE__) && ini_get('allow_url_fopen')) {

				$promoProductId = file_get_contents($splittestUrl);

				

			}

			

			if (defined('COMMENT_LIST_BUILDER_AFFILIATE')) {

				$licensePromoUrl = 'http://promo.' . COMMENT_LIST_BUILDER_AFFILIATE . '.' . $promoProductId . '.digistore24.com/';

			} else {

				$licensePromoUrl = 'http://promo.tobias-conrad.' . $promoProductId . '.digistore24.com/';

			}

			if (defined('COMMENT_LIST_BUILDER_CAMPAIGNKEY')) {

				$licensePromoUrl .= COMMENT_LIST_BUILDER_CAMPAIGNKEY;

			}

			return $licensePromoUrl;

		}

		

		public function adminPageDefault() {

			$args = array('public' => true );
        	$output = 'objects';
        	$operator = 'and';
        	$post_types = get_post_types( $args, $output, $operator );

			$message = "";

			$aOptions = $this->getAdminOptions();

	

			if(!empty($_POST['update_klicktippListbuilding'])) {

			  if($_POST['klicktippAdditionalText']) {$aOptions['klicktippAdditionalText'] = stripslashes($_POST['klicktippAdditionalText']);} else { unset($aOptions['klicktippAdditionalText'] );}

				if($_POST['klicktippCheckboxText']) {$aOptions['klicktippCheckboxText'] = trim($_POST['klicktippCheckboxText']);} else { unset($aOptions['klicktippCheckboxText'] );}

				if($_POST['klicktippCheckboxChecked']) {$aOptions['klicktippCheckboxChecked'] = trim($_POST['klicktippCheckboxChecked']);} else { unset($aOptions['klicktippCheckboxChecked'] );}

				if($_POST['klicktippAddSubscriberWithoutRelease']) {$aOptions['klicktippAddSubscriberWithoutRelease'] = trim($_POST['klicktippAddSubscriberWithoutRelease']);} else { unset($aOptions['klicktippAddSubscriberWithoutRelease'] );}

				

				if ($_POST['plugin-activate']) {

					$allowedPlugins = array('klicktipp', 'autoresponder');

					if (in_array($_POST['plugin-activate'], $allowedPlugins)) {

						$aOptions['listbuilder-plugin-active'] = trim($_POST['plugin-activate']);

					} else {

						$aOptions['listbuilder-plugin-active'] = '';

					}

				} else {

					$aOptions['listbuilder-plugin-active'] = '';

				}

				

				if (isset($_POST['privacy-url'])) {

					$aOptions['listbuilder_privacy_url'] = trim($_POST['privacy-url']);

				} else {

					$aOptions['listbuilder_privacy_url'] = '';

				}

				foreach ( $post_types as $k => $post_type ) {
					if($_POST['clb_pt_'.$post_type->name]) {$aOptions['clb_pt_'.$post_type->name] = trim($_POST['clb_pt_'.$post_type->name]);} else { unset($aOptions['clb_pt_'.$post_type->name] );}
				}

				

				update_option($this->adminOptionsName, $aOptions);

				

				$message = '<div class="updated"><p><strong>' . __('Hinweis: Die Einstellungen wurden erfolgreich gespeichert.', 'comment-list-builder') . '</strong></p></div>';

			}

			foreach ( $post_types as $k => $post_type ) {			
				if($_POST['clb_pt_'.$post_type->name] == 'TRUE') {
					$post_ids = get_posts(array(
				        'post_type' => $post_type->name,
				        'post_status'   => 'any',
				        'posts_per_page'=> -1,
				        'fields'        => 'ids',
				        'meta_query' => array(
						    array(
						         'key'     => '_listbuilder_plugin_active',
						         'value'   => array('default', 'klicktipp', 'autoresponder'),
						         'compare' => 'NOT IN'
						    )
						)
				    ));
				    foreach ($post_ids as $post_id) {
				    	update_post_meta( $post_id, '_listbuilder_plugin_active', 'default');
				    }
				} else {
					$post_ids = get_posts(array(
				        'post_type' => $post_type->name,
				        'post_status'   => 'any',
				        'posts_per_page'=> -1,
				        'fields'        => 'ids',
				        'meta_key'   	=> '_listbuilder_plugin_active',
   						'meta_value' 	=> 'default'
				    ));
				    foreach ($post_ids as $post_id) {
				    	update_post_meta( $post_id, '_listbuilder_plugin_active', '');
				    }
				}
				if($_POST['clb_pr_'.$post_type->name] == 'TRUE' && $_POST['clb_pt_'.$post_type->name] == 'TRUE') {
					$post_ids = get_posts(array(
				        'post_type' => $post_type->name,
				        'post_status'   => 'any',
				        'posts_per_page'=> -1,
				        'fields'        => 'ids'
				    ));
				    foreach ($post_ids as $post_id) {
				    	update_post_meta( $post_id, '_listbuilder_plugin_active', 'default');
				    }
				}
				if($_POST['clb_pr_'.$post_type->name] == 'TRUE' && $_POST['clb_pt_'.$post_type->name] != 'TRUE') {
					$post_ids = get_posts(array(
				        'post_type' => $post_type->name,
				        'post_status'   => 'any',
				        'posts_per_page'=> -1,
				        'fields'        => 'ids'
				    ));
				    foreach ($post_ids as $post_id) {
				    	update_post_meta( $post_id, '_listbuilder_plugin_active', '');
				    }
				}
			}

			

			

			// License Promo Url

			$licensePromoUrl = $this->getListbuilderPromoUrl();

			

			if ( strlen(trim($aOptions['klicktippCheckboxText'])) == 0 ) {

				$aOptions['klicktippCheckboxText'] = __('Newsletter abonnieren', 'comment-list-builder');

			}

			

			// set privacy url to default if it isn't set

			if (strlen($aOptions['listbuilder_privacy_url']) == 0) {

				$aOptions['listbuilder_privacy_url'] = 'http://www.klick-tipp.com/datenschutz/15194';

			}

			

			// Plugin settings form

			echo $this->pluginMessage($this->getAdminOptions());

			echo $message;

			$this->adminTabBar();



			$licenseEmail = (isset($aOptions['listbuilder_license_email'])) ? $aOptions['listbuilder_license_email'] : '';

			$licenseKey = (isset($aOptions['listbuilder_license_key'])) ? $aOptions['listbuilder_license_key'] : '';



			$no_licence = $this->checkAct($licenseEmail, $licenseKey);

			

			include(plugin_dir_path(__FILE__) . '../view/admin-settings.phtml');

			

			return '';

		}



		public function clb_add_kt_lb_meta_box_settings($post_type, $post) {
			if($post_type != 'shop_order') {
				if(post_type_supports( $post_type, 'comments' )) {
					add_meta_box("kt_listbuilding_box", __('Beitrags-Einstellungen für das Wordpress Comments List Building Plugin', 'comment-list-builder'), array($this, 'html_for_kl_meta_box_settings'), $post_type, "normal", "high", null);
				}
			}
		}

		public function clb_add_kt_lb_posts_columns( $columns, $post_type ) {
			if(post_type_supports( $post_type, 'comments' )) {
				$columns[ 'listbuilder-options' ] = __('Plugin aktivieren', 'comment-list-builder');
			}
			return $columns;
		}

		public function clb_add_kt_lb_pages_columns( $columns ) {
			$columns[ 'listbuilder-options' ] = __('Plugin aktivieren', 'comment-list-builder');
			return $columns;
		}

		public function clb_add_kt_lb_manage_posts_custom_column( $column_name, $post_id ) {
			$data = array();
			$options = $this->getAdminOptions();
			$alistbuilder_plugin_active = get_post_meta($post_id, '_listbuilder_plugin_active', null);
		    
			$defaults = $options;
			$defaults['klicktippAdditionalText'] = preg_replace('~>\s+<~', '><', $options['klicktippAdditionalText']);
			$defaults['autoresponderCode'] = preg_replace('~>\s+<~', '><', $options['autoresponderCode']);
			$defaults['autoresponderHidden'] = preg_replace('~>\s+<~', '><', $options['autoresponderHidden']);
		    $data['default'] = json_encode($defaults, JSON_HEX_QUOT | JSON_HEX_TAG);
		    if(empty($alistbuilder_plugin_active)){
		        $data['listbuilder_plugin_active'] = 'default';
		    } else {
		        $data['listbuilder_plugin_active'] = $alistbuilder_plugin_active[0];
		    }
		    $data['klicktipp_additional_text'] = preg_replace('~>\s+<~', '><', get_post_meta($post_id, '_klicktipp_additional_text', true));
		    $data['klicktipp_checkbox_text'] = get_post_meta( $post_id, '_klicktipp_checkbox_text', true );
		    $data['klicktipp_checkbox_checked'] = get_post_meta( $post_id, '_klicktipp_checkbox_checked', true );
		    $data['klicktipp_add_subscriber_without_release'] = get_post_meta( $post_id, '_klicktipp_add_subscriber_without_release', true );
		    $data['listbuilder_privacy_url'] = get_post_meta( $post_id, '_listbuilder_privacy_url', true );
		    $data['klicktipp_optin_id'] = get_post_meta( $post_id, '_klicktipp_optin_id', true );
		    $data['klicktipp_optin_name'] = get_post_meta( $post_id, '_klicktipp_optin_name', true );
		    $data['klicktipp_tag'] = get_post_meta( $post_id, '_klicktipp_tag', true );
		    $data['klicktipp_tag_id'] = get_post_meta( $post_id, '_klicktipp_tag_id', true ); 
		    $data['autoresponder_code'] = preg_replace('~>\s+<~', '><', get_post_meta( $post_id, '_autoresponder_code', true )); 
		    $data['autoresponder_url'] = get_post_meta( $post_id, '_autoresponder_url', true ); 
		    $data['autoresponder_name'] = get_post_meta( $post_id, '_autoresponder_name', true);
		    $data['autoresponder_email'] = get_post_meta( $post_id, '_autoresponder_email', true); 
		    $data['autoresponder_hidden'] = preg_replace('~>\s+<~', '><', get_post_meta( $post_id, '_autoresponder_hidden', true));
		    if(empty($data['klicktipp_additional_text']) && empty($data['klicktipp_checkbox_text']) && empty($data['klicktipp_checkbox_checked']) && empty($data['klicktipp_add_subscriber_without_release']) && empty($data['listbuilder_privacy_url']) && empty($data['klicktipp_optin_id']) && empty($data['klicktipp_optin_name']) && empty($data['klicktipp_tag']) && empty($data['klicktipp_tag_id']) && empty($data['autoresponder_code']) && empty($data['autoresponder_url']) && empty($data['autoresponder_name']) && empty($data['autoresponder_email']) && empty($data['autoresponder_hidden'])) {
		            $data['klicktipp_additional_text'] = preg_replace('~>\s+<~', '><', $options['klicktippAdditionalText']);
		            $data['klicktipp_checkbox_text'] = $options['klicktippCheckboxText'];
		            $data['klicktipp_checkbox_checked'] = $options['klicktippCheckboxChecked'];
		            $data['klicktipp_add_subscriber_without_release'] = $options['klicktippAddSubscriberWithoutRelease'];
		            $data['listbuilder_privacy_url'] = $options['listbuilder_privacy_url'];
		            $data['klicktipp_optin_id'] = $options['klicktippOptInID'];
		            $data['klicktipp_optin_name'] = $options['klicktippOptInName'];
		            $data['klicktipp_tag'] = $options['klicktippTag'];
		            $data['klicktipp_tag_id'] = $options['klicktippTagID'];
		            $data['autoresponder_code'] = preg_replace('~>\s+<~', '><', $options['autoresponderCode']);
		            $data['autoresponder_url'] = $options['autoresponderUrl'];
		            $data['autoresponder_name'] = $options['autoresponderName'];
		            $data['autoresponder_email'] = $options['autoresponderEmail'];
		            $data['autoresponder_hidden'] = preg_replace('~>\s+<~', '><', $options['autoresponderHidden']);
		    }
		    
			switch( $column_name ) {
		      	case 'listbuilder-options':
		         	echo '<div id="listbuilder-options-' . $post_id . '">' . json_encode($data, JSON_HEX_QUOT | JSON_HEX_TAG) . '</div>';
		         	break;
		   }
		}

		public function clb_defaults_html_footer() {
			$data = array();
			$options = $this->getAdminOptions();
			$alistbuilder_plugin_active = get_post_meta($post_id, '_listbuilder_plugin_active', null);

			$defaults = $options;
			$defaults['klicktippAdditionalText'] = preg_replace('~>\s+<~', '><', $options['klicktippAdditionalText']);
			$defaults['autoresponderCode'] = preg_replace('~>\s+<~', '><', $options['autoresponderCode']);
			$defaults['autoresponderHidden'] = preg_replace('~>\s+<~', '><', $options['autoresponderHidden']);
			$defaults['klicktippCheckboxText'] = $options['klicktippCheckboxText'];
			$defaults['klicktippCheckboxChecked'] = $options['klicktippCheckboxChecked'];
			$defaults['klicktippAddSubscriberWithoutRelease'] = $options['klicktippAddSubscriberWithoutRelease'];
			$defaults['listbuilder_privacy_url'] = $options['listbuilder_privacy_url'];
			$defaults['klicktippOptInID'] = $options['klicktippOptInID'];
			$defaults['klicktippOptInName'] = $options['klicktippOptInName'];
			$defaults['klicktippTag'] = $options['klicktippTag'];
			$defaults['klicktippTagID'] = $options['klicktippTagID'];
			$defaults['autoresponderUrl'] = $options['autoresponderUrl'];
			$defaults['autoresponderName'] = $options['autoresponderName'];
			$defaults['autoresponderEmail'] = $options['autoresponderEmail'];

			$data['default'] = json_encode($defaults, JSON_HEX_QUOT | JSON_HEX_TAG);
			echo '<div id="listbuilder-options-bulk" style="display:none;">'. json_encode($data, JSON_HEX_QUOT | JSON_HEX_TAG) . '</div>';
		}

		public function clb_add_kt_lb_quick_edit_custom_box($column_name, $post_type) {
			$options = $this->getAdminOptions();

			$licenseEmail = (isset($options['listbuilder_license_email'])) ? $options['listbuilder_license_email'] : '';

			$licenseKey = (isset($options['listbuilder_license_key'])) ? $options['listbuilder_license_key'] : '';

			$no_licence = $this->checkAct($licenseEmail, $licenseKey);

			if(post_type_supports( $post_type, 'comments' )) {
				if($column_name == 'listbuilder-options') {
					include(plugin_dir_path(__FILE__) . '../view/quick-edit-settings.phtml');
				}
			}
		}

		public function clb_add_kt_lb_bulk_edit_custom_box($column_name, $post_type) {
			$options = $this->getAdminOptions();

			$licenseEmail = (isset($options['listbuilder_license_email'])) ? $options['listbuilder_license_email'] : '';

			$licenseKey = (isset($options['listbuilder_license_key'])) ? $options['listbuilder_license_key'] : '';

			$no_licence = $this->checkAct($licenseEmail, $licenseKey);

			if(post_type_supports( $post_type, 'comments' )) {
				if($column_name == 'listbuilder-options') {
					include(plugin_dir_path(__FILE__) . '../view/bulk-edit-settings.phtml');
				}
			}
		}

		public function clb_save_bulk_edit() {
			if($_POST['listbuilder-plugin-active'] != 'nochange') {
				$post_ids = ( isset( $_POST[ 'post_ids' ] ) && !empty( $_POST[ 'post_ids' ] ) ) ? $_POST[ 'post_ids' ] : array();
			    foreach( $post_ids as $post_id ) {
			        $this->clb_save_kt_lb_meta_boxes_data( $post_id );
			    }
			}
		}

		public function html_for_kl_meta_box_settings($post) {

			$options = $this->getAdminOptions();

			$licenseEmail = (isset($options['listbuilder_license_email'])) ? $options['listbuilder_license_email'] : '';

			$licenseKey = (isset($options['listbuilder_license_key'])) ? $options['listbuilder_license_key'] : '';



			$no_licence = $this->checkAct($licenseEmail, $licenseKey);



			include(plugin_dir_path(__FILE__) . '../view/meta-box-settings.phtml');

		}



		public function clb_save_kt_lb_meta_boxes_data($post_id) {			

			$checkoptions = $this->getAdminOptions();
			$checkoptions['listbuilder-plugin-active'] = $_POST['listbuilder-plugin-active'];
			$checkoptions['autoresponderCode'] = $_POST['ar_code'];
			$msg = $this->pluginMessage($checkoptions);
			if($msg) {
				 set_transient( "clb_save_post_error_msg_$post_id", $msg, 60 );
			}

			$options = $this->getAdminOptions();

			if ($options['klicktippAccessValidationStatus'] == TRUE) {
				$oConnectorTag = $this->connectToKlickTipp();
			}

			$licenseEmail = (isset($options['listbuilder_license_email'])) ? $options['listbuilder_license_email'] : '';

			$licenseKey = (isset($options['listbuilder_license_key'])) ? $options['listbuilder_license_key'] : '';

			

			if (!$this->checkAct($licenseEmail, $licenseKey) && $_POST['action'] != 'optimizepress-live-editor-save' && $_POST['action'] != '') {

				if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ){
					return;

				}

				if ( ! current_user_can( 'edit_post', $post_id ) ){
					return;

				}		


				update_post_meta( $post_id, '_listbuilder_plugin_active', isset($_POST['listbuilder-plugin-active']) ? $_POST['listbuilder-plugin-active'] : "" );

				update_post_meta( $post_id, '_klicktipp_additional_text', isset($_POST['klicktippAdditionalText']) ? $_POST['klicktippAdditionalText'] : "" );

				update_post_meta( $post_id, '_klicktipp_checkbox_text', isset($_POST['klicktippCheckboxText']) ? sanitize_text_field( $_POST['klicktippCheckboxText'] ) : "" );

				update_post_meta( $post_id, '_klicktipp_checkbox_checked', isset($_POST['klicktippCheckboxChecked']) ? (($_POST['klicktippCheckboxChecked'] == 'TRUE') ? "TRUE" : "") : "" );

				update_post_meta( $post_id, '_klicktipp_add_subscriber_without_release', isset($_POST['klicktippAddSubscriberWithoutRelease']) ? (($_POST['klicktippAddSubscriberWithoutRelease'] == 'TRUE') ? "TRUE" : "" ) : "");

				update_post_meta( $post_id, '_listbuilder_privacy_url', isset($_POST['privacy-url']) ? sanitize_text_field( $_POST['privacy-url'] ) : "" );

				update_post_meta( $post_id, '_klicktipp_optin_id', isset($_POST['klicktippOptInID']) ? sanitize_text_field( $_POST['klicktippOptInID'] ) : "" );

				if ($options['klicktippAccessValidationStatus'] == TRUE) {

					$name = $oConnectorTag->subscription_process_get(isset($_POST['klicktippOptInID']) ? $_POST['klicktippOptInID'] : false);

					if($name != false) {

						update_post_meta( $post_id, '_klicktipp_optin_name', $name->name);

					}

					update_post_meta( $post_id, '_klicktipp_tag', isset($_POST['klicktippTag']) ? sanitize_text_field( $_POST['klicktippTag'] ) : "" );

					if(isset($_POST['klicktippTag'])) {
						$aTags = $oConnectorTag->tag_index();

						$iTagID = array_search($_POST['klicktippTag'],$aTags);

						if(empty($iTagID)) {

							$iTagID = $oConnectorTag->tag_create($_POST['klicktippTag'], $text = 'Added by WordPress Listbuilder');

						}
						update_post_meta( $post_id, '_klicktipp_tag_id', $iTagID );
					}	
				}

				update_post_meta( $post_id, '_autoresponder_code', isset($_POST['ar_code']) ? $_POST['ar_code'] : "" );

				update_post_meta( $post_id, '_autoresponder_url', isset($_POST['ar_url']) ? sanitize_text_field( $_POST['ar_url'] ) : "" );

				update_post_meta( $post_id, '_autoresponder_name', isset($_POST['ar_name']) ? sanitize_text_field( $_POST['ar_name'] ) : "" );

				update_post_meta( $post_id, '_autoresponder_email', isset($_POST['ar_email']) ? sanitize_text_field( $_POST['ar_email'] ) : "" );

				update_post_meta( $post_id, '_autoresponder_hidden', isset($_POST['ar_hidden']) ? $_POST['ar_hidden'] : "" );

				
				if ($options['klicktippAccessValidationStatus'] == TRUE) {
					$oConnectorTag->logout();
				}

			}

		}

		

		

		private function adminPageAutoresponder() {

			

			$message = '';

			

			$aOptions = $this->getAdminOptions();

			

			if (isset($_POST)) {

				if (array_key_exists('submit-save', $_POST)) {

					// save autoresponder data

					if (array_key_exists('ar_code', $_POST)) {

						$aOptions['autoresponderCode'] = $_POST['ar_code'];

					}

					if (array_key_exists('ar_url', $_POST)) {

						$aOptions['autoresponderUrl'] = $_POST['ar_url'];

					}

					if (array_key_exists('ar_name', $_POST)) {

						$aOptions['autoresponderName'] = $_POST['ar_name'];

					}

					if (array_key_exists('ar_email', $_POST)) {

						$aOptions['autoresponderEmail'] = $_POST['ar_email'];

					}

					if (array_key_exists('ar_hidden', $_POST)) {

						$aOptions['autoresponderHidden'] = $_POST['ar_hidden'];

					}

					

					update_option($this->adminOptionsName, $aOptions);

					

					$message = '<div class="updated"><p><strong>' . __('Hinweis: Die Einstellungen wurden erfolgreich gespeichert.', 'comment-list-builder') . '</strong></p></div>';

					

				} else if (array_key_exists('submit-reset', $_POST)) {

					// reset autoresponder data

					$aOptions['autoresponderCode'] = '';

					$aOptions['autoresponderUrl'] = '';

					$aOptions['autoresponderName'] = '';

					$aOptions['autoresponderEmail'] = '';

					$aOptions['autoresponderHidden'] = '';

					

					update_option($this->adminOptionsName, $aOptions);

					

					$message = '<div class="updated"><p><strong>' . __('Hinweis: Die Einstellungen wurden erfolgreich gespeichert.', 'comment-list-builder') . '</strong></p></div>';

					

				}

			}

			

			echo $this->pluginMessage($this->getAdminOptions());

			echo $message;

			$this->adminTabBar();

			

			include(plugin_dir_path(__FILE__) . '../view/admin-autoresponder.phtml');

			

		}

		

		

		/**

         * Get api select array

         *

         * @access public

         * @return array

         */

        public function getApiSelect() {

			$aOptions = $this->getAdminOptions();

			

            $apiValues = array(

                'de' => (object) array(

                    'name' => __('Deutschland', 'comment-list-builder')

                ),

                'br' => (object) array(

                    'name' => __('Brasilien', 'comment-list-builder')

                )

            );



            $apiValue = $aOptions['klicktippApi'];

            if (array_key_exists($apiValue, $apiValues)) {

                $apiValues[$apiValue]->selected = 'selected="selected"';

            }



            return $apiValues;

        }

		

		private function adminPageKlicktipp() {

			$message = "";

			$aOptions = $this->getAdminOptions();

	

			if(!empty($_POST['update_klicktippListbuilding'])) {

				

				// save klick-tipp api

				if (isset($_POST['account-api'])) {

					$apiValue = trim($_POST['account-api']);

					$apiValues = $this->getApiSelect();

					if (array_key_exists($apiValue, $apiValues)) {

						$aOptions['klicktippApi'] = $apiValue;

						

						update_option($this->adminOptionsName, $aOptions);

					}

				}

				

				// save temporary the old klicktipp user data

				$tempUser = $aOptions['klicktippUser'];

				$tempPassword = $aOptions['klicktippPassword'];

				

				if($_POST['klicktippUser']) {$aOptions['klicktippUser'] = trim($_POST['klicktippUser']);} else { unset($aOptions['klicktippUser'] );}

				if($_POST['klicktippPassword']) {$aOptions['klicktippPassword'] = trim($_POST['klicktippPassword']);} else { unset($aOptions['klicktippPassword'] );}

				if($_POST['klicktippOptInID']) {$aOptions['klicktippOptInID'] = trim($_POST['klicktippOptInID']);} else { unset($aOptions['klicktippOptInID'] );}

				if($_POST['klicktippTag']) {$aOptions['klicktippTag'] = trim($_POST['klicktippTag']);} else { unset($aOptions['klicktippTag'] );}

				unset($aOptions['klicktippTagID']); // delete Tag ID to force getting the ID. Because tag can change and than ID will be wrong.

				unset($aOptions['klicktippOptInName']); // delete OptIn Name to force getting the Name. Because ID can change and than Name will be wrong.

	

				$aOptions['klicktippAccessValidationStatus']=FALSE; // force validation check

				$aOptions['klicktippAccessValidationNextCheck'] = current_time('timestamp',0); // set next validation check to now

				update_option($this->adminOptionsName, $aOptions);

				

			  if( !empty($_POST['klicktippUser']) && !empty($_POST['klicktippPassword']) ) {

				  update_option($this->adminOptionsName, $aOptions);

				  $message = '<div class="updated"><p><strong>' . __('Hinweis: Die Einstellungen wurden erfolgreich gespeichert.', 'comment-list-builder') . '</strong></p></div>';

				  $aLoginStatus = $this->KTLoginValidation(); // force validation of new access details

	

				  $aOptions = $this->getAdminOptions(); // reload options

	

				  if($aLoginStatus['status']!==TRUE) {

					// login failed, don't save username and password

					// reset temporary klicktipp data

					$aOptions['klicktippUser'] = $tempUser;

					$aOptions['klicktippPassword'] = $tempPassword;

					update_option($this->adminOptionsName, $aOptions);

					

					  $message .= '<div class="error"><p>'.$aLoginStatus['text'].'</p></div>';

				  } else{

					// login successfull

					if($aOptions['klicktippOptInID'] && !$aOptions['klicktippOptInName'])  // OptInID didn't return a process describtion

						$message .= '<div class="error"><p>' . __('OptIn ID scheint bei Klick-Tipp nicht zu existieren. Bitte prüfen!', 'comment-list-builder') . '</p></div>';

				  }

	

			  } else {

				  $message = '<div class="error"><p><strong>' . __('Fehler: F&uuml;llen Sie bitte alle Felder korrekt aus.', 'comment-list-builder') . '</strong></p></div>';

			  }

			  

			}

			  

			  

			// License Promo Url

			$splittestUrl = "http://api.splittest-club.com/splittest.php?test=15036&format=clean&ip=" . $_SERVER["REMOTE_ADDR"];

			if (function_exists('curl_version')) {

				$curl = curl_init();

				curl_setopt($curl, CURLOPT_URL, $splittestUrl);

				curl_setopt($curl,CURLOPT_USERAGENT,'Mozilla/5.0 (Windows NT 6.1; WOW64) AppleWebKit/537.17 (KHTML, like Gecko) Chrome/24.0.1312.52 Safari/537.17');

				curl_setopt($curl, CURLOPT_AUTOREFERER, true); 

				curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);

				curl_setopt($curl, CURLOPT_FOLLOWLOCATION, 1);

				curl_setopt($curl, CURLOPT_VERBOSE, 1);

				$promoProductId = curl_exec($curl);

				curl_close($curl);

				

			} else if (file_get_contents(__FILE__) && ini_get('allow_url_fopen')) {

				$promoProductId = file_get_contents($splittestUrl);

				

			}

			

			if (defined('COMMENT_LIST_BUILDER_AFFILIATE')) {

				$licensePromoUrl = 'http://promo.' . COMMENT_LIST_BUILDER_AFFILIATE . '.' . $promoProductId . '.digistore24.com/';

			} else {

				$licensePromoUrl = 'http://promo.tobias-conrad.' . $promoProductId . '.digistore24.com/';

			}

			if (defined('COMMENT_LIST_BUILDER_CAMPAIGNKEY')) {

				$licensePromoUrl .= COMMENT_LIST_BUILDER_CAMPAIGNKEY;

			}

			

			$selectApiValues = $this->getApiSelect();

			$ktLoginStatus = $this->KTCheckLogin();

			

			

			// Plugin settings form

			echo $this->pluginMessage($this->getAdminOptions());

			echo $message;

			$this->adminTabBar();

			

			include(plugin_dir_path(__FILE__) . '../view/admin-klicktipp.phtml');

			

		}

		

        private function checkKlickTipp($data, $returnArray = false) {

		  $response = "";

            if (function_exists('curl_version')) {

                $ch = curl_init(base64_decode('aHR0cDovL3NhbGVzd29uZGVyLmJpei9rbGlja3RpcC1jYXBpL2NoZWNrX2xpY2Vuc2Vfd3AucGhw'));

                curl_setopt($ch, CURLOPT_POST, true);

                curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

                $response = curl_exec($ch);

                curl_error($ch);

			}

			if (!empty($response)) {

               $jsonObj = json_decode($response);

                if (is_object($jsonObj)) {

					if (isset($jsonObj->end_date)) {

						$this->klickTippP = $jsonObj->end_date;

					} else {

						$this->klickTippP = null;

					}

					if ($returnArray === true) {

						$resArray = array(

							'result' => ($jsonObj->check == 1) ? false : true

						);

						if (isset($jsonObj->current_url)) {

							$resArray['current_url'] = $jsonObj->current_url;

						}

						return $resArray;

					} else {

						if ($jsonObj->check == 1) {

							return false;

						}

					}

                }

            }

			return true;

        }

		

		private function checkAct($email, $key) {

			if (is_null($this->klickTippL)) {

				$datastring = 'product=listbuilder&license_email=' . $email . '&license_key=' . $key . '&site_url=' . site_url();

				$this->klickTippL = $this->checkKlickTipp($datastring);

			}

			return 0; //$this->klickTippL;

		}

		

		private function changeDomain($licenseEmail, $licenseKey) {

			$data = 'product=listbuilder&license_email=' . $licenseEmail . '&license_key=' . $licenseKey . '&site_url=' . site_url();

			

			if (function_exists('curl_version')) {

                $ch = curl_init('http://saleswonder.biz/klicktip-capi/change_domain.php');

                curl_setopt($ch, CURLOPT_POST, true);

                curl_setopt($ch, CURLOPT_POSTFIELDS, $data);

                curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

                $response = curl_exec($ch);

                curl_error($ch);



               $jsonObj = json_decode($response);

                if (is_object($jsonObj)) {

                    if ($jsonObj->success == 1) {

						return true;

					}

                }

            }

			return false;

		}

		

		private function adminPageLicense() {

			$message = "";

			$aOptions = $this->getAdminOptions();

			

			if (!empty($_POST['listbuilder_change_domain'])) {

				

				$licenseEmail = trim($_POST['license-email']);

				$licenseKey = trim($_POST['license-key']);

				

				if ($licenseEmail == '') {

					$message = '<div class="error"><p><strong>' . __('Fehler: Bitte gebe deine E-Mail Adresse ein', 'comment-list-builder') . '</strong></p></div>';

				} else if ($licenseKey == '') {

					$message = '<div class="error"><p><strong>' . __('Fehler: Bitte gebe deinen Lizenzschlüssel ein', 'comment-list-builder') . '</strong></p></div>';

				} else {

					$res = $this->changeDomain($licenseEmail, $licenseKey);

					if ($res) {

						$datastring = 'product=listbuilder&license_email=' . $licenseEmail . '&license_key=' . $licenseKey . '&site_url=' . site_url();

						if (!$this->checkKlickTipp($datastring)) {

							$aOptions['listbuilder_license_email'] = $licenseEmail;

							$aOptions['listbuilder_license_key'] = $licenseKey;

							unset($aOptions['listbuilder-dismiss-trial-notice']);

							

							update_option($this->adminOptionsName, $aOptions);

							

							$message = '<div class="updated"><p><strong>' . __('Hinweis: Die Lizenz wurde auf dieser Domain umgezogen.', 'comment-list-builder') . '</strong></p></div>';

						} else {

							$message = '<div class="error"><p><strong>' . __('Fehler: Die Lizenz konnte nicht auf dieser Domain umgezogen werden.', 'comment-list-builder') . '</strong></p></div>';

						}

						

					} else {

						$message = '<div class="error"><p><strong>' . __('Fehler: Die Lizenz konnte nicht auf die neue Domain umgezogen werden.', 'comment-list-builder') . '</strong></p></div>';

					}

				}

				

				

			}

			

			if(!empty($_POST['listbuilder_save_license'])) {

				

				$licenseEmail = trim($_POST['license-email']);

				$licenseKey = trim($_POST['license-key']);

				

				if ($licenseEmail == '') {

					$message = '<div class="error"><p><strong>' . __('Fehler: Bitte gebe deine E-Mail Adresse ein', 'comment-list-builder') . '</strong></p></div>';

				} else if ($licenseKey == '') {

					$message = '<div class="error"><p><strong>' . __('Fehler: Bitte gebe deinen Lizenzschlüssel ein', 'comment-list-builder') . '</strong></p></div>';

				} else {

					$datastring = 'product=listbuilder&license_email=' . $licenseEmail . '&license_key=' . $licenseKey . '&site_url=' . site_url();

					$checkKlickTipp = $this->checkKlickTipp($datastring, true);

					if (!$checkKlickTipp['result']) {

						$aOptions['listbuilder_license_email'] = $licenseEmail;

						$aOptions['listbuilder_license_key'] = $licenseKey;

						

						$message = '<div class="updated"><p><strong>' . __('Hinweis: Die Einstellungen wurden erfolgreich gespeichert.', 'comment-list-builder') . '</strong></p></div>';

						

					} else {

						if (empty($checkKlickTipp['current_url'])) {

							$message = '<div class="error"><p><strong>' . __('Fehler: Die angegebenen Lizenzdaten sind falsch', 'comment-list-builder') . '</strong></p></div>';

						} else {

							$message = '<div class="error">' . sprintf(__('<p>Lizenz wurde auf der Domain "%s" aktiviert.</p><p>Zum Umziehen der Lizenz bitte Lizenzdaten eingeben und "Lizenz auf dieser Domain aktivieren" klicken.</p>', 'comment-list-builder'), $checkKlickTipp['current_url']) . '</div>';

						}

					}

				}

				

				

				if(isset($_POST['license-delete'])) {

					// delete license key

					unset($aOptions['listbuilder_license_email']);

					unset($aOptions['listbuilder_license_key']);

					$message .= '<div class="updated"><p><strong>' . __('Hinweis: Lizenz wurde erfolgreich gelöscht.', 'comment-list-builder') . '</strong></p></div>';

				}

				

				

				update_option($this->adminOptionsName, $aOptions);

				

			}

			

			// Plugin settings form

			echo $this->pluginMessage($this->getAdminOptions());

			echo $message;

			$this->adminTabBar();

			

			include(plugin_dir_path(__FILE__) . '../view/admin-license.phtml');

			

		}

		

		public function clb_onAdminInit() {

			add_options_page($this->sPluginName, $this->sPluginName, 'manage_options', 'listbuilding', array(&$this, 'klicktippListbuilding_admin'));

		}

		

		private function pluginMessage($aOptions) {

			if ($aOptions['listbuilder-plugin-active'] == 'klicktipp') {

				if ($aOptions['klicktippAccessValidationStatus'] == FALSE) {

					return '<div class="error"><p>' . __('Ihre KT Zugangsdaten sind nicht gültig.', 'comment-list-builder') . '</p></div>';

				}

			} else if ($aOptions['listbuilder-plugin-active'] == 'autoresponder') {

				if (empty($aOptions['autoresponderCode'])) {

					return '<div class="error"><p>' . __('Daten im Autoresponder fehlen.', 'comment-list-builder') . '</p></div>';

				}

			}

		}
		

		public function clb_AdminNotices() {
			global $post;
			  if ( false !== ( $msg = get_transient( "clb_save_post_error_msg_{$post->ID}" ) ) && $msg) {
			    delete_transient( "clb_save_post_error_msg_{$post->ID}" );
			    echo $msg;
			  }

			if(array_key_exists('page', $_GET)) {
				if ($_GET["page"]!='listbuilding') {

					$aOptions = $this->getAdminOptions();

					

					$licenseEmail = (isset($aOptions['listbuilder_license_email'])) ? $aOptions['listbuilder_license_email'] : '';

					$licenseKey = (isset($aOptions['listbuilder_license_key'])) ? $aOptions['listbuilder_license_key'] : '';

					

					//if ($this->checkAct($licenseEmail, $licenseKey)) {

					//	echo '<div class="error">' . sprintf(__('<p>Deine Testversion des Comment List-Builder ist abgelaufen! Es werden keine E-Mail Adressen beim Kommentieren mehr gesammelt!</p><p>Erneuere Jetzt hier deine Lizenz und sammle weiter E-Mail Adressen beim Kommentieren ein! <a href="%s" target="_blank">Erneuere Jetzt hier deine Lizenz</a></p>', 'comment-list-builder'), $this->getListbuilderPromoUrl()) . '</div>';

					if ($this->checkPluginVersion() == 0) {

						// plugin is inactive

						echo '<div class="error">' . sprintf(__('<p><b>'.$this->sPluginName.' ist veraltet</b></p><p>Um das Plugin weiterhin zu benutzen, lade bitte eine neue Version des Plugins herunter. <a href="%s" target="_blank">Plugin Download</a></p>', 'comment-list-builder'), $this->getListbuilderPromoUrl()) . '</div>';

						

					} else if ($this->checkPluginVersion() == 2) {

						// plugin will be inactive

						echo '<div class="error">' . sprintf(__('<p><b>'.$this->sPluginName.' ist bald veraltet</b></p><p>Um das Plugin weiterhin uneingeschränkt zu benutzen, lade bitte eine neue Version des Plugins herunter. <a href="%s" target="_blank">Plugin Download</a></p>', 'comment-list-builder'), $this->getListbuilderPromoUrl()) . '</div>';

						

					} else {

						

						$sError = '';

						

						if ($aOptions['listbuilder-plugin-active'] == 'klicktipp') {

							$aLoginStatus = $this->KTCheckLogin();

							if ($aLoginStatus['status']==FALSE) {

								if(!$sError) $sError = '<div class="error"><p><b>'.$this->sPluginName.'</b>&nbsp;';

								$sError .= '<p>'.$aLoginStatus['text'].'</p>';

							}

						} else if ($aOptions['listbuilder-plugin-active'] == 'autoresponder') {

							if (empty($aOptions['autoresponderCode'])) {

								echo '<div class="error">' . sprintf(__('<p><b>%s</b></p><p>Daten im Autoresponder fehlen. <a href="options-general.php?page=listbuilding&tab=autoresponder">Einstellungen prüfen</a></p>', 'comment-list-builder'), $this->sPluginName) . '</div>';

							}

						} else {

							echo '<div class="error">' . __('<p><b>Danke für Deine Nutzung des WP Comment List Builder Plugins.</b></p><p>Bitte nehme hier die ersten <a href="options-general.php?page=listbuilding">Einstellungen vor</a>, damit den Besuchern etwas angezeigt wird.</p>', 'comment-list-builder') . '</div>';

						}

						

						

						if ($sError) echo $sError.'</div>';

					}

				}
			}

		}

		

		/**

		* Returns Login Status

		* 

		* @param none

		* @return TRUE on success

		*/

		private function KTCheckLogin() {

			$aOptions = $this->getAdminOptions();

			

			if($aOptions['klicktippAccessValidationStatus']==TRUE) { // if status is true

				if($aOptions['klicktippAccessValidationNextCheck']<=current_time('timestamp',0)) {  // if period of status valid is no longer valid

					return $this->KTLoginValidation(); // validation of access details

				} else {

					return array('status' => TRUE,

								 'text' => sprintf(__('Zugangsdaten sind gültig (nächste Prüfung erfolgt am %s).', 'comment-list-builder'), date('d.m.Y H:i', $aOptions['klicktippAccessValidationNextCheck'])));

				}

			} else {

				if($aOptions['klicktippAccessValidationNextCheck']<=current_time('timestamp',0)) {  // if period of status not valid is no longer valid

					return $this->KTLoginValidation(); // validation of access details

				} else {

					return array('status' => FALSE,

								 'text' => __('Keine Verbindung zum Klick-Tipp Server oder Zugangsdaten sind ungültig.', 'comment-list-builder'));

				}

			}

		}

	

		private function KTLoginValidation() {

			$aOptions = $this->getAdminOptions();

			

			$oKTConnector = $this->connectToKlickTipp();

			

			if ($this->klickTippIsLoggedIn == true) {

				$aOptions['klicktippAccessValidationStatus']=TRUE;

				$aOptions['klicktippAccessValidationNextCheck'] = strtotime('+24 hours', current_time('timestamp',0)); // next validation check in 24h

				update_option($this->adminOptionsName, $aOptions);

				$sStatus = TRUE;

				$sStatusText = 'Zugangsdaten sind gültig (nächste Prüfung erfolgt am '.date('d.m.Y H:i', $aOptions['klicktippAccessValidationNextCheck']).').';

				if($aOptions['klicktippTag'] && !$aOptions['klicktippTagID']) $this->KTGetTagID($aOptions); // get the tag ID if tag is given

			} else {

				$aOptions['klicktippAccessValidationStatus']=FALSE;

				$aOptions['klicktippAccessValidationNextCheck'] = strtotime('+15 minutes', current_time('timestamp',0)); // next validation check in 12h

				

				update_option($this->adminOptionsName, $aOptions);

				$sStatus = FALSE;

				$sStatusText = __('Zugangsdaten sind nicht gültig', 'comment-list-builder') . ' ('.$this->connectToKlickTipp()->get_last_error().').';

			}

	

			return array('status' => $sStatus,

						 'text' => $sStatusText);

		}

		 

		private function KTSendSubscriber($post_id, $sSubscriberEmail='', $author) {

			if ( empty($sSubscriberEmail) ) {

			  return;

			}



    		$aOptionsPost = $this->buildPostOptions($post_id);

			$aAdminOptions = $this->getAdminOptions();

			$aOptions = array_merge($aAdminOptions, $aOptionsPost);

			

			$oConnector = $this->connectToKlickTipp();

			

			if($aOptions['klicktippTag'] && !$aOptions['klicktippTagID']) {

				$this->KTGetTagID($aOptions); // get the tag ID if tag is given

				// reload options after adding TagID to option set

				$aOptionsPost = $this->buildPostOptions($post_id);

				$aAdminOptions = $this->getAdminOptions();

				$aOptions = array_merge($aAdminOptions, $aOptionsPost);				 

			}

			$names = explode(" ", $author);

			$subscriber = $oConnector->subscribe($sSubscriberEmail,$aOptions['klicktippOptInID'], $aOptions['klicktippTagID'], array (

			  'fieldFirstName' => $names[0],

			  'fieldLastName' => $names[1],

			));

		}

		  

		private function KTGetTagID($aOptions) {			

			$oConnectorTag = $this->connectToKlickTipp();

			$bLoggedIn = $this->klickTippIsLoggedIn;

		

			$aTags = $oConnectorTag->tag_index();

			$iTagID = array_search($aOptions['klicktippTag'],$aTags);

		

			if(empty($iTagID)) // if tag is not existing in KT tag list create it

				$iTagID = $oConnectorTag->tag_create($aOptions['klicktippTag'], $text = 'Added by WordPress Listbuilder');

	

			if(!empty($aOptions['klicktippOptInID']) && empty($aOptions['klicktippOptInName']))

				$aOptInName = $oConnectorTag->subscription_process_get($aOptions['klicktippOptInID']);

	

			$oConnectorTag->logout();

	

			if(!empty($iTagID)) $aOptions['klicktippTagID'] = $iTagID;

			if(!empty($aOptInName)) $aOptions['klicktippOptInName'] = $aOptInName->name;

			update_option($this->adminOptionsName, $aOptions);

	

			return;

		}



		private function buildPostOptions($post_id) {

			$post_options = array();



			$options = $this->getAdminOptions();			

			

			$licenseEmail = (isset($options['listbuilder_license_email'])) ? $options['listbuilder_license_email'] : '';

			$licenseKey = (isset($options['listbuilder_license_key'])) ? $options['listbuilder_license_key'] : '';

			$listbuilder_plugin_active = get_post_meta( $post_id, '_listbuilder_plugin_active', true );			

			if (!$this->checkAct($licenseEmail, $licenseKey) && $listbuilder_plugin_active != 'default') {


				$klicktipp_additional_text = get_post_meta($post_id, '_klicktipp_additional_text', true);

				$klicktipp_checkbox_text = get_post_meta($post_id, '_klicktipp_checkbox_text', true);

				$klicktipp_add_subscriber_without_release = get_post_meta($post_id, '_klicktipp_add_subscriber_without_release', true);

				$klicktipp_checkbox_checked = get_post_meta($post_id, '_klicktipp_checkbox_checked', true);

				$listbuilder_privacy_url = get_post_meta($post_id, '_listbuilder_privacy_url', true);

				$klicktipp_optin_id = get_post_meta( $post_id, '_klicktipp_optin_id', true );

				$klicktipp_optin_name = get_post_meta( $post_id, '_klicktipp_optin_name', true );

				$klicktipp_tag = get_post_meta( $post_id, '_klicktipp_tag', true );

				$klicktipp_tag_id = get_post_meta( $post_id, '_klicktipp_tag_id', true ); 

				$autoresponder_code = get_post_meta( $post_id, '_autoresponder_code', true ); 

				$autoresponder_url = get_post_meta( $post_id, '_autoresponder_url', true ); 

				$autoresponder_name = get_post_meta( $post_id, '_autoresponder_name', true);

				$autoresponder_email = get_post_meta( $post_id, '_autoresponder_email', true); 

				$autoresponder_hidden = get_post_meta( $post_id, '_autoresponder_hidden', true);

				
				$post_options['listbuilder-plugin-active'] = $listbuilder_plugin_active;

				$post_options['klicktippAdditionalText'] = $klicktipp_additional_text;

				$post_options['klicktippCheckboxText'] = $klicktipp_checkbox_text;

				$post_options['klicktippAddSubscriberWithoutRelease'] = $klicktipp_add_subscriber_without_release;

				$post_options['listbuilder-plugin-active'] = $listbuilder_plugin_active;

				$post_options['klicktippCheckboxChecked'] = $klicktipp_checkbox_checked;

				$post_options['listbuilder_privacy_url'] = $listbuilder_privacy_url;

				$post_options['klicktippOptInID'] = $klicktipp_optin_id;

				$post_options['klicktippOptInName'] = $klicktipp_optin_name;

				$post_options['klicktippTag'] = $klicktipp_tag;

				$post_options['klicktippTagID'] = $klicktipp_tag_id;

				$post_options['autoresponderCode'] = $autoresponder_code;

				$post_options['autoresponderUrl'] = $autoresponder_url;

				$post_options['autoresponderName'] = $autoresponder_name;

				$post_options['autoresponderEmail'] = $autoresponder_email;

				$post_options['autoresponderHidden'] = $autoresponder_hidden;

			}

			if(!array_filter($post_options)) {

				return array();

			} else {

				return $post_options;

			}

		}



		

		public function clb_on_comment_post($id) {

			$com_id = intval($id);

			$comment = get_comment( $com_id ); 

    		$comment_post_id = $comment->comment_post_ID ;



    		$aOptionsPost = $this->buildPostOptions($comment_post_id);

			$aAdminOptions = $this->getAdminOptions();

			$aOptions = array_merge($aAdminOptions, $aOptionsPost);

			

			if( strtolower($_POST['klicktippListbuilding_subscribe']) == "on" ) {

			  add_comment_meta( $com_id, 'klicktippListbuilding_active',  '1');

			}

			

			$sCommentStatus = wp_get_comment_status($com_id);		

			if($sCommentStatus=='approved' || $sCommentStatus=='approve' || $aOptions['klicktippAddSubscriberWithoutRelease']==TRUE) $this->clb_on_comment_approved($com_id, $sCommentStatus);

		}

		  

		public function clb_on_comment_approved($id, $status) {

			$comment_id = intval($id);

			$comment = get_comment( $comment_id ); 

    		$comment_post_id = $comment->comment_post_ID ;



    		$aOptionsPost = $this->buildPostOptions($comment_post_id);

			$aAdminOptions = $this->getAdminOptions();

			$aOptions = array_merge($aAdminOptions, $aOptionsPost);



			

			$licenseEmail = (isset($aOptions['listbuilder_license_email'])) ? $aOptions['listbuilder_license_email'] : '';

			$licenseKey = (isset($aOptions['listbuilder_license_key'])) ? $aOptions['listbuilder_license_key'] : '';

			

			//if (($this->checkPluginVersion() != 0) && !$this->checkAct($licenseEmail, $licenseKey)) {

			if (($this->checkPluginVersion() != 0) ) {

				global $wpdb;

				

				if($status == 'approved' || $status == 'approve' || $aOptions['klicktippAddSubscriberWithoutRelease']==TRUE) {

				  $active = get_comment_meta( $comment_id, 'klicktippListbuilding_active', true);



				  if(intval($active) == 1) {

					$table = $wpdb->prefix . 'comments';

					$email = $wpdb->get_var("select comment_author_email from ".$table." where comment_ID='".$comment_id."';");

					$author = get_comment_author( $comment_id );

					$mailsent = $wpdb->get_var("SELECT count(*) FROM ".$wpdb->prefix."commentmeta as B, ".$wpdb->prefix."comments as A WHERE A.comment_ID=B.comment_id and B.meta_key='klicktippListbuilding_sent' /*and A.comment_approved='1'*/ and A.comment_author_email='".$email."';");

					if(intval($mailsent)<=0) {

						

						if (array_key_exists('listbuilder-plugin-active', $aOptions) && ($aOptions['listbuilder-plugin-active'] == 'autoresponder')) {

							// send to autoresponder code

							$res = $this->sendToAutoresponderCode($comment_id, $email);

							if ($res) {

								add_comment_meta( $comment_id, 'klicktippListbuilding_sent',  '1');

							}

							

						} else if (array_key_exists('listbuilder-plugin-active', $aOptions) && ($aOptions['listbuilder-plugin-active'] == 'klicktipp')) {

							// send to klicktipp

							$this->KTSendSubscriber($comment_post_id, $email, $author);

							add_comment_meta( $comment_id, 'klicktippListbuilding_sent',  '1');

							

						}

					}

				  }

				}

			}

		}

		

		private function sendToAutoresponderCode($commentId, $email) {

			global $wpdb;

			$comment = get_comment( $commentId ); 

    		$comment_post_id = $comment->comment_post_ID ;



    		$aOptionsPost = $this->buildPostOptions($comment_post_id);

			$aAdminOptions = $this->getAdminOptions();

			$aOptions = array_merge($aAdminOptions, $aOptionsPost);

			

			$table = $wpdb->prefix . 'comments';

			$author = $wpdb->get_var("SELECT comment_author FROM " . $table . " WHERE comment_ID='" . $commentId . "';");

			

			// Build first array

			$viewable_fields = array(

				stripslashes($aOptions['autoresponderName']) => stripslashes($author),

				stripslashes($aOptions['autoresponderEmail']) => stripslashes($email)

			);

			// Get and extract hidden fields from options

			if (!empty($aOptions['autoresponderHidden'])) {

				$html = stripslashes($aOptions['autoresponderHidden']);

				$dom = new DOMDocument();

				$dom->loadHTML($html);

				$xpath = new DOMXPath($dom);

				$tags = $xpath->query('//input[@type="hidden"]');

				

				$hidden_fields = array();

				foreach ($tags as $tag) {

					$hidden_fields[$tag->getAttribute('name')] = $tag->getAttribute('value');

				}

			}

			// Build body to submit

			$body = array_merge($viewable_fields, $hidden_fields);

			

			$data = array(

				'method' => 'POST',

				'timeout' => 45,

				'redirection' => 5,

				'httpversion' => '1.0',

				'blocking' => true,

				'headers' => array(),

				'body' => $body,

				'cookies' => array()

			);

			// Send to email service

			return $this->sendForm(stripslashes($aOptions['autoresponderUrl']), $data);

		}

		

		private function sendForm($postUrl, $data){

			$request = new WP_Http();

			$response = $request->post($postUrl, $data);

			

			if ($response instanceof WP_Error) {

				return false;

			} else {

				return true;

			}

		}

		  

		public function clb_add_to_comments_field($fields) {

			global $post;



			if($this->showBox == false) {

				return $fields;

			}



			$options = $this->buildPostOptions($post->ID);

			$aoptions = $this->getAdminOptions();			

			$post_type = get_post_type( $post->ID );

			$licenseEmail = (isset($aoptions['listbuilder_license_email'])) ? $aoptions['listbuilder_license_email'] : '';

			$licenseKey = (isset($aoptions['listbuilder_license_key'])) ? $aoptions['listbuilder_license_key'] : '';

			$listbuilder_plugin_active = get_post_meta( $post->ID, '_listbuilder_plugin_active', true );

			if ($this->checkPluginVersion() == 0 || ( $listbuilder_plugin_active === '') ) {

				return $fields; // don't show the checkbox if plugin is inactive

			}
			
			if($listbuilder_plugin_active == 'default' && $aoptions['clb_pt_'.$post_type] == 'TRUE'){
				$options = $aoptions;
			}

			// if user activated plugin, then show checkbox
			if (array_key_exists('listbuilder-plugin-active', $options) && (!empty($options['listbuilder-plugin-active']))) {

				if ($options['listbuilder-plugin-active'] == 'klicktipp') {

					$aLoginStatus = $this->KTCheckLogin();

					if ($aLoginStatus['status']!==TRUE) {

					   return $fields; //don't show the checkbox if the klicktipp list email is empty (after installation)

					}

				} else if ($options['listbuilder-plugin-active'] == 'autoresponder') {

					if (empty($options['autoresponderCode'])) {

						// if autoresponder code is empty, don't show checkbox

						return $fields;

					}

				}

				

				$reason = stripslashes($options['klicktippCheckboxText']);
				$privacyUrl = (isset($options['listbuilder_privacy_url'])) ? $options['listbuilder_privacy_url'] : 'http://www.klick-tipp.com/datenschutz/15194';
				$additionalText = ($options['klicktippAdditionalText']) ? '<div>' . stripslashes($options['klicktippAdditionalText']) . '</div>' : '';
				$klicktippCheckboxChecked = $options['klicktippCheckboxChecked'] ? 'checked="checked" ' : '';

				echo '<div id="klicktippListbuilding">' . $additionalText . '<input type="checkbox" '.$klicktippCheckboxChecked.'rel="ccf2" name="klicktippListbuilding_subscribe" style="width:12px; margin-right: 10px;" value="on" /> '.$reason . '<br>' . sprintf(__('Wir halten uns an den <a href="%s" target="_blank">Datenschutz</a>.', 'comment-list-builder'), $privacyUrl) . '</div>';

				

				$this->showBox = false;

			}

			

			return $fields;

		}

		 

		public function clb_add_to_comments_with_script($fields) {

			if($this->showBox == false) return $fields;

	

			$fields = $this->clb_add_to_comments_field($fields);

			

			echo '<script type="text/javascript">

				(function() {

				  var comment = document.getElementById("comment");

				  var lb = document.getElementById("klicktippListbuilding");

				  

				  if ( comment.tagName.toLowerCase() == "textarea" ) {

					comment.parentNode.insertBefore(lb, null);

				  }

				}());

			  </script>';

			

			return $fields;

			}

		}



  } // end if(class_exists)

?>