# Intercept Pantheon Upstream
Pantheon upstream for Intercept. This will install [Drupal 8](https://drupal.org/project/drupal) with the [Intercept installation profile](https://drupal.org/project/intercept_profile) which includes the [Intercept modules](https://drupal.org/project/intercept) and the [Intercept theme](https://drupal.org/project/intercept_base).  This is the quickest way to get Intercept, its theme, and its dependencies up and running on Pantheon.

## Why This Upstream Exists
Custom upstreams are a convenient way to quickly install and evaluate a new instance of Drupal 8 using Intercept without needing to worry about server requirements, upgrades, or deployment scripts.  Updates to the upstream will automatically become available to your installation on the Pantheon dashboard.

## Requirements
This upstream requires that you have a Pantheon account and the ability to create a new site using an upstream.

## Creating a Site on Pantheon With This Upstream

1. Visit https://dashboard.pantheon.io/sites/create?upstream_id=57c6a03c-45ff-4249-b708-0cef6470c599
2. Enter your site name and region and click "Continue".
![Create website from Pantheon upstream](https://i.imgur.com/vfE2a6y.jpg)
3. Click on "Visit your Pantheon Site Dashboard".
![Visit your Pantheon Site Dashboard](https://i.imgur.com/4ZGnYMu.jpg)
4. In the Pantheon dashboard, navigate to your new website.
![Navigate to the development website](https://i.imgur.com/WzYx8dE.jpg)
5.  Proceed through the installation process.
![Proceed through the installation process](https://i.imgur.com/7DeU32W.jpg)
![Configure site](https://imgur.com/K6EcjvB.jpg)

## Updating Your Site at a Later Date

Updates can be applied either directly on Pantheon, by using Terminus, or on your local machine.

### Update With Terminus

Install [Terminus 2](https://pantheon.io/docs/terminus/) and the [Terminus Composer plugin](https://github.com/pantheon-systems/terminus-composer-plugin).  Then, to update your site, ensure it is in SFTP mode, and then run:
```
terminus composer <sitename>.<dev> update
```
Other commands will work as well; for example, you may install new modules using `terminus composer <sitename>.<dev> require drupal/pathauto`.

### Update on Your Local Machine

You may also place your site in Git mode, clone it locally, and then run composer commands from there.  Commit and push your files back up to Pantheon as usual.
