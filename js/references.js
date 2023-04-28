/* Navigation bar buttons */
const createDLM = document.getElementById('createDLM');
const deleteDLM = document.getElementById('deleteDLM');
const testDLM = document.getElementById('testDLM');
const publishDLM = document.getElementById('publishDLM');
const selectDLM = document.getElementById('selectDLM');
/* Info modal */
const infoModal = document.getElementById('infoModal');
const infoModalBody = document.getElementById('infoModalBody');
/* Settings elements */
const settingsIcon = document.getElementById('settingsIcon');
const useCache = document.getElementById('useCache');
const clearCache = document.getElementById('clearCache');
const proxyURL = document.getElementById('proxyURL');
/* Test result elements */
const testDLMResults = document.getElementById('testDLMResults');
const testResultsLoadingSpinner = document.getElementById('testResultsLoadingSpinner');
/* Metadata form inputs */
const moduleName = document.getElementById('moduleName');
const moduleDisplayName = document.getElementById('moduleDisplayName');
const moduleDescription = document.getElementById('moduleDescription');
const moduleVersion = document.getElementById('moduleVersion');
const moduleAccountSupport = document.getElementById('moduleAccountSupport');
const moduleUseProxy = document.getElementById('moduleUseProxy');
const moduleMaxResults = document.getElementById('moduleMaxResults');
/* The example search URL and search text form inputs */
const searchURL = document.getElementById('searchURL');
const searchText = document.getElementById('searchText');
/* These elements contain the pattern source and resulting matches */
const sourceLoading = document.getElementById('sourceLoading');
const sourceContent = document.getElementById('sourceContent');
const patternMatches = document.getElementById('patternMatches');
const sourceContextBadge = document.getElementById('sourceContextBadge');
const sourceMethodBadge = document.getElementById('sourceMethodBadge');
/* These elements contain the url source and matches (not immediately
   visible to the user */
const urlSource = document.getElementById('urlSource');
const detailsPageSource = document.getElementById('detailsPageSource');
const bodyMatches = document.getElementById('matchesBody');
const itemMatches = document.getElementById('matchesItem');
const pageMatches = document.getElementById('matchesPage');
/* Patterns and their "use page" checkboxes */
const bodyPattern = document.getElementById('patternBody');
const itemPattern = document.getElementById('patternItem');
const pagePattern = document.getElementById('patternPage');
const titlePattern = document.getElementById('patternTitle');
const titlePatternUsePage = document.getElementById('patternTitleUsePage');
const downloadPattern = document.getElementById('patternDownload');
const downloadPatternUsePage = document.getElementById('patternDownloadUsePage');
const datePattern = document.getElementById('patternDate');
const datePatternUsePage = document.getElementById('patternDateUsePage');
const sizePattern = document.getElementById('patternSize');
const sizePatternUsePage = document.getElementById('patternSizeUsePage');
const seedsPattern = document.getElementById('patternSeeds');
const seedsPatternUsePage = document.getElementById('patternSeedsUsePage');
const leechesPattern = document.getElementById('patternLeeches');
const leechesPatternUsePage = document.getElementById('patternLeechesUsePage');
const categoryPattern = document.getElementById('patternCategory');
const categoryPatternUsePage = document.getElementById('patternCategoryUsePage');
const hashPattern = document.getElementById('patternHash');
const hashPatternUsePage = document.getElementById('patternHashUsePage');
/* The accordion collapsable areas */
const collapseBody = document.getElementById('collapseBody');
const collapseItem = document.getElementById('collapseItem');
const collapsePage = document.getElementById('collapsePage');
const collapseTitle = document.getElementById('collapseTitle');
const collapseDownload = document.getElementById('collapseDownload');
const collapseDate = document.getElementById('collapseDate');
const collapseSize = document.getElementById('collapseSize');
const collapseSeeds = document.getElementById('collapseSeeds');
const collapseLeeches = document.getElementById('collapseLeeches');
const collapseCategory = document.getElementById('collapseCategory');
const collapseHash = document.getElementById('collapseHash');