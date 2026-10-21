<?php

defined('ABSPATH') || exit;

GFForms::include_addon_framework();

class SS88GFFCT extends GFAddOn {

	protected $_version = SS88GFFCT_VERSION;
	protected $_min_gravityforms_version = '1.9';
	protected $_slug = 'ss88-gravity-forms-turnstile';
	protected $_path;
	protected $_full_path = SS88GFFCT_FILE;
	protected $_title = 'Cloudflare Turnstile for Gravity Forms';
	protected $_short_title = 'Turnstile';
	protected $_url = 'https://ss88.us/plugins/cloudflare-turnstile-for-gravity-forms-wordpress-plugin';

	private static $_instance = null;

	// Verified passes for the current request, keyed by form ID (false = none).
	private $passes = [];

	// Forms whose final submission passed the check in this request, keyed by form ID.
	private $claimed = [];

	public static function get_instance() {
		if ( self::$_instance == null ) {
			self::$_instance = new SS88GFFCT();
		}

		return self::$_instance;
	}

	public function __construct() {

		$this->_path = plugin_basename(SS88GFFCT_FILE);

		parent::__construct();

	}

	public function pre_init() {
		parent::pre_init();

		if ($this->is_gravityforms_supported() && class_exists('GF_Field')) {

			require_once __DIR__ . '/field.php';

		}
	}

	public function init() {

		parent::init();
		add_filter('gform_pre_render', [$this, 'force_field_visible']);
		add_filter('gform_pre_validation', [$this, 'force_field_visible']);
		add_filter('gform_validation', [$this, 'gform_validation'], 10, 2);
		add_action('gform_after_submission', [$this, 'reset_pass'], 10, 2);

	}

	public function init_admin() {

		parent::init_admin();
		add_filter('gform_tooltips', [$this, 'tooltips']);
		add_action('gform_field_appearance_settings', [$this, 'field_appearance_settings'], 10, 2);

	}

	public function plugin_settings_fields() {

		return [
			[
				'title'  => esc_html__('Turnstile Settings', 'enable-turnstile-cloudflare-for-gravity-forms'),
				'fields' => [
					[
						'name'              => 'ct_site_key',
						'tooltip'           => esc_html__('This can be found under Your Account -> Turnstile.', 'enable-turnstile-cloudflare-for-gravity-forms'),
						'label'             => esc_html__('Site Key', 'enable-turnstile-cloudflare-for-gravity-forms'),
						'type'              => 'text',
						'class'             => 'small',
						'placeholder'		=> 'e.g. 0x4AA888888888888',
						'required'			=> true,
						'feedback_callback' => [$this, 'is_length'],
					],
					[
						'name'              => 'ct_secret_key',
						'tooltip'           => esc_html__('This can be found under Your Account -> Turnstile.', 'enable-turnstile-cloudflare-for-gravity-forms'),
						'label'             => esc_html__('Secret Key', 'enable-turnstile-cloudflare-for-gravity-forms'),
						'type'              => 'text',
						'input_type'        => 'password',
						'class'             => 'small',
						'required'			=> true,
						'placeholder'		=> 'e.g. 0x4AA9999999999999',
						'feedback_callback' => [$this, 'is_length'],
					]
				]
			]
		];

	}

	public function get_turnstile_field($form) {

		if (!is_array($form) || empty($form['fields']) || !is_array($form['fields'])) return false;

		foreach ($form['fields'] as $field) {
			if (is_object($field) && $field->type === 'turnstile') return $field;
		}

		return false;

	}

	// A hidden Turnstile field could never be solved, so conditional logic and visibility are ignored.
	public function force_field_visible($form) {

		$field = $this->get_turnstile_field($form);

		if ($field) {
			$field->conditionalLogic = null;
			$field->visibility = 'visible';
		}

		return $form;

	}

