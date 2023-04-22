/* Creates an empty DLM datum */
function createEmptyDLMDatum() {
    return {
        'moduleName': '',
        'displayName': 'My New DLM',
        'description': '',
        'version': '0.1',
        'url': '',
        'searchText': '',
        'accountSupport': false,
        'maxResults': 0,
        'bodyPattern': '',
        'itemPattern': '',
        'pagePattern': '/\\/torrent[^"]*/',
        'titlePattern': '',
        'titlePatternUsePage': false,
        'downloadPattern': '/magnet:\\?xt=urn:btih:[^"]*/',
        'downloadPatternUsePage': false,
        'datePattern': '',
        'datePatternUsePage': false,
        'sizePattern': '/\\s*(\\d+(?:\\.\\d+)?\\s*(?:KB|MB|GB|TB))/i',
        'sizePatternUsePage': false,
        'seedsPattern': '',
        'seedsPatternUsePage': false,
        'leechesPattern': '',
        'leechesPatternUsePage': false,
        'categoryPattern': '',
        'categoryPatternUsePage': false,
        'hashPattern': '/magnet:\\?xt=urn:btih:(\\w{40})/',
        'hashPatternUsePage': false
    };
}

/* Get a unique module name by appending the version */
function getUniqueModuleName(datum) {
    return datum['moduleName'] + '_v_' + datum['version'];
}

/* Get a unique display name by appending the version */
function getUniqueDisplayName(datum) {
    return datum['displayName'] + ' v' + datum['version'];
}

/* Refreshes the select element in the navigation bar which 
   shows the user the unique display names of the DLM which
   they are working */
function refreshModuleSelect(data, index) {
    /* Remove all options in the select element */
    selectDLM.innerHTML = '';
    /* Add the options one by one and add the selected attribute
       to the current index */
    let option = null;
    for (let i = 0; i < data.length; i++) {
        option = document.createElement('option');
        /* Set the value to the index */
        option.value = i;
        /* Show a unique display name */
        option.innerText = getUniqueDisplayName(data[i]);
        /* Make sure to select the desired index */
        if (index == i) {
            option.setAttribute('selected', 'selected');
        }
        /* Append the child to the select list */
        selectDLM.appendChild(option);
    }
}

/* Loads DLM data into the user form */
function loadDLMDatum(datum) {
    moduleName.value = datum['moduleName'];
    moduleDisplayName.value = datum['displayName'];
    moduleDescription.value = datum['description'];
    moduleVersion.value = datum['version'];
    moduleAccountSupport.checked = datum['accountSupport'];
    moduleMaxResults.value = datum['maxResults'];
    searchURL.value = datum['url'];
    searchText.value = datum['searchText'];
    bodyPattern.value = datum['bodyPattern'];
    itemPattern.value = datum['itemPattern'];
    pagePattern.value = datum['pagePattern'];
    titlePattern.value = datum['titlePattern'];
    titlePatternUsePage.checked = datum['titlePatternUsePage'];
    downloadPattern.value = datum['downloadPattern'];
    downloadPatternUsePage.checked = datum['downloadPatternUsePage'];
    datePattern.value = datum['datePattern'];
    datePatternUsePage.checked = datum['datePatternUsePage'];
    sizePattern.value = datum['sizePattern'];
    sizePatternUsePage.checked = datum['sizePatternUsePage'];
    seedsPattern.value = datum['seedsPattern'];
    seedsPatternUsePage.checked = datum['seedsPatternUsePage'];
    leechesPattern.value = datum['leechesPattern'];
    leechesPatternUsePage.checked = datum['leechesPatternUsePage'];
    categoryPattern.value = datum['categoryPattern'];
    categoryPatternUsePage.checked = datum['categoryPatternUsePage'];
    hashPattern.value = datum['hashPattern'];
    hashPatternUsePage.checked = datum['hashPatternUsePage'];
    /* Call a change listener on the searchURL which
       will trigger a change int he urlSource and start
       the pattern matching sequence */
    searchURL.dispatchEvent(new Event('change'));
}

