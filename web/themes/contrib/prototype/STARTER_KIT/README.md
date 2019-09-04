# Tooling

This contains the front-end compiling tools needed for this site.

## Installation
Before proceeding with the installation, it is recommended that you use [nvm](https://github.com/creationix/nvm) to help ensure everyone on the project is using a consistent version of node.js.

1. If you don't have nvm installed, follow these [instructions](https://github.com/creationix/nvm#install-script). Windows users may need to use [nvm-windows](https://github.com/coreybutler/nvm-windows) instead.
1. `nvm install` This will install the version of Node that's defined in `.nvmrc`
1. `nvm use` This will set the correct version of node.js by checking this project's `.nvmrc` file.
1. `npm install --global yarn` Yarn is a package manager built on top of npm. It's faster than npm and helps ensure each developer is using the same package versions when developing this project. For [Homebrew](http://brew.sh/) users, `brew install yarn` also is an option.
1. `yarn` This installs all the correct packages for this project.
1. `cp config/index.default.js config/index.js` Creates a configuration file
1. Open up the newly created config/index.js file and replace the siteRoot & themeDir variables are set according to your project and make other adjustments as needed.

*note: nvm and yarn should be the only global dependencies needed for this project*


## Usage
At the beginning of each development session, it's recommended to run `nvm use` to ensure you are developing with the correct version of node.

```
nvm use
```


### Development
To compile CSS and JS run the following command.

```
npm run compile
```

To automatically watch CSS and JS source files for changes and compile, run:

```
npm run compile -- --watch
```

To cancel the watch process, type `Ctrl+c`


To create a Browsersync proxy server, run:

```
npm run compile -- --watch --serve
```

To cancel the browsersync server, type `Ctrl+c`


### CSS
To compile JS run the following command.

```
npm run css
```

To automatically compile JS when files are changed, run:

```
npm run css -- --watch
```


### JS
To compile JS run the following command.

```
npm run js
```

To automatically compile JS when files are changed, run:

```
npm run js -- --watch
```

To cancel the watch process, type `Ctrl+c`


### SVG Sprite
Compile SVG icons run the following command. The SVG sprite merges a folder full of sprites into a single SVG.
This task outputs a template to your components directory as well as a standalone svg file in the build directory.

```
npm run svg-sprite
```

To automatically compile SVG sprite when files are changed, run:

```
npm run svg-sprite -- --watch
```

To cancel the watch process, type `Ctrl+c`
