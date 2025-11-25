# Questions and Answers

## Site Unreachable

If you see "The webpage is not available", "We have trouble finding that site".

![Image](../img/prob1.png)  

Answer: Your local webserver (Apache) is not running.
Please start it via EasyPHP or MAMP control program/panel.

## Blank page

You PHP version is probably too low. Try to use PHP 8 at least.

## URL not found (404)

![Image](../img/prob2.png)  

Answer: The server is running, but the application is not found.
Maybe the Uniform Resource Identifier (URI) is wrong or misspelled.
Please check/correct it. Or the URI is correct, and the application is installed,
but not in the correct directory _lwt_ below _htdocs_.
Please install/copy/move it into the correct directory.  

## Database connection error

![Image](../img/prob3.png)  

Answer: Either the database (MySQL/MariaDB) is not running, or the database connection
parameters in _../htlwt/connect.inc.php_ are wrong.
Please check/correct the database connection parameters and/or start MySQL via the MAMP or EasyPHP control program/panel.  

## Cannot find connect.inc.php

![Image](../img/prob4.png)  

Answer: The Webserver and the database is running, but the database connection parameter file _../htlwt/connect.inc.php_ is not found.
Please rename one of the connection files (according to your server) to _../htlwt/connect.inc.php_.  

## Do not run on Linux after installation/update

If LWT installed or updated LWT on Linux, but the application does not run as expected.

Answer 1: The Webserver does not have full access to all LWT files (insufficient rights).
Open a terminal window, go to the directory where the directory "lwt" has been created with all LWT files, e. g.  
**cd /var/www/html**  
Now execute:
**sudo chmod -R 755 lwt**.  

Answer 2: The PHP "mbstring" extension is not installed.
Please install it: [see this article](https://askubuntu.com/questions/491629/how-to-install-php-mbstring-extension-in-ubuntu).

## MeCab not detected

LWT cannot find MeCab, you can do the following steps:

On Linux or Mac:

1. Open a terminal.
2. Type `mecab -v` to get the current MeCab version. If nothing is displayed MeCab is not installed.
3. If MeCab is already installed, the path may be missing. If you are using MAMP type  

```bash
printf 'export PATH="/usr/local/sbin:/usr/local/bin:/usr/bin:/bin:/usr/sbin:/sbin"' >> /Applications/MAMP/Library/bin/envvars
```