	// Runs at form level so the check can't be skipped by hiding the field or its page.
	public function gform_validation($validation_result, $context = 'form-submit') {

		$form = $validation_result['form'];
		$field = $this->get_turnstile_field($form);

		if (!$field) return $validation_result;

		$form_id = absint($form['id']);
		$field_page = max(1, (int) $field->pageNumber);
		$is_final = $this->is_final_submission($form);
		$is_validate_only = ($context === 'api-validate');

		if (!$is_final && $field_page !== (int) GFFormDisplay::get_source_page($form_id)) return $validation_result;

		$pass = $this->get_pass($form_id);

		if ($pass) {

			if (!$is_final || $is_validate_only) return $validation_result;

			// Claim the pass atomically: only one request can delete it, so concurrent replays can't reuse it.
			$this->passes[$form_id] = false;

			if (delete_transient($this->pass_key($pass))) {
				$this->claimed[$form_id] = true;
				return $validation_result;
			}

		}

		// Validate-only requests (REST /submissions/validation, GFAPI::validate_form) must not consume the single-use token.
		if ($is_validate_only) {
			$verified = ($this->posted_string('cf-turnstile-response') !== '') ? true : esc_html__('Please complete the security check and try again.', 'enable-turnstile-cloudflare-for-gravity-forms');
		}
		else {
			$verified = $this->verify_token();
		}

		if ($verified === true) {

			if ($is_validate_only) return $validation_result;

			// A pass is only needed if the visitor will see the form again: after a page step now, or after a failed final submission (see get_render_pass).
			if ($is_final) $this->claimed[$form_id] = true;
			else $this->passes[$form_id] = $this->create_pass($form_id);

			return $validation_result;

		}

		$field->failed_validation = true;
		$field->validation_message = $verified;

		if ($validation_result['is_valid'] || empty($validation_result['failed_validation_page']) || $field_page < (int) $validation_result['failed_validation_page']) {
			$validation_result['failed_validation_page'] = $field_page;
		}

		$validation_result['is_valid'] = false;
		$validation_result['form'] = $form;

		return $validation_result;

	}

	private function is_final_submission($form) {

		if (!class_exists('GFFormDisplay')) return true;

		$source_page = GFFormDisplay::get_source_page($form['id']);
		$target_page = GFFormDisplay::get_target_page($form, $source_page, rgpost('gform_field_values'));

		return (int) $target_page === 0;

	}

