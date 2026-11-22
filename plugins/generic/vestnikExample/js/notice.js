(() => {
    const labels = pkp.const.VESTNIK_EXAMPLE;

    function startNotice() {
        const notice = document.createElement('aside');
        notice.className = 'vestnik-example-notice';

        const message = document.createElement('span');
        message.setAttribute('role', 'status');
        message.textContent = labels.message;

        const stop = document.createElement('button');
        stop.type = 'button';
        stop.textContent = labels.stop;

        notice.append(message, stop);
        document.body.append(notice);

        let hideTimer;
        function showNotice() {
            notice.hidden = false;
            hideTimer = window.setTimeout(() => { notice.hidden = true; }, 3500);
        }

        showNotice();
        const repeatTimer = window.setInterval(showNotice, 5000);
        stop.addEventListener('click', () => {
            window.clearInterval(repeatTimer);
            window.clearTimeout(hideTimer);
            notice.remove();
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', startNotice, {once: true});
    } else {
        startNotice();
    }
})();
