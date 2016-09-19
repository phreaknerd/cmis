The module use php-cmis-client library but the current version 1.0
depend the guzzle 5 version. Drupal use the guzzle 6 version.
In order to we able using this client we need install it to module vendor
folder.

To install temporary to time when will ready new version of cmis client
you need to go to cmis module root folder (eg. modules/cmis or 
modules/contrib/cmis) and call the next command:
  composer require dkd/php-cmis