/* Attaches listeners to buttons and form inputs which enable:
       - Loading and saving DLM data
       - Updating the select options for DLMs
       - Creating new DLMs
       - Deleting DLMs
*/
function setupSaveLoad() {

    const dlmCurrentKey = 'dlm_current';
    const dlmDataKey = 'dlm_data';

    /* Get current data from local storage */
    let data = JSON.parse(localStorage.getItem(dlmDataKey));
    let index = localStorage.getItem(dlmCurrentKey);
    /* If there's no data, then create an array with default data */
    if (!data || data.length === 0) {
        data = [];
        data.push(createEmptyDLMDatum());
        index = 0;
    } else {
        /* Make sure the index is within bounds */
        if (index >= data.length) {
            index = data.length-1;
        }
    }

    /* Refresh the module selection in the nav bar */
    refreshModuleSelect(data, index);

    /* Track the current datum we're editing */
    let datum = data[index];

    /* Load the data into the form */
    loadDLMDatum(datum);

    /* Record any changes to the patterns */
    const saveData = () => {
        datum['moduleName'] = moduleName.value;
        datum['displayName'] = moduleDisplayName.value;
        datum['description'] = moduleDescription.value;
        datum['version'] = moduleVersion.value;
        datum['url'] = searchURL.value;
        datum['searchText'] = searchText.value;
        datum['accountSupport'] = moduleAccountSupport.checked;
        datum['maxResults'] = moduleMaxResults.value;
        datum['bodyPattern'] = bodyPattern.value;
        datum['itemPattern'] = itemPattern.value;
        datum['pagePattern'] = pagePattern.value;
        datum['titlePattern'] = titlePattern.value;
        datum['titlePatternUsePage'] = titlePatternUsePage.checked;
        datum['downloadPattern'] = downloadPattern.value;
        datum['downloadPatternUsePage'] = downloadPatternUsePage.checked;
        datum['datePattern'] = datePattern.value;
        datum['datePatternUsePage'] = datePatternUsePage.checked;
        datum['sizePattern'] = sizePattern.value;
        datum['sizePatternUsePage'] = sizePatternUsePage.checked;
        datum['seedsPattern'] = seedsPattern.value;
        datum['seedsPatternUsePage'] = seedsPatternUsePage.checked;
        datum['leechesPattern'] = leechesPattern.value;
        datum['leechesPatternUsePage'] = leechesPatternUsePage.checked;
        datum['categoryPattern'] = categoryPattern.value;
        datum['categoryPatternUsePage'] = categoryPatternUsePage.checked;
        datum['hashPattern'] = hashPattern.value;
        datum['hashPatternUsePage'] = hashPatternUsePage.checked;
        /* Save the entire data set as a JSON string */
        localStorage.setItem(dlmDataKey, JSON.stringify(data));
        /* Save the current index */
        localStorage.setItem(dlmCurrentKey, index);
    };
    /* Create a function which updates the module selection
       if the parts of the data which make up the unique display
       name change */
    const updateModuleSelect = () => {
        /* Refresh the module selection in the nav bar */
        refreshModuleSelect(data, index);
    };
    moduleName.addEventListener('input', saveData);
    moduleDisplayName.addEventListener('input', saveData);
    moduleDisplayName.addEventListener('input', updateModuleSelect);
    moduleDescription.addEventListener('input', saveData);
    moduleVersion.addEventListener('input', saveData);
    moduleVersion.addEventListener('input', updateModuleSelect);
    moduleAccountSupport.addEventListener('input', saveData);
    moduleMaxResults.addEventListener('input', saveData);
    searchURL.addEventListener('input', saveData);
    searchText.addEventListener('input', saveData);
    bodyPattern.addEventListener('input', saveData);
    itemPattern.addEventListener('input', saveData);
    pagePattern.addEventListener('input', saveData);
    titlePattern.addEventListener('input', saveData);
    titlePatternUsePage.addEventListener('input', saveData);
    downloadPattern.addEventListener('input', saveData);
    downloadPatternUsePage.addEventListener('input', saveData);
    datePattern.addEventListener('input', saveData);
    datePatternUsePage.addEventListener('input', saveData);
    sizePattern.addEventListener('input', saveData);
    sizePatternUsePage.addEventListener('input', saveData);
    seedsPattern.addEventListener('input', saveData);
    seedsPatternUsePage.addEventListener('input', saveData);
    leechesPattern.addEventListener('input', saveData);
    leechesPatternUsePage.addEventListener('input', saveData);
    categoryPattern.addEventListener('input', saveData);
    categoryPatternUsePage.addEventListener('input', saveData);
    hashPattern.addEventListener('input', saveData);
    hashPatternUsePage.addEventListener('input', saveData);

    /* Handle creating a new DLM */
    createDLM.addEventListener('click', () => {
        data.push(createEmptyDLMDatum());
        index = data.length - 1;
        datum = data[index];
        refreshModuleSelect(data, index);
        loadDLMDatum(datum);
    });

    /* Update the form when another DLM is selected */
    selectDLM.addEventListener('change', () => {
        index = selectDLM.value;
        datum = data[index];
        loadDLMDatum(datum);
        saveData();
    });

    /* Handle deleting an existing DLM */
    deleteDLM.addEventListener('click', () => {
        /* Remove the current DLM from the array */
        data.splice(index, 1);
        /* Create an new DLM if there are none left */
        if (data.length == 0) {
            data.push(createEmptyDLMDatum());
            index = 0;
        } else {
            /* Select the previous index */
            if (index > 0) {
                index = index - 1;
            }
        }
        /* Refresh the select element and load the form */
        datum = data[index];
        refreshModuleSelect(data, index);
        loadDLMDatum(datum);
        /* Save the data to make it permanent */
        saveData();
    });
}