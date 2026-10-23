let SS88GFFCT_Notice = {

    // Every SS88 plugin's Unicorn Panel notice. The 2FA plugin's has no shared class, so it needs its own selector
    selector: '.ss88-unicornpanel-notice, .SS88_2FAVE.notice[data-type="unicornpanel"]',
    storageKey: 'ss88_unicornpanel_dismissed',

    init: ()=>{

        if(typeof SS88GFFCT_NoticeData === 'undefined') return false;

        if(SS88GFFCT_Notice.isDismissedLocally()) {

            // Dismissed in this browser but the server didn't save it (blocked request, expired nonce, cache). Hide it and try again
            document.querySelectorAll(SS88GFFCT_Notice.selector).forEach((notice) => notice.remove());
            SS88GFFCT_Notice.save();

        }
        else {

            SS88GFFCT_Notice.removeDuplicates();

        }

        // Listen on the document straight away: core adds the X button on DOM ready, so there's no gap where a click isn't saved.
        // Handles the X on any plugin's Unicorn Panel notice, whichever one survived removeDuplicates()
        document.addEventListener('click', SS88GFFCT_Notice.onClick, true);

        return true;

    },
    removeDuplicates: ()=>{

        // Other SS88 plugins show the same Unicorn Panel notice, keep only the first one
        document.querySelectorAll(SS88GFFCT_Notice.selector).forEach((notice, index) => {

            if(index > 0) notice.remove();

        });

    },
    onClick: (e)=>{

        let button = e.target.closest ? e.target.closest('.notice-dismiss') : null;

        if(!button || !button.closest(SS88GFFCT_Notice.selector)) return;

        SS88GFFCT_Notice.rememberDismissed();
        SS88GFFCT_Notice.save();

    },
    save: ()=>{

        // Each active SS88 plugin runs this script, so only send one request per page
        if(document.documentElement.dataset.ss88UnicornpanelSaving) return;

        document.documentElement.dataset.ss88UnicornpanelSaving = '1';

        fetch(SS88GFFCT_NoticeData.ajax_url, {
            method: 'POST',
            credentials: 'same-origin',
            keepalive: true, // Finishes even if the user navigates away straight after clicking
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({ action: 'SS88GFFCT_DISMISS_NOTICE', type: 'unicornpanel', nonce: SS88GFFCT_NoticeData.nonce }),
        }).then(function(response) {
            if(!response.ok) throw new Error('Unicorn Panel notice dismissal failed: HTTP ' + response.status);
            return response.json();
        }).catch( err => {
            delete document.documentElement.dataset.ss88UnicornpanelSaving;
            console.log(err);
        } );

    },
    isDismissedLocally: ()=>{

        try { return '1' === window.localStorage.getItem(SS88GFFCT_Notice.storageKey); } catch(e) { return false; }

    },
    rememberDismissed: ()=>{

        try { window.localStorage.setItem(SS88GFFCT_Notice.storageKey, '1'); } catch(e) {}

    }

}

// Script is enqueued in the footer, so the notices are already in the DOM. Run now to avoid a visible flash
SS88GFFCT_Notice.init();
