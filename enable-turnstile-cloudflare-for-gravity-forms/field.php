<?php

defined('ABSPATH') || exit;

if ( ! class_exists( 'GFForms' ) ) {
	die();
}

class SS88GFFCT_Field extends GF_Field {

	public $type = 'turnstile';

	public function get_form_editor_field_title() {
		return esc_attr__( 'Turnstile', 'enable-turnstile-cloudflare-for-gravity-forms' );
	}

    public function get_form_editor_field_icon() {

        return 'gform-icon-SS88GFFCT-icon';

    }

	public function get_form_editor_button() {
		return array(
			'group' => 'advanced_fields',
			'text'  => $this->get_form_editor_field_title(),
            'description' => esc_attr__( 'Enable Cloudflare Turnstile for this form.', 'enable-turnstile-cloudflare-for-gravity-forms' ),
            'icon' => $this->get_form_editor_field_icon()
		);
	}

	// Conditional logic and visibility are deliberately omitted: a hidden Turnstile can't be solved.
	function get_form_editor_field_settings() {
		return array(
            'label_setting',
			'css_class_setting',
			'label_placement_setting',
			'description_setting',
			'turnstile_theme',
			'turnstile_size'
		);
	}

	public function is_conditional_logic_supported() {
		return false;
	}

	public function get_turnstile_theme() {
		return in_array( $this->turnstile_theme, array( 'auto', 'light', 'dark' ), true ) ? $this->turnstile_theme : 'auto';
	}

	public function get_turnstile_size() {
		return in_array( $this->turnstile_size, array( 'normal', 'compact' ), true ) ? $this->turnstile_size : 'normal';
	}

	public function sanitize_settings() {
		parent::sanitize_settings();

		$this->turnstile_theme = $this->get_turnstile_theme();
		$this->turnstile_size  = $this->get_turnstile_size();
	}

	public function get_form_editor_inline_script_on_page_render() {

		$script = sprintf( "function SetDefaultValues_turnstile(field) {field.label = '%s';}", esc_js( $this->get_form_editor_field_title() ) ) . PHP_EOL;
		$script .= "var turnstile_id = false;

			gform.addFilter('gform_form_editor_can_field_be_added', function (canFieldBeAdded, type) {

				if(type=='turnstile' && jQuery('.ginput_container_turnstile').length) {

					alert('" . esc_js( __( 'You have already added the Turnstile to the form.', 'enable-turnstile-cloudflare-for-gravity-forms' ) ) . "');
					return false;

				}

				return canFieldBeAdded;

			});

			jQuery(document).on('gform_load_field_settings', function (event, field, form) { turnstile_id = field.id;

			var turnstile_theme = (field.turnstile_theme == undefined || field.turnstile_theme == '') ? 'auto' : field.turnstile_theme;
			var turnstile_size = (field.turnstile_size == undefined || field.turnstile_size == '') ? 'normal' : field.turnstile_size;

			jQuery('#field_turnstile_theme').val(turnstile_theme);
			jQuery('#field_turnstile_size').val(turnstile_size);

			});" . PHP_EOL;

		$script .= "function saveTurnstile(value) {

			var theme = document.getElementById('field_turnstile_theme').value;
			var size = document.getElementById('field_turnstile_size').value;
			var maxWidth = (size=='compact') ? 130 : 300;

			SetFieldProperty('turnstile_theme', theme);
			SetFieldProperty('turnstile_size', size);

			if(theme=='auto') theme = 'light';

			jQuery('.ginput_container_turnstile img').attr('src', '" . esc_js( esc_url( plugin_dir_url( __FILE__ ) . 'assets/img/' ) ) . "'+theme+'-'+size+'.jpg');
			jQuery('.ginput_container_turnstile img').css('maxWidth', maxWidth + 'px');

		}" . PHP_EOL;

		return $script;
	}

	public function get_field_input( $form, $value = '', $entry = null ) {

		$id              = absint( $this->id );
		$form_id         = absint( $form['id'] );
		$is_entry_detail = $this->is_entry_detail();
		$is_form_editor  = $this->is_form_editor();
		$field_id = $is_entry_detail || $is_form_editor || $form_id == 0 ? "input_$id" : 'input_' . $form_id . "_$id";

		$turnstile_theme = $this->get_turnstile_theme();
		$turnstile_size  = $this->get_turnstile_size();

        if ( $is_form_editor || $is_entry_detail || ( is_admin() && ! wp_doing_ajax() ) ) {

			// Previews only exist for light and dark; "auto" shows the light one.
			$preview_theme = ( $turnstile_theme === 'auto' ) ? 'light' : $turnstile_theme;
			$maxWidth      = ( $turnstile_size === 'compact' ) ? 130 : 300;

		    $input = sprintf(
				"<img style='max-width:%dpx;' id='%s' src='%s' alt='%s'>",
				$maxWidth,
				esc_attr( $field_id ),
				esc_url( plugin_dir_url( __FILE__ ) . "assets/img/{$preview_theme}-{$turnstile_size}.jpg" ),
				esc_attr__( 'Turnstile preview', 'enable-turnstile-cloudflare-for-gravity-forms' )
			);

        }
        else {

            $SS88GFFCT = SS88GFFCT::get_instance();
            $pass = $SS88GFFCT->get_render_pass( $form_id );

            if ( $pass ) {

                // Already verified for this submission, so carry the pass forward instead of a new widget.
                $input = sprintf( "<input type='hidden' name='ss88gffct_pass' value='%s'>", esc_attr( $pass ) );

            }
            else {

                wp_enqueue_script('SS88GFFCT-Turnstile', plugin_dir_url( __FILE__ ) . 'assets/js/front.js', ['jquery'], SS88GFFCT_VERSION, true);
                wp_enqueue_script('SS88GFFCT-ct', 'https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit&onload=SS88TurnstileCallback', ['SS88GFFCT-Turnstile'], SS88GFFCT_VERSION, true);

                $input = sprintf(
                    "<div class='cf-turnstile' data-sitekey='%s' data-theme='%s' data-size='%s'></div>",
                    esc_attr( $SS88GFFCT->get_plugin_setting( 'ct_site_key' ) ),
                    esc_attr( $turnstile_theme ),
                    esc_attr( $turnstile_size )
                );

            }

        }

		return sprintf( "<div class='ginput_container ginput_container_%s'>%s</div>", $this->type, $input );
	}

}

GF_Fields::register( new SS88GFFCT_Field() );
