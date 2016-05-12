# How to install the unofficial schema.org test UI

## Clone from GitHub

First, get the current source code from GitHub:

```
$ git clone https://github.com/digicol/schemaorg-test-ui.git
```

## Download jQuery, Bootstrap etc. via Composer

Download Composer if you haven't got it already:

```
$ curl -sS https://getcomposer.org/installer | php
$ sudo mv composer.phar /usr/local/bin/composer
```

Then run `composer install`. It'll prompt you for some Symfony configuration stuff which you can simply skip with `Enter`:

```
$ cd /path/to/schemaorg-test-ui
$ composer install
```

## Run the Web server

### Using PHP’s internal Web server

The easiest way to run the Web app is to use PHP’s built-in Web server.
See also the [Symfony “How to Use PHP's built-in Web Server” docs](http://symfony.com/doc/current/cookbook/web_server/built_in.html).

If you’re working on localhost, run it like this:

```
$ cd /path/to/schemaorg-test-ui
$ php bin/console server:run
```

If you’re working on a VM, add `0.0.0.0` and a port number so you’re able to access the
Web app from your machine:

```
$ cd /path/to/schemaorg-test-ui
$ php bin/console server:run 0.0.0.0:8000
```

### Using the Apache Web server

Of course, you can also run the Web app on the Apache Web server.
Here’s just some quick instructions, see the [Symfony “Configuring a Web Server” docs](https://symfony.com/doc/current/cookbook/configuration/web_server_configuration.html) docs for more info.

Per the [Symfony “Checking Symfony Application Configuration and Setup - Setting up Permissions” docs](http://symfony.com/doc/current/book/installation.html#checking-symfony-application-configuration-and-setup),
you need to make sure the `var` directory is writable by both command line and Web server user.

On CentOS 7, these commands should do the job:

```
$ cd /path/to/schemaorg-test-ui
$ HTTPDUSER=`ps axo user,comm | grep -E '[a]pache|[h]ttpd|[_]www|[w]ww-data|[n]ginx' | grep -v root | head -1 | cut -d\  -f1`
$ sudo setfacl -R -m u:"$HTTPDUSER":rwX -m u:`whoami`:rwX var
$ sudo setfacl -dR -m u:"$HTTPDUSER":rwX -m u:`whoami`:rwX var
```

Now copy the example Apache configuration include file `httpd.inc.conf.dist`:

```
$ cd /path/to/schemaorg-test-ui
$ cp httpd.inc.conf.dist httpd.inc.conf
```

Include your copy in the Apache configuration. On CentOS 7, add this line to `/etc/httpd/conf/httpd.conf`:

```
Include /path/to/schemaorg-test-ui/httpd.inc.conf
```

Now restart Apache. On CentOS 7:

```
$ sudo systemctl reload httpd.service
```