window.SS88TurnstileCallback = function () {

    // api.js may load late (deferred/delayed by optimisation plugins); its onload calls us again.
    if (typeof window.turnstile === 'undefined') return;

    jQuery('.gform_wrapper form .cf-turnstile').each(function() {

        let CFT = jQuery(this);

        if(!CFT.is(':visible') || CFT.data('ss88-rendered')) return;

        CFT.data('ss88-rendered', true);

        let widgetId = turnstile.render(this, {
            'timeout-callback': function () {
                turnstile.reset(widgetId);
            }
        });

    });

};

document.addEventListener('DOMContentLoaded', SS88TurnstileCallback);
jQuery(document).on('gform_page_loaded', SS88TurnstileCallback);
jQuery(document).on('gform_post_render', SS88TurnstileCallback);
jQuery(document).on('gform_post_conditional_logic', SS88TurnstileCallback);
