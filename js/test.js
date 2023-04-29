/**
 * Sets up a test DLM search using search.php with the current 
 * patterns.
 */
function setupTestDLM() {

    const maxResults = 5;

    async function runTest(data, signal) {
        /* Fetch data from our own fetcher which has its own
           cache */
        let response, json;
        try {
            response = await fetch('./php/test.php', 
            {
                method: "POST",
                body: data,
                signal: signal
            });
            json = await response.json();
        } catch (err) {
            testResultsLoadingSpinner.classList.add('d-none');
            testDLMResults.innerHTML = '<p>Error</p><p>'+err+'</p>';
            throw err;
        }
        console.log('DLM Test Results: ', json);
        /* Hide the loading spinner */
        testResultsLoadingSpinner.classList.add('d-none');
        /* Load the results into the test modal */
        if (json) {
            let testInfo = '';
            for (const info of json['info']) {
                testInfo += '<div class="card-text">'+info+'</div>';
            }
            testDLMResults.innerHTML = 
                `<div class="col-12">\
                    <div class="card">\
                        <div class="card-header">Test Parameters</div>\
                        <div class="card-body">\
                            ${testInfo}\
                        </div>\
                    </div>\
                </div>`;
            for (const datum of json['data']) {
                testDLMResults.insertAdjacentHTML('beforeend', datum);
            }
        } else {
            testDLMResults.innerHTML = '<p>No results returned</p>';
        }
    }

    /* Run the test when the test button is clicked */
    testDLM.addEventListener('click', () => {
        /* Show the loading spinner */
        testResultsLoadingSpinner.classList.remove('d-none');
        /* Clear out old test results */
        testDLMResults.innerHTML = '';
        /* Create an abort controller to stop the 
           request if necessary */
        const abortController = new AbortController();
        /* Get a signal from the controller which we'll 
           pass in the request body */
        const signal = abortController.signal;
   
        /* Set up the post data */
        const data = new FormData();
        data.append("searchURL", searchURL.value);
        data.append("searchText", searchText.value);
        /* Send the patterns */
        data.append("patternBody", bodyPattern.value);
        data.append("patternItem", itemPattern.value);
        data.append("patternTitle", titlePattern.value);
        data.append("patternPage", pagePattern.value);
        data.append("patternHash", hashPattern.value);
        data.append("patternSize", sizePattern.value);
        data.append("patternLeeches", leechesPattern.value);
        data.append("patternSeeds", seedsPattern.value);
        data.append("patternDate", datePattern.value);
        data.append("patternDownload", downloadPattern.value);
        data.append("patternCategory", categoryPattern.value);
        /* Send the "use details page" flags */
        data.append("patternTitleUsePage", titlePatternUsePage.checked);
        data.append("patternHashUsePage", hashPatternUsePage.checked);
        data.append("patternSizeUsePage", sizePatternUsePage.checked);
        data.append("patternLeechesUsePage", leechesPatternUsePage.checked);
        data.append("patternSeedsUsePage", seedsPatternUsePage.checked);
        data.append("patternDateUsePage", datePatternUsePage.checked);
        data.append("patternDownloadUsePage", downloadPatternUsePage.checked);
        data.append("patternCategoryUsePage", categoryPatternUsePage.checked);
        /* Pass the proxy settings */
        data.append("proxyEnable", moduleUseProxy.checked);
        data.append("proxyURL", proxyURL.value);
        /* Disable the cache */
        data.append("cache", useCache.checked);
        /* For the test, always limit results */
        data.append("maxResults", maxResults);
        /* Run the test by POST'ing to the php test script */
        try {
            runTest(data, signal);
        } catch (err) {
            /* Show an error if the test failed */
            testResultsLoadingSpinner.classList.add('d-none');
            testDLMResults.innerHTML = '<p>Error</p><p>'+err+'</p>';
        }
    });
}