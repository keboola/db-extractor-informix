# my-component

[![Build Status](https://travis-ci.com/keboola/my-component.svg?branch=master)](https://travis-ci.com/keboola/my-component)

> Fill in description

# Usage

> fill in usage instructions

## Development
 
Create `.env` file with AWS credentials. They are needed to download the driver from the `keboola-drivers` bucket.
```
AWS_ACCESS_KEY_ID=...
AWS_SECRET_ACCESS_KEY=...
```
 
Clone this repository and init the workspace with following command:

```
git clone https://github.com/keboola/db-extractor-informix
cd my-component
docker-compose build
docker-compose run --rm dev composer install --no-scripts
```

Run the test suite using this command:

```
docker-compose run --rm dev composer tests
```
 
# Integration

For information about deployment and integration with KBC, please refer to the [deployment section of developers documentation](https://developers.keboola.com/extend/component/deployment/) 