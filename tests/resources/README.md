## This folder contains HTML files used in testing 

### JSON-LD only:

* `jsonld-email.html` - A basic email containing JSON-LD markup.

* `jsonld-website.html` - A basic website containing JSON-LD markup.

* `jsonld-website-broken-tag.html` - The same HTML as above, only missing a closing `</body>` tag.

* `jsonld-article-zeit.html` - Article from zeit.de

* `jsonld-website-real-example.html` - HTML pulled from a blog article using cURL. Has multiple JSON-LD elements stored in a single tag. (Source: https://moz.com/blog/json-ld-for-beginners)

## Microdata only:

* `microdata-email.html` - A basic email containing Microdata markup, separated from the actual body.

* `microdata-inline-email.html` - A basic email containing Microdata markup, inline with the body.

* `microdata-inline-website.html` - A basic website containing Microdata markup, inline with the body.

##  Related to other projects

* `cookbook-issue-hello-fresh.html` - HTML pulled from hellofresh.de, which caused an issue in [nextcloud/cookbook](https://github.com/nextcloud/cookbook). See: https://github.com/nextcloud/cookbook/issues/1509.

* `cookbook-issue-marmiton.html` - HTML pulled from marmiton.org, which caused an issue in [nextcloud/cookbook](https://github.com/nextcloud/cookbook). See: https://github.com/nextcloud/cookbook/issues/1592.

* `cookbook-issue-rezeptwelt.html` - HTML pulled from rezeptwelt.de, which caused an issue in [nextcloud/cookbook](https://github.com/nextcloud/cookbook). See: https://github.com/nextcloud/cookbook/issues/1508.



## Miscellaneous:

* `website-without-markup.html` - https://www.example.com - html without any markup.

* `mixed-jsonld-microdata.html` - A real world example of a website containing both JSON-LD tags, and Microdata, as well as having more than one JSON-LD tag. (Source: https://www.zeit.de/zeit-magazin/2023-06/schokolade-tonys-tafel-genuss)