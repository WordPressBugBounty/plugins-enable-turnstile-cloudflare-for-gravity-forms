let SS88GFFCT_Notice = {

    init: ()=>{

        return SS88GFFCT_Notice.initDismissButtons();

    },
    removeDuplicates: ()=>{

        // Other SS88 plugins show the same Unicorn Panel notice, keep only the first one
        document.querySelectorAll('.ss88-unicornpanel-notice, .SS88_2FAVE.notice[data-type="unicornpanel"]').forEach((notice, index) => {

            if(index > 0) notice.remove();

        });

    },
    initDismissButtons: ()=>{

        document.querySelectorAll('.SS88GFFCT.notice .notice-dismiss').forEach((button) => {

            button.addEventListener('click', (e) => {

                e.preventDefault();

                fetch(SS88GFFCT_NoticeData.ajax_url, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: new URLSearchParams({ action: 'SS88GFFCT_DISMISS_NOTICE', type: e.target.closest('.SS88GFFCT.notice').dataset.type, nonce: SS88GFFCT_NoticeData.nonce }),
                }).then(function(response) {
                    return response.json();
                }).catch( err => { console.log(err); } );

            });

        });

        return true;

    }

}

// Script is enqueued in the footer, so the notices are already in the DOM. Run now to avoid a visible flash
SS88GFFCT_Notice.removeDuplicates();

window.addEventListener('load', (event) => {

    if(document.querySelector('.SS88GFFCT.notice')) {

        SS88GFFCT_Notice.init();

    }

});
