Wildfire Local
==============

A little app to help build WildFire apps locally.

What do I need to run it?
=========================

It's just a simple LAMP set up, but the only _requirement_ is PHP.

How'd I get started?
====================

1. Clone the repo, that's pretty standard.
2. Stick your templates in nicely named folders in the `template` folder.
3. Make sure that within your template folder the layout includes `.liquid` files (`default.liquid` as standard) and an `assets` folder. Within the `assets` folder include your `default.css`; this is loaded automatically.
4. Visit your local server, eg: `http://localhost/my_template/default.liquid`.
5. Profit.

What about tags and plugins?
============================

I haven't rewritten the _whole_ WildFire system, but I've allowed you to set variables via `GET` or creating an `assigns.json` file in your template folder. This file is just a simple associative array set up, so any vars you want to sent as constants (such as API keys and Facebook like iframes), set them here, like so:

```json
{
    "foo": "bar"
}
```

Then if you have `{{ foo }}` in your template, it'll be rendered as 'bar'.

This file also allows you to pass one or many stylesheets to the base HTML, just simply create an array called `stylesheets`, like so:

```json
{
    "stylesheets": [
        "retina",
        "facebook"
    ]
}
```

And these'll be added in the `<head>` of the base HTML.

What about support?
===================

Well, there isn't any, really. If you spot any bugs just message me or @ me ([@ahmednuaman](http://twitter.com/ahmednuaman)) on Twitter.