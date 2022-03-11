> Currently looking for tester to provide feedback/confirm everything is working as expected before tagging an official release.

<p align="center">
	<h1 align="center">CreativeSizzle.Redirect</h1>
</p>

<p align="center">
	<em>Manage all your HTTP redirects with an easy to use GUI. This is an essential SEO plugin.</em>
</p>

<p align="center">
	<img src="https://badgen.net/packagist/php/creative-sizzle/wn-redirect-plugin">
	<img src="https://badgen.net/packagist/license/creative-sizzle/wn-redirect-plugin">
	<img src="https://badgen.net/packagist/v/creative-sizzle/wn-redirect-plugin/latest">
	<img src="https://badgen.net/badge/cms/Winter%20CMS">
	<img src="https://badgen.net/badge/type/plugin">
</p>

> This is a fork of https://github.com/vdlp/oc-redirect-plugin plugin to make it work with Winter CMS and add additional functionality not present in the original plugin.

## The #1 Redirect plugin for Winter CMS

This is the best Redirect-plugin for Winter CMS. With this plugin installed you can manage redirects directly from Winter CMS' beautiful interface. Many webmasters and SEO specialists use redirects to optimise their website for search engines. This plugin allows you to manage such redirects with a nice and user-friendly interface.

## History

This plugin was originally build in 2016 by Alwin Drenth a Software Engineer at Van der Let & Partners.
As of 2018 this plugin is re-distributed to the October CMS Marketplace with vendor name Vdlp.Redirect (formerly known as Adrenth.Redirect).
As of 2022 this plugin is re-distributed to the Winter CMS Marketplace with vendor name CreativeSizzle.Redirect (formerly Vdlp.Redirect).

The Redirect plugin will now be maintained by Creative Sizzle and You (the open source community).

## What does this plugin offer?

This plugin adds a 'Redirects' section to the main menu of Winter CMS. This plugin has a unique and fast matching algorithm to match your redirects before your website is being rendered.

## Features

* **Quick** matching algorithm
* A **test** utility for redirects
* Matching using **placeholders** (dynamic paths)
* Matching using **regular expressions**
* **Exact** path matching
* **Importing** and **exporting** redirect rules
* **Schedule** redirects (e.g. active for 2 months)
* Redirect to **external** URLs
* Redirect to **internal** CMS pages
* Redirect to relative or absolute URLs
* Redirect **log**
* **Categorize** redirects
* **Statistics**
    * Hits per redirect
    * Popular redirects per month (top 10)
    * Popular crawlers per month (top 10)
    * Number of redirects per month
    * And more...
* Multilingual ***(Need help translating!***
* Supports MySQL, SQLite and Postgres
* HTTP status codes 301, 302, 303, 404, 410
* Caching

## Supported database platforms

* MySQL
* Postgres
* SQLite

## Requirements

* Winter CMS 1.1 or higher.
* PHP version 7.4 or higher.
* PHP extensions: `ext-curl`, `ext-intl`, and `ext-json`.

## Supported HTTP status codes

* `HTTP/1.1 301 Moved Permanently`
* `HTTP/1.1 302 Found`
* `HTTP/1.1 303 See Other`
* `HTTP/1.1 404 Not Found`
* `HTTP/1.1 410 Gone`

## Supported HTTP request methods

* `GET`
* `POST`
* `HEAD`

## Performance

All redirects are stored in the database and will be automatically "published" to a file which the internal redirect mechanism uses to determine if a certain request needs to be redirected. This is way faster than querying a database.

This plugin is designed to be fast and should have no negative effect on the performance of your website.

To gain maximum performance with this plugin:

* Enable redirect caching using a "in-memory" caching method (see Caching).
* Maintain your redirects frequently to keep the number of redirects as low as possible.
* Try to use placeholders to keep your number of redirect low (less redirects is better performance).

## Caching

If your website has a lot of redirects it is recommended to enable redirect caching. You can enable redirect caching in the settings panel of this plugin.

Only cache drivers which support tagged cache are supported. So driver `file` and `database` are not supported. For this plugin database and file caching do not increase performance, but can actually have a negative influence on performance. So it is recommended to use an in-memory caching solution like `memcached` or `redis`.

### How caching works

If caching is enabled (and supported) every request which is handled by this plugin will be cached. It will be stored with tag `CreativeSizzle.Redirect`.

When you modify a redirect all redirect cache will be invalidated automatically. It is also possible to manually clear the cache using the 'Clear cache' button in the Backend.

## Placeholders

This plugin makes advantage of the `symfony/routing` package. So if you need more info on how to make placeholder requirements for your redirection URLs, please go to: https://symfony.com/doc/current/components/routing/introduction.html#usage

## Contribution

Please feel free to [contribute](https://github.com/creative-sizzle/wn-redirect-plugin) to this awesome plugin.
