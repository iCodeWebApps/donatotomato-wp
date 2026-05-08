(function () {
    var WIDGET_ORIGIN = 'https://app.donatotomato.com';

    window.addEventListener('message', function (e) {
        if (e.origin !== WIDGET_ORIGIN) return;
        if (!e.data || e.data.type !== 'dt-resize' || typeof e.data.height !== 'number') return;

        var iframes = document.querySelectorAll('iframe[src*="app.donatotomato.com"]');
        iframes.forEach(function (iframe) {
            iframe.style.height = (e.data.height + 24) + 'px';
        });
    });
})();
