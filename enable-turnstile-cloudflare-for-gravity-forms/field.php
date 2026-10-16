<?php

if ( ! class_exists( 'GFForms' ) ) {
	die();
}

class SS88GFFCT_Field extends GF_Field {

	public $type = 'turnstile';

	public function get_form_editor_field_title() {
		return 'Turnstile';
	}

    public function get_form_editor_field_icon() {

        return 'gform-icon-SS88GFFCT-icon';

    }

	public function get_form_editor_button() {
		return array(
			'group' => 'advanced_fields',
			'text'  => $this->get_form_editor_field_title(),
            'description' => 'Enable Cloudflare Turnstile for this form.',
            'icon' => $this->get_form_editor_field_icon()
		);
	}

	function get_form_editor_field_settings() {
		return array(
            'label_setting',
			'css_class_setting',
			'conditional_logic_field_setting',
			'visibility_setting',
			'label_placement_setting',
			'description_setting',
			'turnstile_theme',
			'turnstile_size'
		);
	}

	public function is_conditional_logic_supported() {
		return true;
	}

	public function get_form_editor_inline_script_on_page_render() {

		$script = sprintf( "function SetDefaultValues_turnstile(field) {field.label = '%s';}", $this->get_form_editor_field_title() ) . PHP_EOL;
		$script .= "var turnstile_id = false;

			gform.addFilter('gform_form_editor_can_field_be_added', function (canFieldBeAdded, type) {
				
				if(type=='turnstile' && jQuery('.ginput_container_turnstile').length) {
					
					alert('You have already added the Turnstile to the form.');
					return false;

				}

				return canFieldBeAdded;

			});
		
			jQuery(document).bind('gform_load_field_settings', function (event, field, form) { turnstile_id = field.id;

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

			jQuery('.ginput_container_turnstile img').attr('src', '". plugin_dir_url(__FILE__) . "assets/img/'+theme+'-'+size+'.jpg');
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
		$value = esc_attr( $value );
		$class_suffix = $is_entry_detail ? '_admin' : '';
		$class        = $class_suffix;
		$tabindex              = $this->get_tabindex();

		$turnstile_theme = empty($this->turnstile_theme) ? 'auto' : $this->turnstile_theme;
		$turnstile_theme = ($turnstile_theme=='auto') ? 'light' : $turnstile_theme;

		$turnstile_size = empty($this->turnstile_size) ? 'normal' : $this->turnstile_size;

		$maxWidth = ($this->turnstile_size=='compact') ? 130 : 300;

        if(is_admin() && !wp_doing_ajax() || isset($_POST['rg_add_field'])) {

		    $input = "<img name='input_{$id}' style='max-width:{$maxWidth}px;' class='{$class}' {$tabindex} id='{$field_id}' src='". plugin_dir_url(__FILE__) ."/assets/img/{$turnstile_theme}-{$turnstile_size}.jpg'>";
		    return sprintf( "<div class='ginput_container ginput_container_%s'>%s</div>", $this->type, $input );

        }
        else {

            wp_enqueue_script('SS88GFFCT-Turnstile', plugin_dir_url( __FILE__ ) . 'assets/js/front.js', false, SS88GFFCT_VERSION, true);
            wp_enqueue_script('SS88GFFCT-ct', 'https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit', ['SS88GFFCT-Turnstile'], false, true);

            $SS88GFFCT = new SS88GFFCT();
            $SiteKey = $SS88GFFCT->get_plugin_setting('ct_site_key');
            $turnstile = "<div class='cf-turnstile' data-sitekey='{$SiteKey}' data-timeout-callback='SS88TurnstileCallback' data-theme='{$turnstile_theme}' data-size='{$turnstile_size}'></div>";
            return sprintf( "<div class='ginput_container ginput_container_%s'>%s</div>", $this->type, $turnstile);

        }
	}

}

GF_Fields::register( new SS88GFFCT_Field() );