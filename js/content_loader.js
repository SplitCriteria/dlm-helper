/**
 * Sets up the capabilities to request and load content from
 * external sources including the cache.
 */
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
     * @return the content loaded
     */
    async function loadContent(data, signal, destinations) {
        /* Make sure destinations is an array */
        if (!Array.isArray(destinations)) {
            destinations = [ destinations ];
        }
        /* Fetch data from our own fetcher which has its own
           cache */
        const response = await fetch('./php/fetch.php', {
            method: "POST",
            body: data,
            signal: signal
        });
        /* fetch.php returns a JSON object with content
        in the 'data' property */
        const content = await response.json();

        /* Save the content in the destinations and trigger 
        their input listeners */
        destinations.forEach(dst => { 
            dst.value = content['data'];
            dst.dispatchEvent(new Event('input'));
        });

        /* Return the content */
        return content;
    }

    /* When the search URL changes, then fetch the 
       source, if able */
    searchURL.addEventListener('change', async () => {

        /* Create an abort controller to stop the 
           request if necessary */
        const abortController = new AbortController();
        /* Get a signal from the controller which we'll 
           pass in the request body */
        const signal = abortController.signal;
        
        /* Create the data to pass to the content fetcher */
        const data = new FormData();
        data.append("url", searchURL.value);
        data.append("cache", useCache.checked);
        /* If a proxy is desired, send the proxy URL */
        if (moduleUseProxy.checked) {
            data.append("proxy", proxyURL.value);
        }

        /* Remove the old content and show the loading spinners */
        sourceContent.value = '';
        sourceContent.setAttribute('disabled', true);
        sourceLoading.classList.remove('invisible');
        sourceMethodBadge.classList.add('invisible');
        /* Remove any success/danger classes with the badge */
        sourceMethodBadge.classList.remove('btn-success', 'btn-danger');

        /* Start, and await the response of, the content loading process */
        const content = await loadContent(data, signal, [urlSource, sourceContent])
            .catch((err) => {
                /* Set the source to an error message */
                urlSource.value = sourceContent.value = "Error fetching URL '" +
                    searchURL.value+"'\n\n"+err;
                /* Set the content source to an error */
                return { "source": "Error" };
            });

        /* Remove the loading spinners and set the info badges */
        sourceContent.removeAttribute('disabled');
        sourceLoading.classList.add('invisible');
        switch (content['source']) {
            case 'cache':
                /* Show a success color on the source content badge */
                sourceMethodBadge.classList.add('btn-success');
                sourceMethodBadge.innerText = 'From Cache';
                break;
            case 'webdriver':
                sourceMethodBadge.classList.add('btn-success');
                sourceMethodBadge.innerText = 'From WebDriver';
                break;
            case 'curl':
                sourceMethodBadge.classList.add('btn-success');
                sourceMethodBadge.innerText = 'From cURL';
                break;
            default:
                /* Show a danger color for unknown sources (usually errors) */
                sourceMethodBadge.classList.add('btn-danger');
                sourceMethodBadge.innerText = content['source'];
        }
        sourceMethodBadge.classList.remove('invisible');

    });
    
    /* When the page pattern changes, attempt to fetch a details
       page and store it for other dependent patterns to match with 
       when the user clicks the "use page" checkbox */
    let pageDetailsURL = null;
    pageMatches.addEventListener('change', async () => {
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
            data.append("cache", useCache.checked);
            if (moduleUseProxy.checked) {
                data.append("proxy", proxyURL.value);
            }
            /* Load the content */
            await loadContent(data, signal, detailsPageSource)
                .catch((err) => {
                    const msg = "Error loading details page '"+pageDetailsURL+"'\n"+err;
                    detailsPageSource.value = msg;
                    console.log(msg);
                    return;
                });
        }
    });
}