const { By, Builder } = require("selenium-webdriver");

/**
 * Takes a string which can contain URLs in 
 * multiple formats (e.g. (un)quoted string, JSON
 * stringified array) and converts it an array 
 * object)
 * 
 * @param data string
 * @return an Array of URL strings
 */
function parseURLs(data) {
  /* Check the parameter; fail if nothing was given
     or the data isn't the expected type */
  if (!data || !(typeof data === 'string')) {
    return false;
  }
  let result;
  /* Try to decode a stringified JSON array first */
  try {
    /* Try to decode the JSON array first */
    result = JSON.parse(data);
    /* If it's an array, then return result; otherwise fail */
    return Array.isArray(result) ? result : false;
  } catch (err) {
    /* Otherwise, assume it's a single URL string and 
       return it as an array */
    return [ data ];
  }
}

/**
 * Fetches a url using the webdriver through chrome on 
 * the docker bridge network
 * 
 * @returns 
 */
async function fetch(
      urls, remote = 'http://selenium-webdriver:4444') {
  /* Build a webdriver for chrome and set it to the 
     appropriate server -- the server name is the 
     docker container's name */
  let driver, body;
  let text = { };
  try {
    /* Create the webdriver for a chrome browser */
    driver = new Builder()
      .usingServer(remote)
      .forBrowser('chrome')
      .build();
    /* Fetch each URL */
    for (let i = 0; i < urls.length; i++) {
      try {
        console.log('Fetching URL: '+urls[i])
        /* Fetch the URL, and save the innerHTML of the HTML tag */
        await driver.get(urls[i]);
        body = await driver.findElement(By.css('html'));
        text[urls[i]] = await body.getAttribute('innerHTML');
      } catch (err) {
        /* If something went wrong, report the error for this
           specific URL */
        text[urls[i]] = err.toString();
      }
    }
  } catch (err) {
    console.log('Error during proxy fetch: ', err);
    throw err;
  } finally {
    /* Release the driver on error */
    if (driver) {
      await driver.quit();
    }
  }
  return JSON.stringify(text);
}
  
const http = require('http');

/* Define the server hostname/port */
const hostname = '0.0.0.0';
const port = 4445;

/* Create a server that fetches a requested URL */
const server = http.createServer(async (req, res) => {
  /* Get the body of the message */
  let body = '';
  req.setEncoding('utf8');
  /* Read in chunks of data; add to the body */
  req.on('data', (chunk) => {
    body += chunk;
  });
  /* Add the end event, convert the body */
  req.on('end', async () => {
    let post = { };
    res.setHeader('Content-Type', 'application/json');
    /* Extract the request from the POST request (form data in body) */
    try {
      const matches = body.matchAll(/form-data; name="([^"]*)"\s*([^\r\n]*)/g);
      for (const match of matches) {
        if (match && match.length > 2) {
          post[match[1]] = match[2];
        }
      }
    } catch (err) {
      /* Oops, an error decoding the POST, return an error
         500 -- internal server error */
      res.statusCode = 400;
      res.end(JSON.stringify({
        "status": "error",
        "request": {
          "body": body,
          "post": post
        },
        "error": "Error decoding POST request: "+err
      }));
    }
    /* Check for the POST'd url */
    if (post['url']) {
      /* Parse the URLs */
      let urls = parseURLs(post['url']);
      if (!urls) {
        /* Return an error for the incorrect URLs passed */
        res.statusCode = 400;
        res.end(JSON.stringify({
          "status": "error",
          "request": {
            "body": body,
            "post": post
          },
          "error": "Error decoding URL(s): "+err
        }));
        return;
      }
      /* Fetch the URL -- include the optional remote webdriver */
      await fetch(urls, post['remote'])
        .then((text) => {
          /* Send the response as HTML */
          res.statusCode = 200;
          res.end(text);
          return;
        })
        .catch((err) => {
          /* Send an error if fetch didn't work */
          res.statusCode = 400;
          res.end(JSON.stringify({
            "status": "error",
            "request": {
              "body": body,
              "post": post
            },
            "error": "Error fetching URL: "+err
          }));
          return;
        });
    } else {
      /* If no URL given just return a status message */
      req.statusCode = 200;
      /* Include the body and parsed post parameters */
      res.end(JSON.stringify({
        "status": "ok",
        "request": {
          "body": body,
          "post": post
        },
        "usage": "POST with \"url\" to fetch with optional \"remote\" address of Chrome Webdriver",
        "error": null
      }));
    }
  });
});

/* Start the server */
server.listen(port, hostname, () => {
  console.log(`Server running at http://${hostname}:${port}/`);
});