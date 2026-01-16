# Serve assets from CDN in the Email by Leuchtfeuer

### Works with
* Mautic 4
* Mautic 5 

### Install
1. Install bundle into LeuchtfeuerCdnBundle directory or via composer.
2. Make sure the installation has the `symfony/dom-crawler` library, if you install not with composer.
   * For Mautic 4 the `symfony/dom-crawler` library ~4.4.0 (>=4.4.0 <4.5.0) is needed.
   * For Mautic 5 the `symfony/dom-crawler` library ~5.4.0 (>=5.4.0 <5.5.0) is needed.
3. Enable bundle.

### How it works
The bundle provides a subscriber to the `EmailEvents::EMAIL_ON_SEND`, which is being called
when the Mautic sends an Email. The subscriber searches for the `site_url` mautic config value in the email
and replaces it with the value given in the plugin configuration script. You must limit the extensions of
the files served from CDN in the `Extensions` config option of the plugin.

If there is a file in the _Dynamic content_ that should be served from the CDN it will not be replaced.

### Author
Leuchtfeuer Digital Marketing GmbH

mautic-plugins@Leuchtfeuer.com