	// Returns true on success, or the validation message to show.
	private function verify_token() {

		$token = $this->posted_string('cf-turnstile-response');

		if ($token === '') {
			return esc_html__('Please complete the security check and try again.', 'enable-turnstile-cloudflare-for-gravity-forms');
		}

		$apiResponse = wp_remote_post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
			'timeout' => 10,
			'body' => wp_json_encode([
				'secret' => $this->get_plugin_setting('ct_secret_key'),
				'response' => $token,
			]),
			'headers' => [
				'Content-Type' => 'application/json',
			]
		]);

		if (is_wp_error($apiResponse)) {

			$this->log_error(__METHOD__ . '(): Could not reach Cloudflare: ' . $apiResponse->get_error_message());
			return $this->failure_message(esc_html__('Could not connect to Cloudflare.', 'enable-turnstile-cloudflare-for-gravity-forms'));

		}

		$apiBody = json_decode(wp_remote_retrieve_body($apiResponse), true);

		if (!is_array($apiBody)) {

			$this->log_error(__METHOD__ . '(): Unexpected response from Cloudflare (HTTP ' . wp_remote_retrieve_response_code($apiResponse) . ').');
			return $this->failure_message(esc_html__('Unexpected response from Cloudflare.', 'enable-turnstile-cloudflare-for-gravity-forms'));

		}

		if (!empty($apiBody['success'])) return true;

		$codes = isset($apiBody['error-codes']) ? (array) $apiBody['error-codes'] : [];
		$this->log_error(__METHOD__ . '(): Verification failed: ' . implode(', ', $codes));

		$descriptions = [
			'missing-input-secret' => __('The secret parameter was not passed.', 'enable-turnstile-cloudflare-for-gravity-forms'),
			'invalid-input-secret' => __('The secret parameter was invalid or did not exist.', 'enable-turnstile-cloudflare-for-gravity-forms'),
			'missing-input-response' => __('The response parameter was not passed.', 'enable-turnstile-cloudflare-for-gravity-forms'),
			'invalid-input-response' => __('The response parameter is invalid or has expired.', 'enable-turnstile-cloudflare-for-gravity-forms'),
			'bad-request' => __('The request was rejected because it was malformed.', 'enable-turnstile-cloudflare-for-gravity-forms'),
			'timeout-or-duplicate' => __('The response parameter has already been validated before.', 'enable-turnstile-cloudflare-for-gravity-forms'),
			'internal-error' => __('An internal error happened while validating the response. The request can be retried.', 'enable-turnstile-cloudflare-for-gravity-forms'),
		];

		$details = [];
		foreach ($codes as $code) {
			if (isset($descriptions[$code])) $details[] = esc_html($descriptions[$code]);
		}

		return $this->failure_message(implode(' ', $details));

	}

	// Visitors get a generic message; users who can manage settings also see the reason.
	private function failure_message($detail = '') {

		$message = esc_html__('Security check failed. Please try again.', 'enable-turnstile-cloudflare-for-gravity-forms');

		if ($detail !== '' && GFCommon::current_user_can_any('gravityforms_edit_settings')) {
			$message .= ' ' . esc_html__('Error from Cloudflare:', 'enable-turnstile-cloudflare-for-gravity-forms') . ' ' . $detail;
		}

		return $message;

	}

	/*
	 * A pass records that this visitor already solved Turnstile for this form, so the
	 * check survives multi-page navigation and re-renders after other validation errors
	 * (Turnstile tokens are single-use). The final submission claims (deletes) it.
	 */
	public function get_pass($form_id) {

		$form_id = absint($form_id);

		if (!array_key_exists($form_id, $this->passes)) {

			$pass = $this->posted_string('ss88gffct_pass');
			$valid = absint(rgpost('gform_submit')) === $form_id
				&& preg_match('/^[A-Za-z0-9]{32}$/', $pass)
				&& absint(get_transient($this->pass_key($pass))) === $form_id;

			$this->passes[$form_id] = $valid ? $pass : false;

		}

		return $this->passes[$form_id];

	}

	private function create_pass($form_id) {

		$pass = wp_generate_password(32, false);
		set_transient($this->pass_key($pass), absint($form_id), HOUR_IN_SECONDS);

		return $pass;

	}

	// The pass to output when rendering the field. If the final submission passed the check but the form
	// is being shown again (another field failed validation), a fresh pass saves the visitor re-solving.
	public function get_render_pass($form_id) {

		$form_id = absint($form_id);
		$pass = $this->get_pass($form_id);

		if (!$pass && !empty($this->claimed[$form_id])) {

			$pass = $this->passes[$form_id] = $this->create_pass($form_id);
			$this->claimed[$form_id] = false;

		}

		return $pass;

	}

	public function reset_pass($entry, $form) {

		$form_id = absint(rgar($form, 'id'));

		$this->passes[$form_id] = false;
		$this->claimed[$form_id] = false;

	}

	private function pass_key($pass) {

		return 'ss88gffct_' . md5($pass);

	}

	private function posted_string($key) {

		return (isset($_POST[$key]) && is_string($_POST[$key])) ? sanitize_text_field(wp_unslash($_POST[$key])) : '';

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
			'turnstile_theme' => '<h6>' . esc_html__('Turnstile Theme', 'enable-turnstile-cloudflare-for-gravity-forms') . '</h6>' . esc_html__('Select the visual theme for the Turnstile field from the available options to better match your site design. "Auto" will inherit the visitor\'s preference.', 'enable-turnstile-cloudflare-for-gravity-forms'),
			'turnstile_size' => '<h6>' . esc_html__('Turnstile Size', 'enable-turnstile-cloudflare-for-gravity-forms') . '</h6>' . esc_html__('Select the size of the Turnstile field from the available options to better match your site design.', 'enable-turnstile-cloudflare-for-gravity-forms'),
		];

		return array_merge($tooltips, $more_tooltips);
	}

	public function field_appearance_settings( $position, $form_id ) {
		if ( $position == 250 ) {
			?>

			<li class="turnstile_theme field_setting">
				<label for="field_turnstile_theme" class="section_label">
					<?php esc_html_e('Turnstile Theme', 'enable-turnstile-cloudflare-for-gravity-forms'); ?>
					<?php gform_tooltip('turnstile_theme') ?>
				</label>
				<select id="field_turnstile_theme" onchange="saveTurnstile(this.value);">
					<option value="auto"><?php esc_html_e('Auto', 'enable-turnstile-cloudflare-for-gravity-forms'); ?></option>
					<option value="light"><?php esc_html_e('Light', 'enable-turnstile-cloudflare-for-gravity-forms'); ?></option>
					<option value="dark"><?php esc_html_e('Dark', 'enable-turnstile-cloudflare-for-gravity-forms'); ?></option>
				</select>
			</li>

			<li class="turnstile_size field_setting">
				<label for="field_turnstile_size" class="section_label">
					<?php esc_html_e('Turnstile Size', 'enable-turnstile-cloudflare-for-gravity-forms'); ?>
					<?php gform_tooltip('turnstile_size') ?>
				</label>
				<select id="field_turnstile_size" onchange="saveTurnstile(this.value);">
					<option value="normal"><?php esc_html_e('Normal', 'enable-turnstile-cloudflare-for-gravity-forms'); ?></option>
					<option value="compact"><?php esc_html_e('Compact', 'enable-turnstile-cloudflare-for-gravity-forms'); ?></option>
				</select>
			</li>

			<?php
		}
	}

}
