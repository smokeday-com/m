<script type="text/javascript">
(() => {
    // Define a map to store loaded assets
    const loadedCss = {};
    const loadedJs = {};
    const lazyObserver = new IntersectionObserver((entries, observer) => {
        entries.forEach(entry => {
            if (entry.isIntersecting) {
                lazyComponent(entry.target);
                observer.unobserve(entry.target);
            }
        });
    });

    function lazyComponent(element) {
        var name = element.getAttribute('data-lazy-component');
        var jsPath = element.getAttribute('data-lazy-js');
        var cssPath = element.getAttribute('data-lazy-css');
        addJs(name, jsPath);
        addCss(name, cssPath);
        if (typeof element.callback === 'function') {
            callback();
        }
    }

    function addCss(name, path) {
        if (name && path && !loadedCss[name]) {
            const link = document.createElement('link');
            link.rel = 'stylesheet';
            link.type = 'text/css';
            link.href = path;
            addAsset(path, link, name, 'Css');
        }
    }

    function addJs(name, path) {
        if (name && path && !loadedJs[name]) {
            const script = document.createElement('script');
            script.type = 'text/javascript';
            script.src = path;
            script.setAttribute('async', 'true');
            addAsset(path, script, name, 'Js');
        }
    }

    function addAsset(path, item, name, type){
        item.onload = () => {
            if(type === 'css'){
                loadedCss[name] = true;
            }
            if(type === 'js'){
                loadedJs[name] = true;
            }
            // Fire trigger/event when asset is loaded
            const eventName = `loaded ${type}:${name}`;
            const event = new CustomEvent(eventName);
            window.dispatchEvent(event);
        };
        item.onerror = () => {
            console.error(`Failed to load asset: ${path}`);
        };
        document.head.appendChild(item);
    }
    function add2Observe(element, callback ) {
        if(!element ) { return }
        element.callback = callback;
        if ('IntersectionObserver' in window) {
            lazyObserver.observe(element);
        } else {
            lazyComponent(element);
        }
    }
    window.lazyComponent = lazyComponent;
    window.add2Observe = add2Observe;
})();
// document.addEventListener('DOMContentLoaded', function () {
//     var lazyComponents = document.querySelectorAll('.lazy-component');
//     lazyComponents.forEach(function (lazyComponent) {
//         add2Observe.observe(lazyComponent);
//     });
// }, false)

// require(['jquery'], function ($) {
//     $(document.body).on('contentUpdated contentUpdated.oxlazy', lazyComponent)
// })

</script>