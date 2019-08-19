# intercept-pantheon-upstream
Pantheon upstream for Intercept. This is based largely on https://github.com/pantheon-systems/example-drops-8-composer

## Why a new upstream
This allows Aten to pull in various tools and have everything in one starting place.

## Creating a site on Pantheon with this upstream

1. Create the site on pantheon
1. Select the Aten D8 Composer Upstream option
1. After that runs, clone the repo locally
1. Inside the repo, run: `composer install && composer update`
1. Switch the Pantheon site to SFTP mode.
1. Go to the Pantheon site and install the site.
1. Switch the site back to GIT mode.

#### Aten D8 Install Profile

The `composer` branch does not have any modules by default b/c composer adds them for us.

`git subtree pull --prefix web/profiles/aten_d8 git@github.com:AtenDesignGroup/aten-d8-profile.git composer --squash`

#### Aten Utils
`git subtree pull --prefix utils/aten-gulp git@github.com:AtenDesignGroup/aten-gulp.git drupal-8 --squash`

Checkout http://blogs.atlassian.com/2013/05/alternatives-to-git-submodule-git-subtree/ for more info on git subtrees.

# README from Example Drops 8 Composer

This repository can be used to set up a Composer-Managed Drupal 8 site on [Pantheon](https://pantheon.io).

[![CircleCI](https://circleci.com/gh/pantheon-systems/example-drops-8-composer.svg?style=svg)](https://circleci.com/gh/pantheon-systems/example-drops-8-composer)

## Installation

This project can either be used as an upstream repository, or it can be set up manually. It is also possible to set up this site as described in the [Terminus Build Tools Plugin](https://github.com/pantheon-systems/terminus-build-tools-plugin) documentations.

### As an Upstream

Create a custom upstream for this project following the instructions in the [Pantheon Custom Upstream documentation](https://pantheon.io/docs/custom-upstream/). When you do this, Pantheon will automatically run composer install to populate the web and vendor directories each time you create a site.

### Manual Setup

Start off by creating a new Drupal 8 site; then, before installing Drupal, set your site to git mode and do the following from your local machine:
```
$ composer create-project pantheon-systems/example-drops-8-composer my-site
$ cd my-site
$ composer prepare-for-pantheon
$ git init
$ git add -A .
$ git commit -m "web and vendor directory from composer install"
$ git remote add origin ssh://ID@ID.drush.in:2222/~/repository.git
$ git push --force origin master
```
Replace my-site with the name that you gave your Pantheon site, and replace ssh://ID@ID.drush.in:2222/~/repository.git with the URL from the middle of the SSH clone URL from the Connection Info popup dialog on your dashboard.

### Installing Drupal

Note that this example repository sets the installation profile to 'standard' in settings.php, so that the installer will not need to modify the settings file. If you would like to install a different profile, modify settings.php appropriately before installing your site.

## Updating Your Site

When using this repository to manage your Drupal 8 site, you will no longer use the Pantheon dashboard to update your Drupal version. Instead, you will manage your updates using Composer. Updates can be applied either directly on Pantheon, by using Terminus, or on your local machine.

### Update with Terminus

Install [Terminus 1](https://pantheon.io/docs/terminus/) and the [Terminus Composer plugin](https://github.com/pantheon-systems/terminus-composer-plugin).  Then, to update your site, ensure it is in SFTP mode, and then run:
```
terminus composer <sitename>.<dev> update
```
Other commands will work as well; for example, you may install new modules using `terminus composer <sitename>.<dev> require drupal/pathauto`.

### Update on your local machine

You may also place your site in Git mode, clone it locally, and then run composer commands from there.  Commit and push your files back up to Pantheon as usual.
