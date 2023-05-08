# Serve assets from CDN in the Email by Leuchtfeuer

## Install
1. Install bundle into MauticCdnBundle directory or via composer.
2. Make sure the installation has the `symfony/dom-crawler` library, if you install not with composer.
3. Enable bundle.

## How it works
The bundle provides a subscriber to the `EmailEvents::EMAIL_PRE_SAVE`, which is being called
when the Mautic saves Email. The subscriber searches for the `site_url` mautic config value in the email
and replaces it with the value given in the plugin configuration script. You must limit the extensions of
the files served from CDN in the `Extensions` config option of the plugin.

If there is a file in the _Dynamic content_ that should be served from the CDN it will not be replaced.
