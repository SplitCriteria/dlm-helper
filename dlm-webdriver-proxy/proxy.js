const { By, Builder } = require("selenium-webdriver");

/**
 * Fetches a url using the webdriver through chrome on 
 * the docker bridge network
 * 
 * @returns 
 */
async function fetch(
      url, 
      remote = 'http://selenium-webdriver:4444') {
  /* Build a webdriver for chrome and set it to the 
     appropriate server -- the server name is the 
     docker container's name */
  let driver;
  try {
    driver = new Builder()
      .usingServer(remote)
      .forBrowser('chrome')
      .build();
    /* Get the webpage and pass back the innerHTML of the body */
    console.log('Getting URL: '+url)
    await driver.get(url);
    const body = await driver.findElement(By.css('html'));
    const text = await body.getAttribute('innerHTML');
    return text;
  } catch (err) {
    console.log('Error: ', err);
    throw err;
  } finally {
    /* Release the driver */
    if (driver) {
      await driver.quit();
    }
  }
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
    try {
      /* The body contains the POST data in JSON format */
      res.statusCode = 200;
      res.setHeader('Content-Type', 'text/html');
      /* Extract the expected form data */
      const matches = body.matchAll(/form-data; name="([^"]*)"\s*([^\r\n]*)/g);
      const post = { };
      for (const match of matches) {
        if (match && match.length > 2) {
          post[match[1]] = match[2];
        }
      }
      /* Check for the POST'd url */
      if (post['url']) {
        /* If found, then fetch and return the URL */
        res.statusCode = 200;
        res.setHeader('Content-Type', 'text/html');
        res.end(await fetch(post['url'], post['remote']));
      } else {
        /* If not found, then return a status message */
        req.statusCode = 200;
        res.setHeader('Content-Type', 'application/json');
        /* Include the body and parsed post parameters */
        let status = {
          "status": "ok",
          "body": body,
          "post": post,
          "error": null
        };
        res.end(JSON.stringify(status));
      }
    } catch (err) {
      req.statusCode = 400;
      res.setHeader('Content-Type', 'application/json');
      let status = {
        "status": "error",
        "body": body,
        "error": err
      }
      res.end(JSON.stringify(status));
    }
  });
});

/* Start the server */
server.listen(port, hostname, () => {
  console.log(`Server running at http://${hostname}:${port}/`);
});