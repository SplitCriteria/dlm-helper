# Download Manager Helper and Tester
This project helps developers analyze, create, and test Download Manager (dlm) files used by Synology's Download Manager. There are two main files users will access in this project:
```
./DLMHelper.sh
```
and
```
php DLMTester.php
```

Sample DLMs are available in the `sampleDLMs/` folder.

## DLMHelper.sh
DLMHelper.sh is a Bash script which allows the user to:

- Unpack an existing DLM file: `./DLMHelper.sh --unpack filename.dlm`
- Create skeleton DLM files (with template code) for development: `./DLMHelper.sh --create`
- Pack DLM files into a DLM package: `./DLMHelper.sh --pack INFO --name my.dlm`

**Note:** Template DLM files are created using the Synology standard which is located on the [Synology website](https://global.download.synology.com/download/Document/DeveloperGuide/DLM_Guide.pdf).

Planned updates:
- [ ] Create --test command which calls DLMTester.php to test directly from DLMHelper.sh

## DLMTester.php
DLMTester.php is a command line PHP program which allows users to test unpacked, or newly created, DLM files (see DLMHelper.sh, above). Once the files have been tested successfully, the user can pack the DLM files using DLMHelper.sh which will be ready for use on Synology's Download Manager.

To test DLM files, enter a search parameter on the command line. The parsed results will be output to stdout. The user will need to examine the results for accuracy.

```
php DLMTester.php -cvs "Search String" DLM_INFO_file
```

Planned updates:
- [x] Validate DLM search file results (e.g. check that fields are present and correct format)
- [ ] Allow testing of unpacked DLM packages
- [ ] Add web interface if DLMTester.php is loaded on a browser
- [x] Add `--cache` option to either download and create a cache, or use a cached webpage, during testing
- [x] Add `--output html` format which allows for easier viewing/testing results

## Creating or Fixing a DLM search module
If your favorite torrent site DLM search module isn't working, or doesn't exist to begin with, then you can create or fix your own without too much trouble.

### Creating a DLM search module
Here are the recommended steps to creating your own DLM search module:
1. Go to the torrent website and determine how you can obtain search results: RSS feed, JSON object (usually through an API), or Website search
2. Make note of the domain and the search address (e.g. domain: "https://targetdomain.com", search address: "/search.php&q=")
3. Create template DLM files uing `./DLMHelper.sh --create`
4. In the interactive menus, use the domain and search address found above
  * RSS feed

    5. In the interactive menus, select RSS parsing

  * JSON object

    5. In the interactive menus, select JSON parsing

  * Website search

    5. In the interactive menus, select manual result parsing
    6. Copy the torrent website source code into a regular expression test bed (e.g. [RegExr](http://regexr.com))
    7. Create a regular expression which parses the results and creates the following groups: title*, torrent link*, webpage link, hash, date, seeds, leeches, and category.   *Necessary fields
    8. Edit the `search.php` file, inserting your regular expression into the $regx variable, then assign the groups numbers to the appropriate array element (e.g. if title is the first group, then replace `$row[#]` with `row[0]`)

After the files are saved, test the output using `php DLMTester.php -s "sample search query" INFO

### Tips
* Use `--cache` during testing to avoid repeated network usage
