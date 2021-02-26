# Temporary Plugin Deactivation by Iconic

This is a "must-use" plugin which adds a "Plugins" dropdown to the **frontend admin bar menu** for admin users. 

From here you can click each active plugin to temporarily disable it. Click it again to turn it back on.

> :warning: **Important:** This won't work unless it's installed as a [must-use plugin](https://wordpress.org/support/article/must-use-plugins/).

<img src="https://github.com/iconicwp/iconic-tpd/blob/master/github/plugins-menu.png?raw=true" width="323" height="438" alt="Image of Temporary Plugin Deactivation by Iconic" style="margin: 30px 0;">

## Installation

Either add `iconic-tbp.php` to the `/wp-content/mu-plugins` folder, or if you want to load it from a subfolder like `/wp-content/mu-plugins/iconic-tpd` then add a loader file to `/wp-content/mu-plugins` like so:

```
<?php
/**
 * Plugin Name: Must-Use Loader
 * Description: Load Must-Use plugins in subfolders.
 */

require WPMU_PLUGIN_DIR.'/iconic-tpd/iconic-tpd.php';
```

## Why?

When developing with WordPress, it's a hassle to keep activating and deactivating plugins; especially if you then need to reneter license keys, etc.

This plugin means you can do it from anywhere with a single click.

## Do you accept contributions?

Sure, feel free to submit a PR if anything is missing.