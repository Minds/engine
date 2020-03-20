Minds Engine
============
[![Build Status](http://drone.minds.io/api/badges/Minds/engine-internal/status.svg)](http://drone.minds.io/Minds/engine-internal)

Back-end system for Minds. Please run inside of [the Minds repo](https://github.com/minds/minds).

## Documentation
Documentation for Minds can be found at [minds.org/docs](https://www.minds.org/docs)


## Tasks
Running CLI jobs. They must be run inside a container.

* run ```docker exec -it minds_php-fpm_1 php '/var/www/Minds/engine/cli.php' controller_name task``

Help files and parameters are available for some tasks.

## Default admin user

Minds ships with a local user ready to roll. 
* username: minds
* password: Pa$$w0rd

To enable admin functionality, set 'development_mode' to **true** in your settings.php post installation.

### Syncing the newsfeed

* Make sure you have at least 1 upvote and a hashtag.
* Assuming your container is named 'minds_php-fpm_1'
* run ```docker exec -it minds_php-fpm_1 php '/var/www/Minds/engine/cli.php' suggested sync_newsfeed```inside the php-fpm container

### Environment variables locally

Override environment variables locally by adding them to the ./.env file in the root of engine. The file is ignored and won't pick up your changes. These values
override the settings in settings.php

Prefix the environment variables with MINDS_ENV_. All others are ignored
Suffix the environment variables with the key in Config.php
Nest arrays with {prefix}{key}__subkey__{...}_{Config Key}

You can then manage these ENVs out on the review sites with the [Deployment Guide](https://developers.minds.com/docs/guides/deployment/)

### Running php tests

* Have a fully setup development environment so all the composer dependencies are installed.
* To run all tests: ```bin/phpsec run```
* To run a specific spec, include a specific spec file ```bin/phpspec run Spec/Core/Feeds/Suggested/RepositorySpec.php```
* To run a specific test in a spec, include a specific spec file:line number of the test function: ```bin/phpspec run Spec/Core/Feeds/Suggested/RepositorySpec.php:82```
## Contributing
If you'd like to contribute to the Minds project, check out the [Contribution](https://www.minds.org/docs/contributing.html) section of Minds.org or head right over to the [Minds Open Source Community](https://www.minds.com/groups/profile/365903183068794880).  If you've found or fixed a bug, let us know in the [Minds Help and Support Group](https://www.minds.com/groups/profile/100000000000000681/activity)!

## Security reports
Please report all security issues to [security@minds.com](mailto:security@minds.com).

## License
[AGPLv3](https://www.minds.org/docs/license.html). Please see the license file of each repository.

## Credits
[PHP](https://php.net), [Cassandra](http://cassandra.apache.org/), [Angular2](http://angular.io), [Nginx](https://nginx.com), [Ubuntu](https://ubuntu.com), [OpenSSL](https://www.openssl.org/), [RabbitMQ](https://www.rabbitmq.com/), [Elasticsearch](https://www.elastic.co/), [Cordova](https://cordova.apache.org/), [Neo4j](https://neo4j.com/), [Elgg](http://elgg.org), [Node.js](https://nodejs.org/en/), [MongoDB](https://www.mongodb.com/), [Redis](http://redis.io/), [WebRTC](https://webrtc.org/), [Socket.io](http://socket.io/), [TinyMCE](https://www.tinymce.com/), [Ionic](http://ionicframework.com/), [Requirejs](http://requirejs.org/), [OAuth](http://oauth.net/2/), [Apigen](http://www.apigen.org/)). If any are missing please feel free to add.

___Copyright Minds 2012 - 2020___

Copyright for portions of Minds are held by [Elgg](http://elgg.org), 2013 as part of the [Elgg](http://elgg.org) project. All other copyright for Minds is held by Minds, Inc.
