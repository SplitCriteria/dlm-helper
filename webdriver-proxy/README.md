# About
DLM WebDriver fetcher uses a webdriver interface to fetch the HTML of a webpage

# Creating the webdriver proxy
```
# Build the docker image
docker build -t webdriver-proxy .
# Create a Docker network
docker network create dlm-proxy
# Launch the WebDriver proxy on the DLM-Proxy network
docker run -dp 4445:4445 --name webdriver-proxy --network dlm-proxy webdriver-proxy
# Launch the Selenium Chrome container on the DLM-Proxy network
docker run -d -p 4444:4444 -p 7900:7900 --name selenium-webdriver --network dlm-proxy --shm-size="2g" selenium/standalone-chrome
```