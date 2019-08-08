# WP-GitHub-Plugin-Updater
A simple GitHub Wordpress Plugin Updater which does not use the core updater framework

## Introduction

I have tried several plugins, classes, addons to achive this! Even wrote my own class to handle it using the in-build wordpress transient updater for plugins
none ever worked quite right. 

This does not use the in-build wordpress framework and instead merely inserts a button which shows if a new version is available or allows you to reinstall. 

## Installation

### Option 1 - Using Composer *Recommended*

Add to your plugins main php file.


` /* Include Composer */`

`require(plugin_dir_path(__FILE__) . 'vendor/autoload.php');`

`add_action('admin_init',function(){ new WP_GitHub_Updater(__FILE__); }); `

### Option 2 - Include as Class

Download this REPO

Place the updater.php file somewhere within your plugin_dir_path and include the below in your plugins main php file

` require('updater.php');`

`add_action('admin_init',function(){ new WP_GitHub_Updater(__FILE__); });`


## Usage

Your *Plugin URI* in your main functions.php should be set to your full github repo

`
Plugin URI: https://github.com/myrepo/myplugin
`

Whenever you now go into the plugin page, a quick check is made to see if the main plugin file version is greater on your repo. If so it will give you the option to install
Otherwise the option to reinstall is given instead. 






