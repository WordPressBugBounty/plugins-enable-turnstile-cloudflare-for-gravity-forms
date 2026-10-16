<?php

GFForms::include_addon_framework();

class SS88GFFCT extends GFAddOn {

	protected $_version = SS88GFFCT_VERSION;
	protected $_min_gravityforms_version = '1.9';
	protected $_slug = 'ss88-gravity-forms-turnstile';
	protected $_path = 'ss88-gravity-forms-turnstile/ss88-gravity-forms-turnstile.php';
	protected $_full_path = __FILE__;
	protected $_title = 'Cloudflare Turnstile for Gravity Forms';
	protected $_short_title = 'Turnstile';
	protected $_url = 'https://ss88.us/plugins/cloudflare-turnstile-for-gravity-forms-wordpress-plugin';

	private static $_instance = null;

	public static function get_instance() {
		if ( self::$_instance == null ) {
			self::$_instance = new SS88GFFCT();
		}

		return self::$_instance;
	}

	public function pre_init() {
		parent::pre_init();

		if ($this->is_gravityforms_supported() && class_exists('GF_Field')) {

			require_once('field.php');

		}
	}

	public function init() {

		parent::init();
		add_filter('gform_field_validation', [$this, 'gform_field_validation'], 10, 4);

	}

	public function init_admin() {

		parent::init_admin();
		add_filter('gform_tooltips', [$this, 'tooltips']);
		add_action('gform_field_appearance_settings', [$this, 'field_appearance_settings'], 10, 2);

	}

	public function plugin_settings_fields() {

		return [
			[
				'title'  => 'Turnstile Settings',
				'fields' => [
					[
						'name'              => 'ct_site_key',
						'tooltip'           => 'This can be found under Your Account -> Turnstile.',
						'label'             => 'Site Key',
						'type'              => 'text',
						'class'             => 'small',
						'placeholder'		=> 'e.g. 0x4AA888888888888',
						'required'			=> true,
						'feedback_callback' => [$this, 'is_length'],
					],
					[
						'name'              => 'ct_secret_key',
						'tooltip'           => 'This can be found under Your Account -> Turnstile.',
						'label'             => 'Secret Key',
						'type'              => 'text',
						'class'             => 'small',
						'required'			=> true,
						'placeholder'		=> 'e.g. 0x4AA9999999999999',
						'feedback_callback' => [$this, 'is_length'],
					]
				]
			]
		];

	}

	public function gform_field_validation($result, $value, $form, $field) {

		if ($field->type === 'turnstile') {

			$Response = isset($_POST['cf-turnstile-response']) ? $_POST['cf-turnstile-response'] : false;

			if(empty($Response)) {

				$result['is_valid'] = false;
				$result['message']  = 'Turnstile response was empty. Please try again.';

			}
			else {

				$CF_Data = [
					'secret' => $this->get_plugin_setting('ct_secret_key'),
					'response' => $Response
				];

				$apiResponse = wp_remote_post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
					'body' => wp_json_encode($CF_Data),
					'headers' => [
						'Content-Type' => 'application/json',
					]
				]);
	
				$apiBody = json_decode( wp_remote_retrieve_body( $apiResponse ), true);

				if($apiBody['success']==true) {

					$result['is_valid'] = true;

				}
				else {

					$ErrorMessage = '';

					if(in_array('missing-input-secret', $apiBody['error-codes'])) $ErrorMessage .= 'The secret parameter was not passed. ';
					if(in_array('invalid-input-secret', $apiBody['error-codes'])) $ErrorMessage .= 'The secret parameter was invalid or did not exist. ';
					if(in_array('missing-input-response', $apiBody['error-codes'])) $ErrorMessage .= 'The response parameter was not passed. ';
					if(in_array('invalid-input-response', $apiBody['error-codes'])) $ErrorMessage .= 'The response parameter is invalid or has expired. ';
					if(in_array('bad-request', $apiBody['error-codes'])) $ErrorMessage .= 'The request was rejected because it was malformed. ';
					if(in_array('timeout-or-duplicate', $apiBody['error-codes'])) $ErrorMessage .= 'The response parameter has already been validated before. ';
					if(in_array('internal-error', $apiBody['error-codes'])) $ErrorMessage .= 'An internal error happened while validating the response. The request can be retried. ';

					$result['is_valid'] = false;
					$result['message']  = 'Turnstile failed. Please try again. Error from Cloudflare: ' . $ErrorMessage;

				}

			}

		}

		return $result;

	}

	public function is_length( $value ) {

		return strlen( $value ) > 20;

	}

	public function get_menu_icon() {

		return 'gform-icon-SS88GFFCT-icon';

	}

	public function styles() {

		$styles = [
			[
				'handle'  => 'SS88GFFCT',
				'src'     => plugin_dir_url(__FILE__) . 'assets/css/admin.css',
				'version' => $this->_version,
				'enqueue' => [
					[
						'query' => 'page=gf_edit_forms'
					],
					[
						'query' => 'page=gf_settings'
					]
				]
			]
		];

		return array_merge( parent::styles(), $styles );
	}

	public function tooltips( $tooltips ) {

		$more_tooltips = [
			'turnstile_theme' => '<h6>Turnstile Theme</h6>Select the visual theme for the Turnstile field from the available options to better match your site design. "Auto" will inherit the visitor\'s preference.',
			'turnstile_size' => '<h6>Turnstile Size</h6>Select the size of the Turnstile field from the available options to better match your site design.',
		];
	 
		return array_merge($tooltips, $more_tooltips);
	}

	public function field_appearance_settings( $position, $form_id ) {
		if ( $position == 250 ) {
			?>

			<li class="turnstile_theme field_setting">
				<label for="field_turnstile_theme" class="section_label">
					Turnstile Theme
					<?php gform_tooltip('turnstile_theme') ?>
				</label>
				<select id="field_turnstile_theme" onchange="saveTurnstile(this.value);">
					<option value="auto">Auto</option>
					<option value="light">Light</option>
					<option value="dark">Dark</option>
				</select>
			</li>

			<li class="turnstile_size field_setting">
				<label for="field_turnstile_size" class="section_label">
					Turnstile Size
					<?php gform_tooltip('turnstile_size') ?>
				</label>
				<select id="field_turnstile_size" onchange="saveTurnstile(this.value);">
					<option value="normal">Normal</option>
					<option value="compact">Compact</option>
				</select>
			</li>
	 
			<?php
		}
	}

	function debug($msg) {

		error_log("\n" . '[' . date('Y-m-d H:i:s') . '] ' .  $msg, 3, plugin_dir_path(__FILE__) . 'debug.log');

	}

}
