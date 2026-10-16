window.SS88TurnstileCallback = function () {

    jQuery('.gform_wrapper form').each(function() {

        let Form = jQuery(this);
        let CFT = Form.find('.cf-turnstile');

        if(CFT.is(':visible') && CFT.html() == '') {
        
            turnstile.render(CFT.get(0));
    
        }

    });

};

document.addEventListener('DOMContentLoaded', SS88TurnstileCallback);
jQuery(document).on('gform_page_loaded', SS88TurnstileCallback);
jQuery(document).on('gform_post_render', SS88TurnstileCallback);
jQuery(document).on('gform_post_conditional_logic', SS88TurnstileCallback);