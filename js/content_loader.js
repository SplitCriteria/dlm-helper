function setupContentLoader() {

    /**
     * Load content using a custom php fetch script with 
     * its own internal caching system
     * 
     * @param {*} data          FormData used for POST
     * @param {*} signal        a signal for an AbortController
     * @param {*} destinations  an array of destinations for
     *                          the loaded content to go -- 
     *                          'input' events will be sent to
     *                          destinations after updating
     */
    async function loadContent(data, signal, destinations) {
        /* Make sure destinations is an array */
        if (!Array.isArray(destinations)) {
            destinations = [ destinations ];
        }
        /* Fetch data from our own fetcher which has its own
           cache */
        const response = await fetch('./php/fetch.php', 
        {
            method: "POST",
            body: data,
            signal: signal
        });
        const text = await response.text();
        /* Save the content in the destinations and trigger 
           their input listeners */
        destinations.forEach(dst => { 
            dst.value = text;
            dst.dispatchEvent(new Event('input'));
        });
    }

    /* When the search URL changes, then fetch the 
       source, if able */
    searchURL.addEventListener('change', () => {

        /* Create an abort controller to stop the 
           request if necessary */
        const abortController = new AbortController();
        /* Get a signal from the controller which we'll 
           pass in the request body */
        const signal = abortController.signal;
        
        /* Create the data to pass to the content fetcher */
        const data = new FormData();
        data.append("url", searchURL.value);
        data.append("cache", true);

        /* Start the content loading process */
        loadContent(data, signal, [urlSource, sourceContent]);
    });
    
    /* When the page pattern changes, attempt to fetch a details
       page and store it for other dependent patterns to match with 
       when the user clicks the "use page" checkbox */
    let pageDetailsURL = null;
    pageMatches.addEventListener('change', () => {
        /* Create an abort controller to stop the 
        request if necessary */
        const abortController = new AbortController();
        /* Get a signal from the controller which we'll 
        pass in the request body */
        const signal = abortController.signal;

        /* Get the domain from the searchURL */
        const domain = searchURL.value.match(/(?:https?:\/\/)[^\/]*/);
        const url = (domain ? domain[0] : '') + pageMatches.value;
        /* Skip fetching a URL which is identical to the last-fetched */
        if (pageDetailsURL != url) {
            pageDetailsURL = url;
            const data = new FormData();
            data.append("url", url);
            data.append("cache", true);
            loadContent(data, signal, detailsPageSource);
        }
    });
}