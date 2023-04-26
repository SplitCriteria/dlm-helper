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

    /* Clear the cache */
    clearCache.addEventListener('click', () => {
        const data = new FormData();
        data.append("command", "clear");
        data.append("dir", "../cache");
        const response = fetch('./php/cache.php', 
        {
            method: "POST",
            body: data
        });
    });

    /* Open the settings menu when the settings icon clicked */
    settingsIcon.addEventListener('click', () => {
        const settings = new bootstrap.Offcanvas('#settings');
        settings.show();
    });

}