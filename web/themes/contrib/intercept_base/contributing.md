This is the intercept_base base theme which contains all the templates, styles and scripts specific to the Intercept project.

# Tooling

This contains the front-end compiling tools needed for this site.

## Installation
Before proceeding with the installation, it is recommended that you use [nvm](https://github.com/creationix/nvm) to help ensure everyone on the project is using a consistent version of node.js.

1. If you don't have nvm installed, follow these [instructions](https://github.com/creationix/nvm#install-script). Windows users may need to use [nvm-windows](https://github.com/coreybutler/nvm-windows) instead.
1. `nvm install` This will install the version of Node that's defined in `.nvmrc`
1. `nvm use` This will set the correct version of node.js by checking this project's `.nvmrc` file.
1. `npm install` This will install the build tool dependencies.

*note: nvm should be the only global dependency needed for this project*

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
npm run compile:watch
```

To cancel the watch process, type `Ctrl+c`


To create a Browsersync proxy server, run:

```
npm run compile:serve
```

To cancel the browsersync server, type `Ctrl+c`


### CSS
To compile CSS run the following command.

```
npm run css
```

To automatically compile JS when files are changed, run:

```
npm run css:watch
```


### JS
To compile JS run the following command.

```
npm run js
```

To automatically compile JS when files are changed, run:

```
npm run js:watch
```

To cancel the watch process, type `Ctrl+c`


### SVG Sprite
Deprecated
