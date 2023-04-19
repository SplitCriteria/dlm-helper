/**
 * Pattern matching is done with respect to how each item is 
 * dependent on one another. A dependency chart is shown below:
 * 
 *                    url source ---
 *                        |        |
 *                       body      |
 *                        |        |
 *                       item ------
 *                        |
 *                     -------
 *                     |     |
 *                    page   |
 *                     |     |
 *      -----------------------------------------------------
 *      |       |      |    |      |       |        |       |
 *  download  title  size  date  seeds  leeches  category  hash
 * 
 * 
 * The source is optionally isolated to a "body" which is
 * separated into multiple items (one for each download result
 * in the source). The details "page" must be derived from each
 * item (if at all). The remaining attributes are derived either
 * from the specific item or from the details page depending on
 * user-preference.
 */

/**
 * Matches a pattern against a source then places the 
 * matches and number of matches in the destination. The
 * parameter `options` contains the following properties:
 * 
 *  pattern     an object with a value property to be used
 *              as a pattern (e.g. RegExp)
 *  source      an object with a value to match against
 *  destination an object, or array of objects, with a 
 *              value property which will contain the matches
 *  matchSource a boolean flag indicating that the source 
 *              value will be forwarded to the destination
 *              if no matches are found or the pattern is
 *              invalid
 *  number      an object with a value property which will 
 *              be set to the # of matches found
 *  pattern, source, and destination are required parameters
 * 
 * Returns an object with the 'success' flag and an 'error'
 * if any exists
*/
function showMatches(options) {
    /* Check the parameter */
    if (options === undefined || typeof options !== 'object' || 
            !options['pattern'] || !options['source']
            || !options['destination']) {
        return { 'success': false, 'error': 'Invalid options' };
    }
    /* If the destination is not an array, then make it one for 
       easier processing below */
    if (!Array.isArray(options['destination'])) {
        options['destination'] = [options['destination']];
    }
    /* Create a return value with a initial success flag set to false */
    let returnValue = { 'success': false };
    /* Create a regex which matches a regex with (optional) flags */
    const patternRegEx = /\/(.*)\/([dgimsuy]*)/;
    /* Only try to match if there's a source value */
    if (options['source'].value) {
        /* Clear out the destination(s) */
        options['destination'].forEach(dst => {
            dst.value = '';
        });
        /* Check that the pattern given is a regex */
        let matches = options['pattern'].value.match(patternRegEx);
        if (matches !== null && matches[1]) {
            /* Log the type of pattern detected */
            returnValue['pattern'] = 'regex';
            /* If it is, then try to compile it */
            try {
                /* Create a regex from the pattern and flags */
                const re = new RegExp(matches[1], matches[2]);
                /* Count the number of matches */
                returnValue['matches'] = [];

                let match = null;
                /* Helper function which adds a match to the
                   destination and return value */
                const addMatch = (match) => {
                    if (match) {
                        /* Append the match to each of the destinations
                           add a newline if there's already text in 
                           present. Use the first group if grouping 
                           exists, otherwise use the whole match 
                           (at index 0). */
                        options['destination'].forEach(dst => {
                            dst.value = dst.value + 
                                (dst.value ? "\n" : "") + 
                                (match.length > 1 ? match[1] : match[0]);
                        });
                        /* Add to the return value */
                        returnValue['matches'].push(match.length > 1 ? match[1] : match[0]);
                    }
                }

                /* If there's not a global flag, do only 1 match */
                if (!matches[2].includes('g')) {
                    if (match = options['source'].value.match(re)) {
                        addMatch(match);
                    }
                } else {
                    /* Otherwise, find all the matches */
                    while (match = re.exec(options['source'].value)) {
                        addMatch(match);
                    }
                }
                /* Define success as having at least 1 match */
                if (returnValue['matches'].length > 0) {
                    returnValue['success'] = true;
                }
            } catch (error) {
                returnValue['error'] = 'Error compiling or matching regex';
            }
        } else {
            returnValue['error'] = 'Unknown pattern type';
        }
    } else {
        returnValue['error'] = 'No source value';
    }

    /* Display the number of matches, if a number destination was passed */
    if (options['number']) {
        if (returnValue['matches'] && returnValue['matches'].length > 0) {
            options['number'].innerText = returnValue['matches'].length;
        } else {
            options['number'].innerText = '';
        }
    }

    /* If there's no match, then log that as an error and copy over
       the source value if desired */
    if (!returnValue['success']) {
        if (options['matchSource']) {
            options['destination'].forEach(dst => {
                dst.value = options['source'].value;
            });
        }
    }

    return returnValue;
}

