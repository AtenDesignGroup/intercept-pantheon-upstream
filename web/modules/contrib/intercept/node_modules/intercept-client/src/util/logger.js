/* eslint prefer-destructuring: "off" */
const logger = {};
export default logger;

logger.canLog = null;
logger.canApply = null;
logger.canGroup = null;
logger.canError = null;

// define contexts and whether they can console.log or not
// import {LOG} from './../config';
const LOG = false;
logger.debugSettings = LOG;

/**
 * Check if we are in a console capable system
 */
logger.init = () => {
  logger.canLog =
    typeof console !== 'undefined' && typeof console.log !== 'undefined';
  logger.canApply = typeof console.log.apply !== 'undefined';
  logger.canGroup = typeof console.group !== 'undefined';
  logger.canError = typeof console.error !== 'undefined';
};

/**
 * Log a message, taking context and loggability into account.
 */
logger.log = () => {
  let context = 'master';
  const thisArguments = Array.prototype.slice.call(arguments);

  if (logger.canLog === null) {
    logger.init();
  }

  if (arguments.length > 1) {
    if (
      typeof arguments[0] === 'string' &&
      typeof logger.debugSettings[arguments[0]] !== 'undefined'
    ) {
      context = arguments[0];
      thisArguments.shift();
    }
  }

  if (
    typeof logger.debugSettings[context] !== 'undefined' &&
    logger.debugSettings[context]
  ) {
    if (logger.canLog) {
      if (logger.canApply) {
        return console.log(...thisArguments);
      }

      // non-apply version for some browsers (*cough* ie)
      console.log(thisArguments);
    }
  }
};

/**
 * Log a message, taking context and loggability into account.
 */
logger.group = () => {
  let context = 'master';
  const thisArguments = Array.prototype.slice.call(arguments);

  if (logger.canLog === null) {
    logger.init();
  }

  if (arguments.length > 1) {
    if (
      typeof arguments[0] === 'string' &&
      typeof logger.debugSettings[arguments[0]] !== 'undefined'
    ) {
      context = arguments[0];
      thisArguments.shift();
    }
  }

  if (
    typeof logger.debugSettings[context] !== 'undefined' &&
    logger.debugSettings[context]
  ) {
    if (logger.canGroup) {
      // non-apply version for some browsers (*cough* ie)
      console.group(thisArguments);
    }
  }
};

/**
 * Log a message, taking context and loggability into account.
 */
logger.groupEnd = () => {
  let context = 'master';
  const thisArguments = Array.prototype.slice.call(arguments);

  if (logger.canLog === null) {
    logger.init();
  }

  if (arguments.length > 1) {
    if (
      typeof arguments[0] === 'string' &&
      typeof logger.debugSettings[arguments[0]] !== 'undefined'
    ) {
      context = arguments[0];
      thisArguments.shift();
    }
  }

  if (
    typeof logger.debugSettings[context] !== 'undefined' &&
    logger.debugSettings[context]
  ) {
    if (logger.canGroup) {
      // non-apply version for some browsers (*cough* ie)
      console.groupEnd(thisArguments);
    }
  }
};

/**
 * Log a message, taking context and loggability into account.
 */
logger.error = () => {
  let context = 'master';
  const thisArguments = Array.prototype.slice.call(arguments);

  if (logger.canError === null) {
    logger.init();
  }

  if (arguments.length > 1) {
    if (
      typeof arguments[0] === 'string' &&
      typeof logger.debugSettings[arguments[0]] !== 'undefined'
    ) {
      context = arguments[0];
      thisArguments.shift();
    }
  }

  if (
    typeof logger.debugSettings[context] !== 'undefined' &&
    logger.debugSettings[context]
  ) {
    if (logger.canError) {
      // non-apply version for some browsers (*cough* ie)
      console.error(thisArguments);
    }
  }
};
