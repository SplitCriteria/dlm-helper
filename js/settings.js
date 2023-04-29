/**
 * Initializes the settings menu and handles the clicks/events
 * of the various inputs/controls.
 */
function setupSettings() {

    /* Load the cache setting */
    let cacheSetting = localStorage.getItem('use_cache');
    if (cacheSetting === null) {
        cacheSetting = true;
        localStorage.setItem('use_cache', true);
    }
    useCache.checked = cacheSetting == "true";
    /* Save changes to the use cache setting */
    useCache.addEventListener('change', () => {
        localStorage.setItem('use_cache', useCache.checked);
    });

    /* Add a "clear cache" click listener */
    clearCache.addEventListener('click', async () => {
        /* Set the info modal to say that we're clearing the cache */
        infoModalBody.innerHTML = '<p>Clearing the cache...</p>';
        const data = new FormData();
        data.append("command", "clear");
        data.append("dir", "../cache");
        const response = await fetch('./php/cache.php', 
        {
            method: "POST",
            body: data
        });
        /* Show the info notice */
        infoModalBody.innerHTML = '<p>Cache cleared!</p>';
    });

    /* Load the proxy url */
    let proxyURLSetting = localStorage.getItem('proxy_url');
    if (proxyURLSetting === null) {
        proxyURLSetting = "http://localhost:4445";
        localStorage.setItem('proxy_url', proxyURLSetting);
    }
    proxyURL.value = proxyURLSetting;
    /* Save the proxy URL when it's changed */
    proxyURL.addEventListener('change', () => {
        localStorage.setItem('proxy_url', proxyURL.value);
    });

    /** 
     * Checks the status of the proxy server.
     */
    async function checkProxyStatus() {
        /* Remove both "status" badges */
        proxyOffline.classList.add('d-none');
        proxyOnline.classList.add('d-none');
        try {
            const data = new FormData();
            data.append('proxy', proxyURL.value);
            const response = await fetch('./php/check_proxy.php', 
            {
                method: "POST",
                body: data
            });
            /* The proxy should return a json object */
            const status = await response.json();
            if (status['status'] == 'ok') {
                /* Show success badge */
                proxyOnline.classList.remove('d-none');
            } else {
                throw true; 
            }
        } catch {
            /* Show the offline badge if there was an error */
            proxyOffline.classList.remove('d-none');
        }
    }

    checkProxyStatus();
    checkProxy.addEventListener('click', checkProxyStatus);

    /* Open the settings menu when the settings icon clicked */
    settingsIcon.addEventListener('click', () => {
        const settings = new bootstrap.Offcanvas('#settings');
        settings.show();
    });

}