/**
 * Attaches pattern and match elements to each other to provide
 * real-time matching of user-typed patterns
 */
function setupPatternMatching() {

    /* Add change listeners to the patterns so the matches are updated in real time */
    const updateBodyMatches = () => {
        /* Save the body matches in the bodyMatches element (not displayed)
           and show the matches to the user as well */
        /* Set the source content to the urlSource */
        sourceContent.value = urlSource.value;
        showMatches({ 
            'pattern': bodyPattern, 'source': sourceContent, 
            'destination': [patternMatches, bodyMatches], 
            'number': bodyMatchesCount, 'matchSource': true
        });
        /* After the body is updated, update the item matches but 
           do so "in the background". That is, use the undisplayed 
           body matches as a source and the item matches as the 
           destination -- the user does not see these */
        showMatches({
            'pattern': itemPattern, 'source': bodyMatches, 
            'destination': itemMatches
        });
        /* After the item is updated, then update the page matches but,
           like above, do so to a control not displayed to the user */
        showMatches({
            'pattern': pagePattern, 'source': itemMatches, 
            'destination': pageMatches
        });
        /* Manually call a change to the pageMatches so the a details 
           page load is attempted */
        pageMatches.dispatchEvent(new Event('change'));
    }
    const updateItemMatches = () => {
        /* Save the item matches in the itemMatches element (not displayed)
           and show the matches to the user as well */
        /* Set the source content to the body matches */
        sourceContent.value = bodyMatches.value;
        showMatches({ 
            'pattern': itemPattern, 'source': sourceContent, 
            'destination': [patternMatches, itemMatches], 
            'number': itemMatchesCount, 'matchSource': true
        });
        /* After the item is updated, then update the page matches but,
           like above, do so to a control not displayed to the user */
        showMatches({
            'pattern': pagePattern, 'source': itemMatches, 
            'destination': pageMatches
        });
        /* Manually call a change to the pageMatches so the a details 
           page load is attempted */
        pageMatches.dispatchEvent(new Event('change'));
    }
    const updatePageMatches = () => {
        /* Set the soruce content to the item matches */
        sourceContent.value = itemMatches.value;
        showMatches({ 
            'pattern': pagePattern, 'source': sourceContent, 
            'destination': [patternMatches, pageMatches], 'number': pageMatchesCount
        });
        /* Manually call a change to the pageMatches so the a details 
           page load is attempted */
        pageMatches.dispatchEvent(new Event('change'));
    }
    const updateTitleMatches = () => {
        /* Set the source content to the either the item matches
           or the details page */
        sourceContent.value = titlePatternUsePage.checked ? 
            detailsPageSource.value : itemMatches.value;
        showMatches({ 
            'pattern': titlePattern, 'source': sourceContent, 
            'destination': patternMatches, 'number': titleMatchesCount
        });
    }
    const updateDownloadMatches = () => {
        sourceContent.value = downloadPatternUsePage.checked ? 
            detailsPageSource.value : itemMatches.value;
        showMatches({ 
            'pattern': downloadPattern, 'source': sourceContent, 
            'destination': patternMatches, 'number': downloadMatchesCount
        });
    }
    const updateDateMatches = () => {
        sourceContent.value = datePatternUsePage.checked ? 
            detailsPageSource.value : itemMatches.value;
        showMatches({ 
            'pattern': datePattern, 'source': sourceContent, 
            'destination': patternMatches, 'number': dateMatchesCount
        });
    }
    const updateSizeMatches = () => {
        sourceContent.value = sizePatternUsePage.checked ? 
            detailsPageSource.value : itemMatches.value;
        showMatches({ 
            'pattern': sizePattern, 'source': sourceContent, 
            'destination': patternMatches, 'number': sizeMatchesCount
        });
    }
    const updateSeedsMatches = () => {
        sourceContent.value = seedsPatternUsePage.checked ? 
            detailsPageSource.value : itemMatches.value;
        showMatches({ 
            'pattern': seedsPattern, 'source': sourceContent, 
            'destination': patternMatches, 'number': seedsMatchesCount
        });
    }
    const updateLeechesMatches = () => {
        sourceContent.value = leechesPatternUsePage.checked ? 
            detailsPageSource.value : itemMatches.value;
        showMatches({ 
            'pattern': leechesPattern, 'source': sourceContent, 
            'destination': patternMatches, 'number': leechesMatchesCount
        });
    }
    const updateCategoryMatches = () => {
        sourceContent.value = categoryPatternUsePage.checked ? 
            detailsPageSource.value : itemMatches.value;
        showMatches({ 
            'pattern': categoryPattern, 'source': sourceContent, 
            'destination': patternMatches, 'number': categoryMatchesCount
        });
    }
    const updateHashMatches = () => {
        sourceContent.value = hashPatternUsePage.checked ? 
            detailsPageSource.value : itemMatches.value;
        showMatches({ 
            'pattern': hashPattern, 'source': sourceContent, 
            'destination': patternMatches, 'number': hashMatchesCount
        });
    }
    /* Update all the matches which depend on either the source
       content (i.e. the source > body > items) or the details 
       page */
    const updateDependentMatches = () => {
        updateTitleMatches();
        updateDownloadMatches();
        updateDateMatches();
        updateSizeMatches();
        updateSeedsMatches();
        updateLeechesMatches();
        updateCategoryMatches();
        updateHashMatches();
    }
    /* When the source content is loaded or the body pattern 
       is updated then update the body matches */
    urlSource.addEventListener('input', updateBodyMatches);
    bodyPattern.addEventListener('input', updateBodyMatches);
    /* When the body matches are provided/matched or the item 
       pattern is updated then update individual item matches */
    bodyMatches.addEventListener('input', updateItemMatches);
    itemPattern.addEventListener('input', updateItemMatches);
    /* All the remaining patterns apply to either the items,
       taken from the original source, or the details page. 
       So, when the item matches are updated, trigger an 
       update to all matches. */
    itemMatches.addEventListener('input', updatePageMatches);
    itemMatches.addEventListener('input', updateDependentMatches);
    /* When the remaining patterns are changed then update 
       their individual matches */
    titlePattern.addEventListener('input', updateTitleMatches);
    downloadPattern.addEventListener('input', updateDownloadMatches);
    datePattern.addEventListener('input', updateDateMatches);
    sizePattern.addEventListener('input', updateSizeMatches);
    seedsPattern.addEventListener('input', updateSeedsMatches);
    leechesPattern.addEventListener('input', updateLeechesMatches);
    categoryPattern.addEventListener('input', updateCategoryMatches);
    hashPattern.addEventListener('input', updateHashMatches);
    pagePattern.addEventListener('input', updatePageMatches);
    /* When the pattern "use page" checkbox is checked we'll update
       the matches as well since the source content is changing */
    titlePatternUsePage.addEventListener('input', updateTitleMatches);
    downloadPatternUsePage.addEventListener('input', updateDownloadMatches);
    datePatternUsePage.addEventListener('input', updateDateMatches);
    sizePatternUsePage.addEventListener('input', updateSizeMatches);
    seedsPatternUsePage.addEventListener('input', updateSeedsMatches);
    leechesPatternUsePage.addEventListener('input', updateLeechesMatches);
    categoryPatternUsePage.addEventListener('input', updateCategoryMatches);
    hashPatternUsePage.addEventListener('input', updateHashMatches);

    /* When the specific pattern is selected in the accordion we'll 
       update and show the matches */
    collapseBody.addEventListener('shown.bs.collapse', updateBodyMatches);
    collapseItem.addEventListener('shown.bs.collapse', updateItemMatches);
    collapsePage.addEventListener('shown.bs.collapse', updatePageMatches);
    collapseTitle.addEventListener('shown.bs.collapse', updateTitleMatches);
    collapseDownload.addEventListener('shown.bs.collapse', updateDownloadMatches);
    collapseDate.addEventListener('shown.bs.collapse', updateDateMatches);
    collapseSize.addEventListener('shown.bs.collapse', updateSizeMatches);
    collapseSeeds.addEventListener('shown.bs.collapse', updateSeedsMatches);
    collapseLeeches.addEventListener('shown.bs.collapse', updateLeechesMatches);
    collapseCategory.addEventListener('shown.bs.collapse', updateCategoryMatches);
    collapseHash.addEventListener('shown.bs.collapse', updateHashMatches);
}