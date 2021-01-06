import redis from 'redis';
import crypto from 'crypto';

function actionCreator(type) {
  let argNames = new Array();
  for (let _key = 1; _key < arguments.length; _key += 1) {
    argNames[_key - 1] = arguments[_key];
  }

  return function () {
    const _len2 = arguments.length;
    let args = new Array(_len2);
    for (let _key2 = 0; _key2 < _len2; _key2 += 1) {
      args[_key2] = arguments[_key2];
    }

    let action = {
      type,
    };
    argNames.forEach((arg, index) => {
      action[argNames[index]] = args[index];
    });
    return action;
  };
}

const ADD = 'intercept/ADD';
const CLEAR_ERRORS = 'intercept/CLEAR_ERRORS';
const EDIT = 'intercept/EDIT';
const FAILURE = 'intercept/FAILURE';
const MARK_DIRTY = 'intercept/MARK_DIRTY';
const PURGE = 'intercept/PURGE';
const RECEIVE = 'intercept/RECEIVE';
const RECEIVE_TRANSLATION = 'intercept/RECEIVE_TRANSLATION';
const REQUEST = 'intercept/REQUEST';
const RESET = 'intercept/RESET';
const SET_SAVED = 'intercept/SET_SAVED';
const SET_TIMESTAMP = 'intercept/SET_TIMESTAMP';
const SET_VALIDATING = 'intercept/SET_VALIDATING';

const request = actionCreator(REQUEST, 'resource', 'id');
const receive = actionCreator(RECEIVE, 'resp', 'resource', 'id');
const receiveTranslation = actionCreator(RECEIVE_TRANSLATION, 'resp', 'resource', 'langcode', 'id');
const failure = actionCreator(FAILURE, 'error', 'resource', 'id'); // Removes all local items and resets API syncing state;

const purge = actionCreator(PURGE, 'resource'); // Resets API syncing state;

const reset = actionCreator(RESET, 'resource');
const clearErrors = actionCreator(CLEAR_ERRORS, 'resource', 'id');
const setSaved = actionCreator(SET_SAVED, 'value', 'resource', 'id');
const markDirty = actionCreator(MARK_DIRTY, 'resource', 'id');
const setTimestamp = actionCreator(SET_TIMESTAMP, 'resource', 'timestamp');
const setValidating = actionCreator(SET_VALIDATING, 'resource', 'value');
const add = actionCreator(ADD, 'data', 'resource', 'id');
const edit = actionCreator(EDIT, 'data', 'resource', 'id');

const actions = Object.freeze({
  request,
  receive,
  receiveTranslation,
  failure,
  purge,
  reset,
  clearErrors,
  setSaved,
  markDirty,
  setTimestamp,
  setValidating,
  add,
  edit,
});

const NAME = 'intercept'; // Filters

const DATE = 'date';
const DATE_START = 'date--start';
const DATE_END = 'date--end';
const KEYWORD = 'keyword'; // Entity Types

const TYPE_FILE = 'file--file';
const TYPE_SAVED_EVENT = 'flagging--saved_event';
const TYPE_EVENT_RECURRENCE = 'event_recurrence--event_recurrence';
const TYPE_MEDIA_FILE = 'media--file';
const TYPE_MEDIA_IMAGE = 'media--image';
const TYPE_MEDIA_SLIDESHOW = 'media--slideshow';
const TYPE_MEDIA_VIDEO = 'media--web_video';
const TYPE_EQUIPMENT = 'node--equipment';
const TYPE_EQUIPMENT_RESERVATION = 'equipment_reservation--equipment_reservation';
const TYPE_EVENT = 'node--event';
const TYPE_EVENT_ATTENDANCE = 'event_attendance--event_attendance';
const TYPE_EVENT_SERIES = 'node--event_series';
const TYPE_EVENT_REGISTRATION = 'event_registration--event_registration';
const TYPE_LOCATION = 'node--location';
const TYPE_ROOM = 'node--room';
const TYPE_ROOM_RESERVATION = 'room_reservation--room_reservation';
const TYPE_AUDIENCE = 'taxonomy_term--audience';
const TYPE_EQUIPMENT_TYPE = 'taxonomy_term--equipment_type';
const TYPE_EVALUATION_CRITERIA = 'taxonomy_term--evaluation_criteria';
const TYPE_EVENT_TYPE = 'taxonomy_term--event_type';
const TYPE_SUBJECT = 'taxonomy_term--lc_subject';
const TYPE_MEETING_PURPOSE = 'taxonomy_term--meeting_purpose';
const TYPE_POPULATION_SEGMENT = 'taxonomy_term--population_segment';
const TYPE_ROOM_TYPE = 'taxonomy_term--room_type';
const TYPE_TAG = 'taxonomy_term--tag';
const TYPE_USER = 'user--user';

const constants = Object.freeze({
  NAME,
  DATE,
  DATE_START,
  DATE_END,
  KEYWORD,
  TYPE_FILE,
  TYPE_SAVED_EVENT,
  TYPE_EVENT_RECURRENCE,
  TYPE_MEDIA_FILE,
  TYPE_MEDIA_IMAGE,
  TYPE_MEDIA_SLIDESHOW,
  TYPE_MEDIA_VIDEO,
  TYPE_EQUIPMENT,
  TYPE_EQUIPMENT_RESERVATION,
  TYPE_EVENT,
  TYPE_EVENT_ATTENDANCE,
  TYPE_EVENT_SERIES,
  TYPE_EVENT_REGISTRATION,
  TYPE_LOCATION,
  TYPE_ROOM,
  TYPE_ROOM_RESERVATION,
  TYPE_AUDIENCE,
  TYPE_EQUIPMENT_TYPE,
  TYPE_EVALUATION_CRITERIA,
  TYPE_EVENT_TYPE,
  TYPE_SUBJECT,
  TYPE_MEETING_PURPOSE,
  TYPE_POPULATION_SEGMENT,
  TYPE_ROOM_TYPE,
  TYPE_TAG,
  TYPE_USER,
});

const commonjsGlobal = typeof window !== 'undefined' ? window : typeof global !== 'undefined' ? global : typeof self !== 'undefined' ? self : {};


function createCommonjsModule(fn, module) {
  return module = { exports: {} }, fn(module, module.exports), module.exports;
}

/** Detect free variable `global` from Node.js. */

const freeGlobal = typeof commonjsGlobal === 'object' && commonjsGlobal && commonjsGlobal.Object === Object && commonjsGlobal;
const _freeGlobal = freeGlobal;

/** Detect free variable `self`. */

const freeSelf = typeof self === 'object' && self && self.Object === Object && self;
/** Used as a reference to the global object. */

const root = _freeGlobal || freeSelf || Function('return this')();
const _root = root;

/** Built-in value references. */

const Symbol$1 = _root.Symbol;
const _Symbol = Symbol$1;

/** Used for built-in method references. */

const objectProto = Object.prototype;
/** Used to check objects for own properties. */

const hasOwnProperty = objectProto.hasOwnProperty;
/**
 * Used to resolve the
 * [`toStringTag`](http://ecma-international.org/ecma-262/7.0/#sec-object.prototype.tostring)
 * of values.
 */

const nativeObjectToString = objectProto.toString;
/** Built-in value references. */

const symToStringTag = _Symbol ? _Symbol.toStringTag : undefined;
/**
 * A specialized version of `baseGetTag` which ignores `Symbol.toStringTag` values.
 *
 * @private
 * @param {*} value The value to query.
 * @returns {string} Returns the raw `toStringTag`.
 */

function getRawTag(value) {
  const isOwn = hasOwnProperty.call(value, symToStringTag);
  const tag = value[symToStringTag];
  let unmasked = false;

  try {
    value[symToStringTag] = undefined;
    unmasked = true;
  }
  catch (e) {}

  const result = nativeObjectToString.call(value);

  if (unmasked) {
    if (isOwn) {
      value[symToStringTag] = tag;
    }
  else {
      delete value[symToStringTag];
    }
  }

  return result;
}

let _getRawTag = getRawTag;

/** Used for built-in method references. */
let objectProto$1 = Object.prototype;
/**
 * Used to resolve the
 * [`toStringTag`](http://ecma-international.org/ecma-262/7.0/#sec-object.prototype.tostring)
 * of values.
 */

let nativeObjectToString$1 = objectProto$1.toString;
/**
 * Converts `value` to a string using `Object.prototype.toString`.
 *
 * @private
 * @param {*} value The value to convert.
 * @returns {string} Returns the converted string.
 */

function objectToString(value) {
  return nativeObjectToString$1.call(value);
}

let _objectToString = objectToString;

/** `Object#toString` result references. */

let nullTag = '[object Null]';
let undefinedTag = '[object Undefined]';
/** Built-in value references. */

let symToStringTag$1 = _Symbol ? _Symbol.toStringTag : undefined;
/**
 * The base implementation of `getTag` without fallbacks for buggy environments.
 *
 * @private
 * @param {*} value The value to query.
 * @returns {string} Returns the `toStringTag`.
 */

function baseGetTag(value) {
  if (value == null) {
    return value === undefined ? undefinedTag : nullTag;
  }

  return symToStringTag$1 && symToStringTag$1 in Object(value) ? _getRawTag(value) : _objectToString(value);
}

let _baseGetTag = baseGetTag;

/**
 * Checks if `value` is the
 * [language type](http://www.ecma-international.org/ecma-262/7.0/#sec-ecmascript-language-types)
 * of `Object`. (e.g. arrays, functions, objects, regexes, `new Number(0)`, and `new String('')`)
 *
 * @static
 * @memberOf _
 * @since 0.1.0
 * @category Lang
 * @param {*} value The value to check.
 * @returns {boolean} Returns `true` if `value` is an object, else `false`.
 * @example
 *
 * _.isObject({});
 * // => true
 *
 * _.isObject([1, 2, 3]);
 * // => true
 *
 * _.isObject(_.noop);
 * // => true
 *
 * _.isObject(null);
 * // => false
 */
function isObject(value) {
  let type = typeof value;
  return value != null && (type == 'object' || type == 'function');
}

let isObject_1 = isObject;

/** `Object#toString` result references. */

let asyncTag = '[object AsyncFunction]';
let funcTag = '[object Function]';
let genTag = '[object GeneratorFunction]';
let proxyTag = '[object Proxy]';
/**
 * Checks if `value` is classified as a `Function` object.
 *
 * @static
 * @memberOf _
 * @since 0.1.0
 * @category Lang
 * @param {*} value The value to check.
 * @returns {boolean} Returns `true` if `value` is a function, else `false`.
 * @example
 *
 * _.isFunction(_);
 * // => true
 *
 * _.isFunction(/abc/);
 * // => false
 */

function isFunction(value) {
  if (!isObject_1(value)) {
    return false;
  } // The use of `Object#toString` avoids issues with the `typeof` operator
  // in Safari 9 which returns 'object' for typed arrays and other constructors.


  let tag = _baseGetTag(value);
  return tag == funcTag || tag == genTag || tag == asyncTag || tag == proxyTag;
}

let isFunction_1 = isFunction;

/** Used to detect overreaching core-js shims. */

let coreJsData = _root['__core-js_shared__'];
let _coreJsData = coreJsData;

/** Used to detect methods masquerading as native. */

let maskSrcKey = (function () {
  var uid = /[^.]+$/.exec(_coreJsData && _coreJsData.keys && _coreJsData.keys.IE_PROTO || '');
  return uid ? 'Symbol(src)_1.' + uid : '';
}());
/**
 * Checks if `func` has its source masked.
 *
 * @private
 * @param {Function} func The function to check.
 * @returns {boolean} Returns `true` if `func` is masked, else `false`.
 */


function isMasked(func) {
  return !!maskSrcKey && maskSrcKey in func;
}

let _isMasked = isMasked;

/** Used for built-in method references. */
let funcProto = Function.prototype;
/** Used to resolve the decompiled source of functions. */

let funcToString = funcProto.toString;
/**
 * Converts `func` to its source code.
 *
 * @private
 * @param {Function} func The function to convert.
 * @returns {string} Returns the source code.
 */

function toSource(func) {
  if (func != null) {
    try {
      return funcToString.call(func);
    }
 catch (e) {}

    try {
      return `${func  }`;
    }
 catch (e) {}
  }

  return '';
}

let _toSource = toSource;

/**
 * Used to match `RegExp`
 * [syntax characters](http://ecma-international.org/ecma-262/7.0/#sec-patterns).
 */

let reRegExpChar = /[\\^$.*+?()[\]{}|]/g;
/** Used to detect host constructors (Safari). */

let reIsHostCtor = /^\[object .+?Constructor\]$/;
/** Used for built-in method references. */

let funcProto$1 = Function.prototype;
let objectProto$2 = Object.prototype;
/** Used to resolve the decompiled source of functions. */

let funcToString$1 = funcProto$1.toString;
/** Used to check objects for own properties. */

let hasOwnProperty$1 = objectProto$2.hasOwnProperty;
/** Used to detect if a method is native. */

let reIsNative = RegExp(`^${  funcToString$1.call(hasOwnProperty$1).replace(reRegExpChar, '\\$&').replace(/hasOwnProperty|(function).*?(?=\\\()| for .+?(?=\\\])/g, '$1.*?')  }$`);
/**
 * The base implementation of `_.isNative` without bad shim checks.
 *
 * @private
 * @param {*} value The value to check.
 * @returns {boolean} Returns `true` if `value` is a native function,
 *  else `false`.
 */

function baseIsNative(value) {
  if (!isObject_1(value) || _isMasked(value)) {
    return false;
  }

  let pattern = isFunction_1(value) ? reIsNative : reIsHostCtor;
  return pattern.test(_toSource(value));
}

let _baseIsNative = baseIsNative;

/**
 * Gets the value at `key` of `object`.
 *
 * @private
 * @param {Object} [object] The object to query.
 * @param {string} key The key of the property to get.
 * @returns {*} Returns the property value.
 */
function getValue(object, key) {
  return object == null ? undefined : object[key];
}

let _getValue = getValue;

/**
 * Gets the native function at `key` of `object`.
 *
 * @private
 * @param {Object} object The object to query.
 * @param {string} key The key of the method to get.
 * @returns {*} Returns the function if it's native, else `undefined`.
 */

function getNative(object, key) {
  let value = _getValue(object, key);
  return _baseIsNative(value) ? value : undefined;
}

let _getNative = getNative;

let defineProperty = (function () {
  try {
    var func = _getNative(Object, 'defineProperty');
    func({}, '', {});
    return func;
  } catch (e) {}
}());

let _defineProperty = defineProperty;

/**
 * The base implementation of `assignValue` and `assignMergeValue` without
 * value checks.
 *
 * @private
 * @param {Object} object The object to modify.
 * @param {string} key The key of the property to assign.
 * @param {*} value The value to assign.
 */

function baseAssignValue(object, key, value) {
  if (key == '__proto__' && _defineProperty) {
    _defineProperty(object, key, {
      configurable: true,
      enumerable: true,
      value: value,
      writable: true,
    });
  }
 else {
    object[key] = value;
  }
}

let _baseAssignValue = baseAssignValue;

/**
 * A specialized version of `baseAggregator` for arrays.
 *
 * @private
 * @param {Array} [array] The array to iterate over.
 * @param {Function} setter The function to set `accumulator` values.
 * @param {Function} iteratee The iteratee to transform keys.
 * @param {Object} accumulator The initial aggregated object.
 * @returns {Function} Returns `accumulator`.
 */
function arrayAggregator(array, setter, iteratee, accumulator) {
  let index = -1,
    length = array == null ? 0 : array.length;

  while (++index < length) {
    let value = array[index];
    setter(accumulator, value, iteratee(value), array);
  }

  return accumulator;
}

let _arrayAggregator = arrayAggregator;

/**
 * Creates a base function for methods like `_.forIn` and `_.forOwn`.
 *
 * @private
 * @param {boolean} [fromRight] Specify iterating from right to left.
 * @returns {Function} Returns the new base function.
 */
function createBaseFor(fromRight) {
  return function (object, iteratee, keysFunc) {
    let index = -1,
      iterable = Object(object),
      props = keysFunc(object),
      length = props.length;

    while (length--) {
      let key = props[fromRight ? length : ++index];

      if (iteratee(iterable[key], key, iterable) === false) {
        break;
      }
    }

    return object;
  };
}

let _createBaseFor = createBaseFor;

/**
 * The base implementation of `baseForOwn` which iterates over `object`
 * properties returned by `keysFunc` and invokes `iteratee` for each property.
 * Iteratee functions may exit iteration early by explicitly returning `false`.
 *
 * @private
 * @param {Object} object The object to iterate over.
 * @param {Function} iteratee The function invoked per iteration.
 * @param {Function} keysFunc The function to get the keys of `object`.
 * @returns {Object} Returns `object`.
 */

let baseFor = _createBaseFor();
let _baseFor = baseFor;

/**
 * The base implementation of `_.times` without support for iteratee shorthands
 * or max array length checks.
 *
 * @private
 * @param {number} n The number of times to invoke `iteratee`.
 * @param {Function} iteratee The function invoked per iteration.
 * @returns {Array} Returns the array of results.
 */
function baseTimes(n, iteratee) {
  let index = -1,
    result = Array(n);

  while (++index < n) {
    result[index] = iteratee(index);
  }

  return result;
}

let _baseTimes = baseTimes;

/**
 * Checks if `value` is object-like. A value is object-like if it's not `null`
 * and has a `typeof` result of "object".
 *
 * @static
 * @memberOf _
 * @since 4.0.0
 * @category Lang
 * @param {*} value The value to check.
 * @returns {boolean} Returns `true` if `value` is object-like, else `false`.
 * @example
 *
 * _.isObjectLike({});
 * // => true
 *
 * _.isObjectLike([1, 2, 3]);
 * // => true
 *
 * _.isObjectLike(_.noop);
 * // => false
 *
 * _.isObjectLike(null);
 * // => false
 */
function isObjectLike(value) {
  return value != null && typeof value === 'object';
}

let isObjectLike_1 = isObjectLike;

/** `Object#toString` result references. */

let argsTag = '[object Arguments]';
/**
 * The base implementation of `_.isArguments`.
 *
 * @private
 * @param {*} value The value to check.
 * @returns {boolean} Returns `true` if `value` is an `arguments` object,
 */

function baseIsArguments(value) {
  return isObjectLike_1(value) && _baseGetTag(value) == argsTag;
}

let _baseIsArguments = baseIsArguments;

/** Used for built-in method references. */

let objectProto$3 = Object.prototype;
/** Used to check objects for own properties. */

let hasOwnProperty$2 = objectProto$3.hasOwnProperty;
/** Built-in value references. */

let propertyIsEnumerable = objectProto$3.propertyIsEnumerable;
/**
 * Checks if `value` is likely an `arguments` object.
 *
 * @static
 * @memberOf _
 * @since 0.1.0
 * @category Lang
 * @param {*} value The value to check.
 * @returns {boolean} Returns `true` if `value` is an `arguments` object,
 *  else `false`.
 * @example
 *
 * _.isArguments(function() { return arguments; }());
 * // => true
 *
 * _.isArguments([1, 2, 3]);
 * // => false
 */

let isArguments = _baseIsArguments(function () {
  return arguments;
}()) ? _baseIsArguments : function (value) {
    return isObjectLike_1(value) && hasOwnProperty$2.call(value, 'callee') && !propertyIsEnumerable.call(value, 'callee');
  };
let isArguments_1 = isArguments;

/**
 * Checks if `value` is classified as an `Array` object.
 *
 * @static
 * @memberOf _
 * @since 0.1.0
 * @category Lang
 * @param {*} value The value to check.
 * @returns {boolean} Returns `true` if `value` is an array, else `false`.
 * @example
 *
 * _.isArray([1, 2, 3]);
 * // => true
 *
 * _.isArray(document.body.children);
 * // => false
 *
 * _.isArray('abc');
 * // => false
 *
 * _.isArray(_.noop);
 * // => false
 */
let isArray = Array.isArray;
let isArray_1 = isArray;

/**
 * This method returns `false`.
 *
 * @static
 * @memberOf _
 * @since 4.13.0
 * @category Util
 * @returns {boolean} Returns `false`.
 * @example
 *
 * _.times(2, _.stubFalse);
 * // => [false, false]
 */
function stubFalse() {
  return false;
}

let stubFalse_1 = stubFalse;

let isBuffer_1 = createCommonjsModule((module, exports) => {
  /** Detect free variable `exports`. */
  var freeExports = 'object' == 'object' && exports && !exports.nodeType && exports;
  /** Detect free variable `module`. */

  var freeModule = freeExports && 'object' == 'object' && module && !module.nodeType && module;
  /** Detect the popular CommonJS extension `module.exports`. */

  var moduleExports = freeModule && freeModule.exports === freeExports;
  /** Built-in value references. */

  var Buffer = moduleExports ? _root.Buffer : undefined;
  /* Built-in method references for those with the same name as other `lodash` methods. */

  var nativeIsBuffer = Buffer ? Buffer.isBuffer : undefined;
  /**
   * Checks if `value` is a buffer.
   *
   * @static
   * @memberOf _
   * @since 4.3.0
   * @category Lang
   * @param {*} value The value to check.
   * @returns {boolean} Returns `true` if `value` is a buffer, else `false`.
   * @example
   *
   * _.isBuffer(new Buffer(2));
   * // => true
   *
   * _.isBuffer(new Uint8Array(2));
   * // => false
   */

  var isBuffer = nativeIsBuffer || stubFalse_1;
  module.exports = isBuffer;
});

/** Used as references for various `Number` constants. */
let MAX_SAFE_INTEGER = 9007199254740991;
/** Used to detect unsigned integer values. */

let reIsUint = /^(?:0|[1-9]\d*)$/;
/**
 * Checks if `value` is a valid array-like index.
 *
 * @private
 * @param {*} value The value to check.
 * @param {number} [length=MAX_SAFE_INTEGER] The upper bounds of a valid index.
 * @returns {boolean} Returns `true` if `value` is a valid index, else `false`.
 */

function isIndex(value, length) {
  let type = typeof value;
  length = length == null ? MAX_SAFE_INTEGER : length;
  return !!length && (type == 'number' || type != 'symbol' && reIsUint.test(value)) && value > -1 && value % 1 == 0 && value < length;
}

let _isIndex = isIndex;

/** Used as references for various `Number` constants. */
let MAX_SAFE_INTEGER$1 = 9007199254740991;
/**
 * Checks if `value` is a valid array-like length.
 *
 * **Note:** This method is loosely based on
 * [`ToLength`](http://ecma-international.org/ecma-262/7.0/#sec-tolength).
 *
 * @static
 * @memberOf _
 * @since 4.0.0
 * @category Lang
 * @param {*} value The value to check.
 * @returns {boolean} Returns `true` if `value` is a valid length, else `false`.
 * @example
 *
 * _.isLength(3);
 * // => true
 *
 * _.isLength(Number.MIN_VALUE);
 * // => false
 *
 * _.isLength(Infinity);
 * // => false
 *
 * _.isLength('3');
 * // => false
 */

function isLength(value) {
  return typeof value === 'number' && value > -1 && value % 1 == 0 && value <= MAX_SAFE_INTEGER$1;
}

let isLength_1 = isLength;

/** `Object#toString` result references. */

let argsTag$1 = '[object Arguments]';
let arrayTag = '[object Array]';
let boolTag = '[object Boolean]';
let dateTag = '[object Date]';
let errorTag = '[object Error]';
let funcTag$1 = '[object Function]';
let mapTag = '[object Map]';
let numberTag = '[object Number]';
let objectTag = '[object Object]';
let regexpTag = '[object RegExp]';
let setTag = '[object Set]';
let stringTag = '[object String]';
let weakMapTag = '[object WeakMap]';
let arrayBufferTag = '[object ArrayBuffer]';
let dataViewTag = '[object DataView]';
let float32Tag = '[object Float32Array]';
let float64Tag = '[object Float64Array]';
let int8Tag = '[object Int8Array]';
let int16Tag = '[object Int16Array]';
let int32Tag = '[object Int32Array]';
let uint8Tag = '[object Uint8Array]';
let uint8ClampedTag = '[object Uint8ClampedArray]';
let uint16Tag = '[object Uint16Array]';
let uint32Tag = '[object Uint32Array]';
/** Used to identify `toStringTag` values of typed arrays. */

let typedArrayTags = {};
typedArrayTags[float32Tag] = typedArrayTags[float64Tag] = typedArrayTags[int8Tag] = typedArrayTags[int16Tag] = typedArrayTags[int32Tag] = typedArrayTags[uint8Tag] = typedArrayTags[uint8ClampedTag] = typedArrayTags[uint16Tag] = typedArrayTags[uint32Tag] = true;
typedArrayTags[argsTag$1] = typedArrayTags[arrayTag] = typedArrayTags[arrayBufferTag] = typedArrayTags[boolTag] = typedArrayTags[dataViewTag] = typedArrayTags[dateTag] = typedArrayTags[errorTag] = typedArrayTags[funcTag$1] = typedArrayTags[mapTag] = typedArrayTags[numberTag] = typedArrayTags[objectTag] = typedArrayTags[regexpTag] = typedArrayTags[setTag] = typedArrayTags[stringTag] = typedArrayTags[weakMapTag] = false;
/**
 * The base implementation of `_.isTypedArray` without Node.js optimizations.
 *
 * @private
 * @param {*} value The value to check.
 * @returns {boolean} Returns `true` if `value` is a typed array, else `false`.
 */

function baseIsTypedArray(value) {
  return isObjectLike_1(value) && isLength_1(value.length) && !!typedArrayTags[_baseGetTag(value)];
}

let _baseIsTypedArray = baseIsTypedArray;

/**
 * The base implementation of `_.unary` without support for storing metadata.
 *
 * @private
 * @param {Function} func The function to cap arguments for.
 * @returns {Function} Returns the new capped function.
 */
function baseUnary(func) {
  return function (value) {
    return func(value);
  };
}

let _baseUnary = baseUnary;

let _nodeUtil = createCommonjsModule((module, exports) => {
  /** Detect free variable `exports`. */
  var freeExports = 'object' == 'object' && exports && !exports.nodeType && exports;
  /** Detect free variable `module`. */

  var freeModule = freeExports && 'object' == 'object' && module && !module.nodeType && module;
  /** Detect the popular CommonJS extension `module.exports`. */

  var moduleExports = freeModule && freeModule.exports === freeExports;
  /** Detect free variable `process` from Node.js. */

  var freeProcess = moduleExports && _freeGlobal.process;
  /** Used to access faster Node.js helpers. */

  var nodeUtil = function () {
    try {
      return freeProcess && freeProcess.binding && freeProcess.binding('util');
    } catch (e) {}
  }();

  module.exports = nodeUtil;
});

/* Node.js helper references. */

let nodeIsTypedArray = _nodeUtil && _nodeUtil.isTypedArray;
/**
 * Checks if `value` is classified as a typed array.
 *
 * @static
 * @memberOf _
 * @since 3.0.0
 * @category Lang
 * @param {*} value The value to check.
 * @returns {boolean} Returns `true` if `value` is a typed array, else `false`.
 * @example
 *
 * _.isTypedArray(new Uint8Array);
 * // => true
 *
 * _.isTypedArray([]);
 * // => false
 */

let isTypedArray = nodeIsTypedArray ? _baseUnary(nodeIsTypedArray) : _baseIsTypedArray;
let isTypedArray_1 = isTypedArray;

/** Used for built-in method references. */

let objectProto$4 = Object.prototype;
/** Used to check objects for own properties. */

let hasOwnProperty$3 = objectProto$4.hasOwnProperty;
/**
 * Creates an array of the enumerable property names of the array-like `value`.
 *
 * @private
 * @param {*} value The value to query.
 * @param {boolean} inherited Specify returning inherited property names.
 * @returns {Array} Returns the array of property names.
 */

function arrayLikeKeys(value, inherited) {
  let isArr = isArray_1(value),
    isArg = !isArr && isArguments_1(value),
    isBuff = !isArr && !isArg && isBuffer_1(value),
    isType = !isArr && !isArg && !isBuff && isTypedArray_1(value),
    skipIndexes = isArr || isArg || isBuff || isType,
    result = skipIndexes ? _baseTimes(value.length, String) : [],
    length = result.length;

  for (let key in value) {
    if ((inherited || hasOwnProperty$3.call(value, key)) && !(skipIndexes && ( // Safari 9 has enumerable `arguments.length` in strict mode.
      (// Skip index properties.
      (key == 'length' || // Node.js 0.10 has enumerable non-index properties on buffers.
    isBuff && (key == 'offset' || key == 'parent') || // PhantomJS 2 has enumerable non-index properties on typed arrays.
    isType && (key == 'buffer' || key == 'byteLength' || key == 'byteOffset') || _isIndex(key, length)))))) {
      result.push(key);
    }
  }

  return result;
}

let _arrayLikeKeys = arrayLikeKeys;

/** Used for built-in method references. */
let objectProto$5 = Object.prototype;
/**
 * Checks if `value` is likely a prototype object.
 *
 * @private
 * @param {*} value The value to check.
 * @returns {boolean} Returns `true` if `value` is a prototype, else `false`.
 */

function isPrototype(value) {
  let Ctor = value && value.constructor,
    proto = typeof Ctor === 'function' && Ctor.prototype || objectProto$5;
  return value === proto;
}

let _isPrototype = isPrototype;

/**
 * Creates a unary function that invokes `func` with its argument transformed.
 *
 * @private
 * @param {Function} func The function to wrap.
 * @param {Function} transform The argument transform.
 * @returns {Function} Returns the new function.
 */
function overArg(func, transform) {
  return function (arg) {
    return func(transform(arg));
  };
}

let _overArg = overArg;

/* Built-in method references for those with the same name as other `lodash` methods. */

let nativeKeys = _overArg(Object.keys, Object);
let _nativeKeys = nativeKeys;

/** Used for built-in method references. */

let objectProto$6 = Object.prototype;
/** Used to check objects for own properties. */

let hasOwnProperty$4 = objectProto$6.hasOwnProperty;
/**
 * The base implementation of `_.keys` which doesn't treat sparse arrays as dense.
 *
 * @private
 * @param {Object} object The object to query.
 * @returns {Array} Returns the array of property names.
 */

function baseKeys(object) {
  if (!_isPrototype(object)) {
    return _nativeKeys(object);
  }

  let result = [];

  for (let key in Object(object)) {
    if (hasOwnProperty$4.call(object, key) && key != 'constructor') {
      result.push(key);
    }
  }

  return result;
}

let _baseKeys = baseKeys;

/**
 * Checks if `value` is array-like. A value is considered array-like if it's
 * not a function and has a `value.length` that's an integer greater than or
 * equal to `0` and less than or equal to `Number.MAX_SAFE_INTEGER`.
 *
 * @static
 * @memberOf _
 * @since 4.0.0
 * @category Lang
 * @param {*} value The value to check.
 * @returns {boolean} Returns `true` if `value` is array-like, else `false`.
 * @example
 *
 * _.isArrayLike([1, 2, 3]);
 * // => true
 *
 * _.isArrayLike(document.body.children);
 * // => true
 *
 * _.isArrayLike('abc');
 * // => true
 *
 * _.isArrayLike(_.noop);
 * // => false
 */

function isArrayLike(value) {
  return value != null && isLength_1(value.length) && !isFunction_1(value);
}

let isArrayLike_1 = isArrayLike;

/**
 * Creates an array of the own enumerable property names of `object`.
 *
 * **Note:** Non-object values are coerced to objects. See the
 * [ES spec](http://ecma-international.org/ecma-262/7.0/#sec-object.keys)
 * for more details.
 *
 * @static
 * @since 0.1.0
 * @memberOf _
 * @category Object
 * @param {Object} object The object to query.
 * @returns {Array} Returns the array of property names.
 * @example
 *
 * function Foo() {
 *   this.a = 1;
 *   this.b = 2;
 * }
 *
 * Foo.prototype.c = 3;
 *
 * _.keys(new Foo);
 * // => ['a', 'b'] (iteration order is not guaranteed)
 *
 * _.keys('hi');
 * // => ['0', '1']
 */

function keys(object) {
  return isArrayLike_1(object) ? _arrayLikeKeys(object) : _baseKeys(object);
}

let keys_1 = keys;

/**
 * The base implementation of `_.forOwn` without support for iteratee shorthands.
 *
 * @private
 * @param {Object} object The object to iterate over.
 * @param {Function} iteratee The function invoked per iteration.
 * @returns {Object} Returns `object`.
 */

function baseForOwn(object, iteratee) {
  return object && _baseFor(object, iteratee, keys_1);
}

let _baseForOwn = baseForOwn;

/**
 * Creates a `baseEach` or `baseEachRight` function.
 *
 * @private
 * @param {Function} eachFunc The function to iterate over a collection.
 * @param {boolean} [fromRight] Specify iterating from right to left.
 * @returns {Function} Returns the new base function.
 */

function createBaseEach(eachFunc, fromRight) {
  return function (collection, iteratee) {
    if (collection == null) {
      return collection;
    }

    if (!isArrayLike_1(collection)) {
      return eachFunc(collection, iteratee);
    }

    let length = collection.length,
      index = fromRight ? length : -1,
      iterable = Object(collection);

    while (fromRight ? index-- : ++index < length) {
      if (iteratee(iterable[index], index, iterable) === false) {
        break;
      }
    }

    return collection;
  };
}

let _createBaseEach = createBaseEach;

/**
 * The base implementation of `_.forEach` without support for iteratee shorthands.
 *
 * @private
 * @param {Array|Object} collection The collection to iterate over.
 * @param {Function} iteratee The function invoked per iteration.
 * @returns {Array|Object} Returns `collection`.
 */

let baseEach = _createBaseEach(_baseForOwn);
let _baseEach = baseEach;

/**
 * Aggregates elements of `collection` on `accumulator` with keys transformed
 * by `iteratee` and values set by `setter`.
 *
 * @private
 * @param {Array|Object} collection The collection to iterate over.
 * @param {Function} setter The function to set `accumulator` values.
 * @param {Function} iteratee The iteratee to transform keys.
 * @param {Object} accumulator The initial aggregated object.
 * @returns {Function} Returns `accumulator`.
 */

function baseAggregator(collection, setter, iteratee, accumulator) {
  _baseEach(collection, (value, key, collection) => {
    setter(accumulator, value, iteratee(value), collection);
  });
  return accumulator;
}

let _baseAggregator = baseAggregator;

/**
 * Removes all key-value entries from the list cache.
 *
 * @private
 * @name clear
 * @memberOf ListCache
 */
function listCacheClear() {
  this.__data__ = [];
  this.size = 0;
}

let _listCacheClear = listCacheClear;

/**
 * Performs a
 * [`SameValueZero`](http://ecma-international.org/ecma-262/7.0/#sec-samevaluezero)
 * comparison between two values to determine if they are equivalent.
 *
 * @static
 * @memberOf _
 * @since 4.0.0
 * @category Lang
 * @param {*} value The value to compare.
 * @param {*} other The other value to compare.
 * @returns {boolean} Returns `true` if the values are equivalent, else `false`.
 * @example
 *
 * var object = { 'a': 1 };
 * var other = { 'a': 1 };
 *
 * _.eq(object, object);
 * // => true
 *
 * _.eq(object, other);
 * // => false
 *
 * _.eq('a', 'a');
 * // => true
 *
 * _.eq('a', Object('a'));
 * // => false
 *
 * _.eq(NaN, NaN);
 * // => true
 */
function eq(value, other) {
  return value === other || value !== value && other !== other;
}

let eq_1 = eq;

/**
 * Gets the index at which the `key` is found in `array` of key-value pairs.
 *
 * @private
 * @param {Array} array The array to inspect.
 * @param {*} key The key to search for.
 * @returns {number} Returns the index of the matched value, else `-1`.
 */

function assocIndexOf(array, key) {
  let length = array.length;

  while (length--) {
    if (eq_1(array[length][0], key)) {
      return length;
    }
  }

  return -1;
}

let _assocIndexOf = assocIndexOf;

/** Used for built-in method references. */

let arrayProto = Array.prototype;
/** Built-in value references. */

let splice = arrayProto.splice;
/**
 * Removes `key` and its value from the list cache.
 *
 * @private
 * @name delete
 * @memberOf ListCache
 * @param {string} key The key of the value to remove.
 * @returns {boolean} Returns `true` if the entry was removed, else `false`.
 */

function listCacheDelete(key) {
  let data = this.__data__,
    index = _assocIndexOf(data, key);

  if (index < 0) {
    return false;
  }

  let lastIndex = data.length - 1;

  if (index == lastIndex) {
    data.pop();
  }
 else {
    splice.call(data, index, 1);
  }

  --this.size;
  return true;
}

let _listCacheDelete = listCacheDelete;

/**
 * Gets the list cache value for `key`.
 *
 * @private
 * @name get
 * @memberOf ListCache
 * @param {string} key The key of the value to get.
 * @returns {*} Returns the entry value.
 */

function listCacheGet(key) {
  let data = this.__data__,
    index = _assocIndexOf(data, key);
  return index < 0 ? undefined : data[index][1];
}

let _listCacheGet = listCacheGet;

/**
 * Checks if a list cache value for `key` exists.
 *
 * @private
 * @name has
 * @memberOf ListCache
 * @param {string} key The key of the entry to check.
 * @returns {boolean} Returns `true` if an entry for `key` exists, else `false`.
 */

function listCacheHas(key) {
  return _assocIndexOf(this.__data__, key) > -1;
}

let _listCacheHas = listCacheHas;

/**
 * Sets the list cache `key` to `value`.
 *
 * @private
 * @name set
 * @memberOf ListCache
 * @param {string} key The key of the value to set.
 * @param {*} value The value to set.
 * @returns {Object} Returns the list cache instance.
 */

function listCacheSet(key, value) {
  let data = this.__data__,
    index = _assocIndexOf(data, key);

  if (index < 0) {
    ++this.size;
    data.push([key, value]);
  }
 else {
    data[index][1] = value;
  }

  return this;
}

let _listCacheSet = listCacheSet;

/**
 * Creates an list cache object.
 *
 * @private
 * @constructor
 * @param {Array} [entries] The key-value pairs to cache.
 */

function ListCache(entries) {
  let index = -1,
    length = entries == null ? 0 : entries.length;
  this.clear();

  while (++index < length) {
    let entry = entries[index];
    this.set(entry[0], entry[1]);
  }
} // Add methods to `ListCache`.


ListCache.prototype.clear = _listCacheClear;
ListCache.prototype['delete'] = _listCacheDelete;
ListCache.prototype.get = _listCacheGet;
ListCache.prototype.has = _listCacheHas;
ListCache.prototype.set = _listCacheSet;
let _ListCache = ListCache;

/**
 * Removes all key-value entries from the stack.
 *
 * @private
 * @name clear
 * @memberOf Stack
 */

function stackClear() {
  this.__data__ = new _ListCache();
  this.size = 0;
}

let _stackClear = stackClear;

/**
 * Removes `key` and its value from the stack.
 *
 * @private
 * @name delete
 * @memberOf Stack
 * @param {string} key The key of the value to remove.
 * @returns {boolean} Returns `true` if the entry was removed, else `false`.
 */
function stackDelete(key) {
  let data = this.__data__,
    result = data['delete'](key);
  this.size = data.size;
  return result;
}

let _stackDelete = stackDelete;

/**
 * Gets the stack value for `key`.
 *
 * @private
 * @name get
 * @memberOf Stack
 * @param {string} key The key of the value to get.
 * @returns {*} Returns the entry value.
 */
function stackGet(key) {
  return this.__data__.get(key);
}

let _stackGet = stackGet;

/**
 * Checks if a stack value for `key` exists.
 *
 * @private
 * @name has
 * @memberOf Stack
 * @param {string} key The key of the entry to check.
 * @returns {boolean} Returns `true` if an entry for `key` exists, else `false`.
 */
function stackHas(key) {
  return this.__data__.has(key);
}

let _stackHas = stackHas;

/* Built-in method references that are verified to be native. */

let Map$1 = _getNative(_root, 'Map');
let _Map = Map$1;

/* Built-in method references that are verified to be native. */

let nativeCreate = _getNative(Object, 'create');
let _nativeCreate = nativeCreate;

/**
 * Removes all key-value entries from the hash.
 *
 * @private
 * @name clear
 * @memberOf Hash
 */

function hashClear() {
  this.__data__ = _nativeCreate ? _nativeCreate(null) : {};
  this.size = 0;
}

let _hashClear = hashClear;

/**
 * Removes `key` and its value from the hash.
 *
 * @private
 * @name delete
 * @memberOf Hash
 * @param {Object} hash The hash to modify.
 * @param {string} key The key of the value to remove.
 * @returns {boolean} Returns `true` if the entry was removed, else `false`.
 */
function hashDelete(key) {
  let result = this.has(key) && delete this.__data__[key];
  this.size -= result ? 1 : 0;
  return result;
}

let _hashDelete = hashDelete;

/** Used to stand-in for `undefined` hash values. */

let HASH_UNDEFINED = '__lodash_hash_undefined__';
/** Used for built-in method references. */

let objectProto$7 = Object.prototype;
/** Used to check objects for own properties. */

let hasOwnProperty$5 = objectProto$7.hasOwnProperty;
/**
 * Gets the hash value for `key`.
 *
 * @private
 * @name get
 * @memberOf Hash
 * @param {string} key The key of the value to get.
 * @returns {*} Returns the entry value.
 */

function hashGet(key) {
  let data = this.__data__;

  if (_nativeCreate) {
    let result = data[key];
    return result === HASH_UNDEFINED ? undefined : result;
  }

  return hasOwnProperty$5.call(data, key) ? data[key] : undefined;
}

let _hashGet = hashGet;

/** Used for built-in method references. */

let objectProto$8 = Object.prototype;
/** Used to check objects for own properties. */

let hasOwnProperty$6 = objectProto$8.hasOwnProperty;
/**
 * Checks if a hash value for `key` exists.
 *
 * @private
 * @name has
 * @memberOf Hash
 * @param {string} key The key of the entry to check.
 * @returns {boolean} Returns `true` if an entry for `key` exists, else `false`.
 */

function hashHas(key) {
  let data = this.__data__;
  return _nativeCreate ? data[key] !== undefined : hasOwnProperty$6.call(data, key);
}

let _hashHas = hashHas;

/** Used to stand-in for `undefined` hash values. */

let HASH_UNDEFINED$1 = '__lodash_hash_undefined__';
/**
 * Sets the hash `key` to `value`.
 *
 * @private
 * @name set
 * @memberOf Hash
 * @param {string} key The key of the value to set.
 * @param {*} value The value to set.
 * @returns {Object} Returns the hash instance.
 */

function hashSet(key, value) {
  let data = this.__data__;
  this.size += this.has(key) ? 0 : 1;
  data[key] = _nativeCreate && value === undefined ? HASH_UNDEFINED$1 : value;
  return this;
}

let _hashSet = hashSet;

/**
 * Creates a hash object.
 *
 * @private
 * @constructor
 * @param {Array} [entries] The key-value pairs to cache.
 */

function Hash(entries) {
  let index = -1,
    length = entries == null ? 0 : entries.length;
  this.clear();

  while (++index < length) {
    let entry = entries[index];
    this.set(entry[0], entry[1]);
  }
} // Add methods to `Hash`.


Hash.prototype.clear = _hashClear;
Hash.prototype['delete'] = _hashDelete;
Hash.prototype.get = _hashGet;
Hash.prototype.has = _hashHas;
Hash.prototype.set = _hashSet;
let _Hash = Hash;

/**
 * Removes all key-value entries from the map.
 *
 * @private
 * @name clear
 * @memberOf MapCache
 */

function mapCacheClear() {
  this.size = 0;
  this.__data__ = {
    hash: new _Hash(),
    map: new (_Map || _ListCache)(),
    string: new _Hash(),
  };
}

let _mapCacheClear = mapCacheClear;

/**
 * Checks if `value` is suitable for use as unique object key.
 *
 * @private
 * @param {*} value The value to check.
 * @returns {boolean} Returns `true` if `value` is suitable, else `false`.
 */
function isKeyable(value) {
  let type = typeof value;
  return type == 'string' || type == 'number' || type == 'symbol' || type == 'boolean' ? value !== '__proto__' : value === null;
}

let _isKeyable = isKeyable;

/**
 * Gets the data for `map`.
 *
 * @private
 * @param {Object} map The map to query.
 * @param {string} key The reference key.
 * @returns {*} Returns the map data.
 */

function getMapData(map, key) {
  let data = map.__data__;
  return _isKeyable(key) ? data[typeof key === 'string' ? 'string' : 'hash'] : data.map;
}

let _getMapData = getMapData;

/**
 * Removes `key` and its value from the map.
 *
 * @private
 * @name delete
 * @memberOf MapCache
 * @param {string} key The key of the value to remove.
 * @returns {boolean} Returns `true` if the entry was removed, else `false`.
 */

function mapCacheDelete(key) {
  let result = _getMapData(this, key)['delete'](key);
  this.size -= result ? 1 : 0;
  return result;
}

let _mapCacheDelete = mapCacheDelete;

/**
 * Gets the map value for `key`.
 *
 * @private
 * @name get
 * @memberOf MapCache
 * @param {string} key The key of the value to get.
 * @returns {*} Returns the entry value.
 */

function mapCacheGet(key) {
  return _getMapData(this, key).get(key);
}

let _mapCacheGet = mapCacheGet;

/**
 * Checks if a map value for `key` exists.
 *
 * @private
 * @name has
 * @memberOf MapCache
 * @param {string} key The key of the entry to check.
 * @returns {boolean} Returns `true` if an entry for `key` exists, else `false`.
 */

function mapCacheHas(key) {
  return _getMapData(this, key).has(key);
}

let _mapCacheHas = mapCacheHas;

/**
 * Sets the map `key` to `value`.
 *
 * @private
 * @name set
 * @memberOf MapCache
 * @param {string} key The key of the value to set.
 * @param {*} value The value to set.
 * @returns {Object} Returns the map cache instance.
 */

function mapCacheSet(key, value) {
  let data = _getMapData(this, key),
    size = data.size;
  data.set(key, value);
  this.size += data.size == size ? 0 : 1;
  return this;
}

let _mapCacheSet = mapCacheSet;

/**
 * Creates a map cache object to store key-value pairs.
 *
 * @private
 * @constructor
 * @param {Array} [entries] The key-value pairs to cache.
 */

function MapCache(entries) {
  let index = -1,
    length = entries == null ? 0 : entries.length;
  this.clear();

  while (++index < length) {
    let entry = entries[index];
    this.set(entry[0], entry[1]);
  }
} // Add methods to `MapCache`.


MapCache.prototype.clear = _mapCacheClear;
MapCache.prototype['delete'] = _mapCacheDelete;
MapCache.prototype.get = _mapCacheGet;
MapCache.prototype.has = _mapCacheHas;
MapCache.prototype.set = _mapCacheSet;
let _MapCache = MapCache;

/** Used as the size to enable large array optimizations. */

let LARGE_ARRAY_SIZE = 200;
/**
 * Sets the stack `key` to `value`.
 *
 * @private
 * @name set
 * @memberOf Stack
 * @param {string} key The key of the value to set.
 * @param {*} value The value to set.
 * @returns {Object} Returns the stack cache instance.
 */

function stackSet(key, value) {
  let data = this.__data__;

  if (data instanceof _ListCache) {
    let pairs = data.__data__;

    if (!_Map || pairs.length < LARGE_ARRAY_SIZE - 1) {
      pairs.push([key, value]);
      this.size = ++data.size;
      return this;
    }

    data = this.__data__ = new _MapCache(pairs);
  }

  data.set(key, value);
  this.size = data.size;
  return this;
}

let _stackSet = stackSet;

/**
 * Creates a stack cache object to store key-value pairs.
 *
 * @private
 * @constructor
 * @param {Array} [entries] The key-value pairs to cache.
 */

function Stack(entries) {
  let data = this.__data__ = new _ListCache(entries);
  this.size = data.size;
} // Add methods to `Stack`.


Stack.prototype.clear = _stackClear;
Stack.prototype['delete'] = _stackDelete;
Stack.prototype.get = _stackGet;
Stack.prototype.has = _stackHas;
Stack.prototype.set = _stackSet;
let _Stack = Stack;

/** Used to stand-in for `undefined` hash values. */
let HASH_UNDEFINED$2 = '__lodash_hash_undefined__';
/**
 * Adds `value` to the array cache.
 *
 * @private
 * @name add
 * @memberOf SetCache
 * @alias push
 * @param {*} value The value to cache.
 * @returns {Object} Returns the cache instance.
 */

function setCacheAdd(value) {
  this.__data__.set(value, HASH_UNDEFINED$2);

  return this;
}

let _setCacheAdd = setCacheAdd;

/**
 * Checks if `value` is in the array cache.
 *
 * @private
 * @name has
 * @memberOf SetCache
 * @param {*} value The value to search for.
 * @returns {number} Returns `true` if `value` is found, else `false`.
 */
function setCacheHas(value) {
  return this.__data__.has(value);
}

let _setCacheHas = setCacheHas;

/**
 *
 * Creates an array cache object to store unique values.
 *
 * @private
 * @constructor
 * @param {Array} [values] The values to cache.
 */

function SetCache(values) {
  let index = -1,
    length = values == null ? 0 : values.length;
  this.__data__ = new _MapCache();

  while (++index < length) {
    this.add(values[index]);
  }
} // Add methods to `SetCache`.


SetCache.prototype.add = SetCache.prototype.push = _setCacheAdd;
SetCache.prototype.has = _setCacheHas;
let _SetCache = SetCache;

/**
 * A specialized version of `_.some` for arrays without support for iteratee
 * shorthands.
 *
 * @private
 * @param {Array} [array] The array to iterate over.
 * @param {Function} predicate The function invoked per iteration.
 * @returns {boolean} Returns `true` if any element passes the predicate check,
 *  else `false`.
 */
function arraySome(array, predicate) {
  let index = -1,
    length = array == null ? 0 : array.length;

  while (++index < length) {
    if (predicate(array[index], index, array)) {
      return true;
    }
  }

  return false;
}

let _arraySome = arraySome;

/**
 * Checks if a `cache` value for `key` exists.
 *
 * @private
 * @param {Object} cache The cache to query.
 * @param {string} key The key of the entry to check.
 * @returns {boolean} Returns `true` if an entry for `key` exists, else `false`.
 */
function cacheHas(cache, key) {
  return cache.has(key);
}

let _cacheHas = cacheHas;

/** Used to compose bitmasks for value comparisons. */

let COMPARE_PARTIAL_FLAG = 1;
let COMPARE_UNORDERED_FLAG = 2;
/**
 * A specialized version of `baseIsEqualDeep` for arrays with support for
 * partial deep comparisons.
 *
 * @private
 * @param {Array} array The array to compare.
 * @param {Array} other The other array to compare.
 * @param {number} bitmask The bitmask flags. See `baseIsEqual` for more details.
 * @param {Function} customizer The function to customize comparisons.
 * @param {Function} equalFunc The function to determine equivalents of values.
 * @param {Object} stack Tracks traversed `array` and `other` objects.
 * @returns {boolean} Returns `true` if the arrays are equivalent, else `false`.
 */

function equalArrays(array, other, bitmask, customizer, equalFunc, stack) {
  let isPartial = bitmask & COMPARE_PARTIAL_FLAG,
    arrLength = array.length,
    othLength = other.length;

  if (arrLength != othLength && !(isPartial && othLength > arrLength)) {
    return false;
  } // Assume cyclic values are equal.


  let stacked = stack.get(array);

  if (stacked && stack.get(other)) {
    return stacked == other;
  }

  let index = -1,
    result = true,
    seen = bitmask & COMPARE_UNORDERED_FLAG ? new _SetCache() : undefined;
  stack.set(array, other);
  stack.set(other, array); // Ignore non-index properties.

  while (++index < arrLength) {
    var arrValue = array[index],
      othValue = other[index];

    if (customizer) {
      var compared = isPartial ? customizer(othValue, arrValue, index, other, array, stack) : customizer(arrValue, othValue, index, array, other, stack);
    }

    if (compared !== undefined) {
      if (compared) {
        continue;
      }

      result = false;
      break;
    } // Recursively compare arrays (susceptible to call stack limits).


    if (seen) {
      if (!_arraySome(other, (othValue, othIndex) => {
        if (!_cacheHas(seen, othIndex) && (arrValue === othValue || equalFunc(arrValue, othValue, bitmask, customizer, stack))) {
          return seen.push(othIndex);
        }
      })) {
        result = false;
        break;
      }
    }
 else if (!(arrValue === othValue || equalFunc(arrValue, othValue, bitmask, customizer, stack))) {
      result = false;
      break;
    }
  }

  stack['delete'](array);
  stack['delete'](other);
  return result;
}

let _equalArrays = equalArrays;

/** Built-in value references. */

let Uint8Array = _root.Uint8Array;
let _Uint8Array = Uint8Array;

/**
 * Converts `map` to its key-value pairs.
 *
 * @private
 * @param {Object} map The map to convert.
 * @returns {Array} Returns the key-value pairs.
 */
function mapToArray(map) {
  let index = -1,
    result = Array(map.size);
  map.forEach((value, key) => {
    result[++index] = [key, value];
  });
  return result;
}

let _mapToArray = mapToArray;

/**
 * Converts `set` to an array of its values.
 *
 * @private
 * @param {Object} set The set to convert.
 * @returns {Array} Returns the values.
 */
function setToArray(set) {
  let index = -1,
    result = Array(set.size);
  set.forEach((value) => {
    result[++index] = value;
  });
  return result;
}

let _setToArray = setToArray;

/** Used to compose bitmasks for value comparisons. */

let COMPARE_PARTIAL_FLAG$1 = 1;
let COMPARE_UNORDERED_FLAG$1 = 2;
/** `Object#toString` result references. */

let boolTag$1 = '[object Boolean]';
let dateTag$1 = '[object Date]';
let errorTag$1 = '[object Error]';
let mapTag$1 = '[object Map]';
let numberTag$1 = '[object Number]';
let regexpTag$1 = '[object RegExp]';
let setTag$1 = '[object Set]';
let stringTag$1 = '[object String]';
let symbolTag = '[object Symbol]';
let arrayBufferTag$1 = '[object ArrayBuffer]';
let dataViewTag$1 = '[object DataView]';
/** Used to convert symbols to primitives and strings. */

let symbolProto = _Symbol ? _Symbol.prototype : undefined;
let symbolValueOf = symbolProto ? symbolProto.valueOf : undefined;
/**
 * A specialized version of `baseIsEqualDeep` for comparing objects of
 * the same `toStringTag`.
 *
 * **Note:** This function only supports comparing values with tags of
 * `Boolean`, `Date`, `Error`, `Number`, `RegExp`, or `String`.
 *
 * @private
 * @param {Object} object The object to compare.
 * @param {Object} other The other object to compare.
 * @param {string} tag The `toStringTag` of the objects to compare.
 * @param {number} bitmask The bitmask flags. See `baseIsEqual` for more details.
 * @param {Function} customizer The function to customize comparisons.
 * @param {Function} equalFunc The function to determine equivalents of values.
 * @param {Object} stack Tracks traversed `object` and `other` objects.
 * @returns {boolean} Returns `true` if the objects are equivalent, else `false`.
 */

function equalByTag(object, other, tag, bitmask, customizer, equalFunc, stack) {
  switch (tag) {
    case dataViewTag$1:
      if (object.byteLength != other.byteLength || object.byteOffset != other.byteOffset) {
        return false;
      }

      object = object.buffer;
      other = other.buffer;

    case arrayBufferTag$1:
      if (object.byteLength != other.byteLength || !equalFunc(new _Uint8Array(object), new _Uint8Array(other))) {
        return false;
      }

      return true;

    case boolTag$1:
    case dateTag$1:
    case numberTag$1:
      // Coerce booleans to `1` or `0` and dates to milliseconds.
      // Invalid dates are coerced to `NaN`.
      return eq_1(+object, +other);

    case errorTag$1:
      return object.name == other.name && object.message == other.message;

    case regexpTag$1:
    case stringTag$1:
      // Coerce regexes to strings and treat strings, primitives and objects,
      // as equal. See http://www.ecma-international.org/ecma-262/7.0/#sec-regexp.prototype.tostring
      // for more details.
      return object == `${other  }`;

    case mapTag$1:
      var convert = _mapToArray;

    case setTag$1:
      var isPartial = bitmask & COMPARE_PARTIAL_FLAG$1;
      convert || (convert = _setToArray);

      if (object.size != other.size && !isPartial) {
        return false;
      } // Assume cyclic values are equal.


      var stacked = stack.get(object);

      if (stacked) {
        return stacked == other;
      }

      bitmask |= COMPARE_UNORDERED_FLAG$1; // Recursively compare objects (susceptible to call stack limits).

      stack.set(object, other);
      var result = _equalArrays(convert(object), convert(other), bitmask, customizer, equalFunc, stack);
      stack['delete'](object);
      return result;

    case symbolTag:
      if (symbolValueOf) {
        return symbolValueOf.call(object) == symbolValueOf.call(other);
      }
  }

  return false;
}

let _equalByTag = equalByTag;

/**
 * Appends the elements of `values` to `array`.
 *
 * @private
 * @param {Array} array The array to modify.
 * @param {Array} values The values to append.
 * @returns {Array} Returns `array`.
 */
function arrayPush(array, values) {
  let index = -1,
    length = values.length,
    offset = array.length;

  while (++index < length) {
    array[offset + index] = values[index];
  }

  return array;
}

let _arrayPush = arrayPush;

/**
 * The base implementation of `getAllKeys` and `getAllKeysIn` which uses
 * `keysFunc` and `symbolsFunc` to get the enumerable property names and
 * symbols of `object`.
 *
 * @private
 * @param {Object} object The object to query.
 * @param {Function} keysFunc The function to get the keys of `object`.
 * @param {Function} symbolsFunc The function to get the symbols of `object`.
 * @returns {Array} Returns the array of property names and symbols.
 */

function baseGetAllKeys(object, keysFunc, symbolsFunc) {
  let result = keysFunc(object);
  return isArray_1(object) ? result : _arrayPush(result, symbolsFunc(object));
}

let _baseGetAllKeys = baseGetAllKeys;

/**
 * A specialized version of `_.filter` for arrays without support for
 * iteratee shorthands.
 *
 * @private
 * @param {Array} [array] The array to iterate over.
 * @param {Function} predicate The function invoked per iteration.
 * @returns {Array} Returns the new filtered array.
 */
function arrayFilter(array, predicate) {
  let index = -1,
    length = array == null ? 0 : array.length,
    resIndex = 0,
    result = [];

  while (++index < length) {
    let value = array[index];

    if (predicate(value, index, array)) {
      result[resIndex++] = value;
    }
  }

  return result;
}

let _arrayFilter = arrayFilter;

/**
 * This method returns a new empty array.
 *
 * @static
 * @memberOf _
 * @since 4.13.0
 * @category Util
 * @returns {Array} Returns the new empty array.
 * @example
 *
 * var arrays = _.times(2, _.stubArray);
 *
 * console.log(arrays);
 * // => [[], []]
 *
 * console.log(arrays[0] === arrays[1]);
 * // => false
 */
function stubArray() {
  return [];
}

let stubArray_1 = stubArray;

/** Used for built-in method references. */

let objectProto$9 = Object.prototype;
/** Built-in value references. */

let propertyIsEnumerable$1 = objectProto$9.propertyIsEnumerable;
/* Built-in method references for those with the same name as other `lodash` methods. */

let nativeGetSymbols = Object.getOwnPropertySymbols;
/**
 * Creates an array of the own enumerable symbols of `object`.
 *
 * @private
 * @param {Object} object The object to query.
 * @returns {Array} Returns the array of symbols.
 */

let getSymbols = !nativeGetSymbols ? stubArray_1 : function (object) {
  if (object == null) {
    return [];
  }

  object = Object(object);
  return _arrayFilter(nativeGetSymbols(object), (symbol) => {
    return propertyIsEnumerable$1.call(object, symbol);
  });
};
let _getSymbols = getSymbols;

/**
 * Creates an array of own enumerable property names and symbols of `object`.
 *
 * @private
 * @param {Object} object The object to query.
 * @returns {Array} Returns the array of property names and symbols.
 */

function getAllKeys(object) {
  return _baseGetAllKeys(object, keys_1, _getSymbols);
}

let _getAllKeys = getAllKeys;

/** Used to compose bitmasks for value comparisons. */

let COMPARE_PARTIAL_FLAG$2 = 1;
/** Used for built-in method references. */

let objectProto$10 = Object.prototype;
/** Used to check objects for own properties. */

let hasOwnProperty$7 = objectProto$10.hasOwnProperty;
/**
 * A specialized version of `baseIsEqualDeep` for objects with support for
 * partial deep comparisons.
 *
 * @private
 * @param {Object} object The object to compare.
 * @param {Object} other The other object to compare.
 * @param {number} bitmask The bitmask flags. See `baseIsEqual` for more details.
 * @param {Function} customizer The function to customize comparisons.
 * @param {Function} equalFunc The function to determine equivalents of values.
 * @param {Object} stack Tracks traversed `object` and `other` objects.
 * @returns {boolean} Returns `true` if the objects are equivalent, else `false`.
 */

function equalObjects(object, other, bitmask, customizer, equalFunc, stack) {
  let isPartial = bitmask & COMPARE_PARTIAL_FLAG$2,
    objProps = _getAllKeys(object),
    objLength = objProps.length,
    othProps = _getAllKeys(other),
    othLength = othProps.length;

  if (objLength != othLength && !isPartial) {
    return false;
  }

  let index = objLength;

  while (index--) {
    var key = objProps[index];

    if (!(isPartial ? key in other : hasOwnProperty$7.call(other, key))) {
      return false;
    }
  } // Assume cyclic values are equal.


  let stacked = stack.get(object);

  if (stacked && stack.get(other)) {
    return stacked == other;
  }

  let result = true;
  stack.set(object, other);
  stack.set(other, object);
  let skipCtor = isPartial;

  while (++index < objLength) {
    key = objProps[index];
    let objValue = object[key],
      othValue = other[key];

    if (customizer) {
      var compared = isPartial ? customizer(othValue, objValue, key, other, object, stack) : customizer(objValue, othValue, key, object, other, stack);
    } // Recursively compare objects (susceptible to call stack limits).


    if (!(compared === undefined ? objValue === othValue || equalFunc(objValue, othValue, bitmask, customizer, stack) : compared)) {
      result = false;
      break;
    }

    skipCtor || (skipCtor = key == 'constructor');
  }

  if (result && !skipCtor) {
    let objCtor = object.constructor,
      othCtor = other.constructor; // Non `Object` object instances with different constructors are not equal.

    if (objCtor != othCtor && 'constructor' in object && 'constructor' in other && !(typeof objCtor === 'function' && objCtor instanceof objCtor && typeof othCtor === 'function' && othCtor instanceof othCtor)) {
      result = false;
    }
  }

  stack['delete'](object);
  stack['delete'](other);
  return result;
}

let _equalObjects = equalObjects;

/* Built-in method references that are verified to be native. */

let DataView = _getNative(_root, 'DataView');
let _DataView = DataView;

/* Built-in method references that are verified to be native. */

let Promise$1 = _getNative(_root, 'Promise');
let _Promise = Promise$1;

/* Built-in method references that are verified to be native. */

let Set = _getNative(_root, 'Set');
let _Set = Set;

/* Built-in method references that are verified to be native. */

let WeakMap = _getNative(_root, 'WeakMap');
let _WeakMap = WeakMap;

/** `Object#toString` result references. */

let mapTag$2 = '[object Map]';
let objectTag$1 = '[object Object]';
let promiseTag = '[object Promise]';
let setTag$2 = '[object Set]';
let weakMapTag$1 = '[object WeakMap]';
let dataViewTag$2 = '[object DataView]';
/** Used to detect maps, sets, and weakmaps. */

let dataViewCtorString = _toSource(_DataView);
let mapCtorString = _toSource(_Map);
let promiseCtorString = _toSource(_Promise);
let setCtorString = _toSource(_Set);
let weakMapCtorString = _toSource(_WeakMap);
/**
 * Gets the `toStringTag` of `value`.
 *
 * @private
 * @param {*} value The value to query.
 * @returns {string} Returns the `toStringTag`.
 */

let getTag = _baseGetTag; // Fallback for data views, maps, sets, and weak maps in IE 11 and promises in Node.js < 6.

if (_DataView && getTag(new _DataView(new ArrayBuffer(1))) != dataViewTag$2 || _Map && getTag(new _Map()) != mapTag$2 || _Promise && getTag(_Promise.resolve()) != promiseTag || _Set && getTag(new _Set()) != setTag$2 || _WeakMap && getTag(new _WeakMap()) != weakMapTag$1) {
  getTag = function (value) {
    let result = _baseGetTag(value),
      Ctor = result == objectTag$1 ? value.constructor : undefined,
      ctorString = Ctor ? _toSource(Ctor) : '';

    if (ctorString) {
      switch (ctorString) {
        case dataViewCtorString:
          return dataViewTag$2;

        case mapCtorString:
          return mapTag$2;

        case promiseCtorString:
          return promiseTag;

        case setCtorString:
          return setTag$2;

        case weakMapCtorString:
          return weakMapTag$1;
      }
    }

    return result;
  };
}

let _getTag = getTag;

/** Used to compose bitmasks for value comparisons. */

let COMPARE_PARTIAL_FLAG$3 = 1;
/** `Object#toString` result references. */

let argsTag$2 = '[object Arguments]';
let arrayTag$1 = '[object Array]';
let objectTag$2 = '[object Object]';
/** Used for built-in method references. */

let objectProto$11 = Object.prototype;
/** Used to check objects for own properties. */

let hasOwnProperty$8 = objectProto$11.hasOwnProperty;
/**
 * A specialized version of `baseIsEqual` for arrays and objects which performs
 * deep comparisons and tracks traversed objects enabling objects with circular
 * references to be compared.
 *
 * @private
 * @param {Object} object The object to compare.
 * @param {Object} other The other object to compare.
 * @param {number} bitmask The bitmask flags. See `baseIsEqual` for more details.
 * @param {Function} customizer The function to customize comparisons.
 * @param {Function} equalFunc The function to determine equivalents of values.
 * @param {Object} [stack] Tracks traversed `object` and `other` objects.
 * @returns {boolean} Returns `true` if the objects are equivalent, else `false`.
 */

function baseIsEqualDeep(object, other, bitmask, customizer, equalFunc, stack) {
  let objIsArr = isArray_1(object),
    othIsArr = isArray_1(other),
    objTag = objIsArr ? arrayTag$1 : _getTag(object),
    othTag = othIsArr ? arrayTag$1 : _getTag(other);
  objTag = objTag == argsTag$2 ? objectTag$2 : objTag;
  othTag = othTag == argsTag$2 ? objectTag$2 : othTag;
  let objIsObj = objTag == objectTag$2,
    othIsObj = othTag == objectTag$2,
    isSameTag = objTag == othTag;

  if (isSameTag && isBuffer_1(object)) {
    if (!isBuffer_1(other)) {
      return false;
    }

    objIsArr = true;
    objIsObj = false;
  }

  if (isSameTag && !objIsObj) {
    stack || (stack = new _Stack());
    return objIsArr || isTypedArray_1(object) ? _equalArrays(object, other, bitmask, customizer, equalFunc, stack) : _equalByTag(object, other, objTag, bitmask, customizer, equalFunc, stack);
  }

  if (!(bitmask & COMPARE_PARTIAL_FLAG$3)) {
    let objIsWrapped = objIsObj && hasOwnProperty$8.call(object, '__wrapped__'),
      othIsWrapped = othIsObj && hasOwnProperty$8.call(other, '__wrapped__');

    if (objIsWrapped || othIsWrapped) {
      let objUnwrapped = objIsWrapped ? object.value() : object,
        othUnwrapped = othIsWrapped ? other.value() : other;
      stack || (stack = new _Stack());
      return equalFunc(objUnwrapped, othUnwrapped, bitmask, customizer, stack);
    }
  }

  if (!isSameTag) {
    return false;
  }

  stack || (stack = new _Stack());
  return _equalObjects(object, other, bitmask, customizer, equalFunc, stack);
}

let _baseIsEqualDeep = baseIsEqualDeep;

/**
 * The base implementation of `_.isEqual` which supports partial comparisons
 * and tracks traversed objects.
 *
 * @private
 * @param {*} value The value to compare.
 * @param {*} other The other value to compare.
 * @param {boolean} bitmask The bitmask flags.
 *  1 - Unordered comparison
 *  2 - Partial comparison
 * @param {Function} [customizer] The function to customize comparisons.
 * @param {Object} [stack] Tracks traversed `value` and `other` objects.
 * @returns {boolean} Returns `true` if the values are equivalent, else `false`.
 */

function baseIsEqual(value, other, bitmask, customizer, stack) {
  if (value === other) {
    return true;
  }

  if (value == null || other == null || !isObjectLike_1(value) && !isObjectLike_1(other)) {
    return value !== value && other !== other;
  }

  return _baseIsEqualDeep(value, other, bitmask, customizer, baseIsEqual, stack);
}

let _baseIsEqual = baseIsEqual;

/** Used to compose bitmasks for value comparisons. */

let COMPARE_PARTIAL_FLAG$4 = 1;
let COMPARE_UNORDERED_FLAG$2 = 2;
/**
 * The base implementation of `_.isMatch` without support for iteratee shorthands.
 *
 * @private
 * @param {Object} object The object to inspect.
 * @param {Object} source The object of property values to match.
 * @param {Array} matchData The property names, values, and compare flags to match.
 * @param {Function} [customizer] The function to customize comparisons.
 * @returns {boolean} Returns `true` if `object` is a match, else `false`.
 */

function baseIsMatch(object, source, matchData, customizer) {
  let index = matchData.length,
    length = index,
    noCustomizer = !customizer;

  if (object == null) {
    return !length;
  }

  object = Object(object);

  while (index--) {
    var data = matchData[index];

    if (noCustomizer && data[2] ? data[1] !== object[data[0]] : !(data[0] in object)) {
      return false;
    }
  }

  while (++index < length) {
    data = matchData[index];
    let key = data[0],
      objValue = object[key],
      srcValue = data[1];

    if (noCustomizer && data[2]) {
      if (objValue === undefined && !(key in object)) {
        return false;
      }
    }
 else {
      let stack = new _Stack();

      if (customizer) {
        var result = customizer(objValue, srcValue, key, object, source, stack);
      }

      if (!(result === undefined ? _baseIsEqual(srcValue, objValue, COMPARE_PARTIAL_FLAG$4 | COMPARE_UNORDERED_FLAG$2, customizer, stack) : result)) {
        return false;
      }
    }
  }

  return true;
}

let _baseIsMatch = baseIsMatch;

/**
 * Checks if `value` is suitable for strict equality comparisons, i.e. `===`.
 *
 * @private
 * @param {*} value The value to check.
 * @returns {boolean} Returns `true` if `value` if suitable for strict
 *  equality comparisons, else `false`.
 */

function isStrictComparable(value) {
  return value === value && !isObject_1(value);
}

let _isStrictComparable = isStrictComparable;

/**
 * Gets the property names, values, and compare flags of `object`.
 *
 * @private
 * @param {Object} object The object to query.
 * @returns {Array} Returns the match data of `object`.
 */

function getMatchData(object) {
  let result = keys_1(object),
    length = result.length;

  while (length--) {
    let key = result[length],
      value = object[key];
    result[length] = [key, value, _isStrictComparable(value)];
  }

  return result;
}

let _getMatchData = getMatchData;

/**
 * A specialized version of `matchesProperty` for source values suitable
 * for strict equality comparisons, i.e. `===`.
 *
 * @private
 * @param {string} key The key of the property to get.
 * @param {*} srcValue The value to match.
 * @returns {Function} Returns the new spec function.
 */
function matchesStrictComparable(key, srcValue) {
  return function (object) {
    if (object == null) {
      return false;
    }

    return object[key] === srcValue && (srcValue !== undefined || key in Object(object));
  };
}

let _matchesStrictComparable = matchesStrictComparable;

/**
 * The base implementation of `_.matches` which doesn't clone `source`.
 *
 * @private
 * @param {Object} source The object of property values to match.
 * @returns {Function} Returns the new spec function.
 */

function baseMatches(source) {
  let matchData = _getMatchData(source);

  if (matchData.length == 1 && matchData[0][2]) {
    return _matchesStrictComparable(matchData[0][0], matchData[0][1]);
  }

  return function (object) {
    return object === source || _baseIsMatch(object, source, matchData);
  };
}

let _baseMatches = baseMatches;

/** `Object#toString` result references. */

let symbolTag$1 = '[object Symbol]';
/**
 * Checks if `value` is classified as a `Symbol` primitive or object.
 *
 * @static
 * @memberOf _
 * @since 4.0.0
 * @category Lang
 * @param {*} value The value to check.
 * @returns {boolean} Returns `true` if `value` is a symbol, else `false`.
 * @example
 *
 * _.isSymbol(Symbol.iterator);
 * // => true
 *
 * _.isSymbol('abc');
 * // => false
 */

function isSymbol(value) {
  return typeof value === 'symbol' || isObjectLike_1(value) && _baseGetTag(value) == symbolTag$1;
}

let isSymbol_1 = isSymbol;

/** Used to match property names within property paths. */

let reIsDeepProp = /\.|\[(?:[^[\]]*|(["'])(?:(?!\1)[^\\]|\\.)*?\1)\]/;
let reIsPlainProp = /^\w*$/;
/**
 * Checks if `value` is a property name and not a property path.
 *
 * @private
 * @param {*} value The value to check.
 * @param {Object} [object] The object to query keys on.
 * @returns {boolean} Returns `true` if `value` is a property name, else `false`.
 */

function isKey(value, object) {
  if (isArray_1(value)) {
    return false;
  }

  let type = typeof value;

  if (type == 'number' || type == 'symbol' || type == 'boolean' || value == null || isSymbol_1(value)) {
    return true;
  }

  return reIsPlainProp.test(value) || !reIsDeepProp.test(value) || object != null && value in Object(object);
}

let _isKey = isKey;

/** Error message constants. */

let FUNC_ERROR_TEXT = 'Expected a function';
/**
 * Creates a function that memoizes the result of `func`. If `resolver` is
 * provided, it determines the cache key for storing the result based on the
 * arguments provided to the memoized function. By default, the first argument
 * provided to the memoized function is used as the map cache key. The `func`
 * is invoked with the `this` binding of the memoized function.
 *
 * **Note:** The cache is exposed as the `cache` property on the memoized
 * function. Its creation may be customized by replacing the `_.memoize.Cache`
 * constructor with one whose instances implement the
 * [`Map`](http://ecma-international.org/ecma-262/7.0/#sec-properties-of-the-map-prototype-object)
 * method interface of `clear`, `delete`, `get`, `has`, and `set`.
 *
 * @static
 * @memberOf _
 * @since 0.1.0
 * @category Function
 * @param {Function} func The function to have its output memoized.
 * @param {Function} [resolver] The function to resolve the cache key.
 * @returns {Function} Returns the new memoized function.
 * @example
 *
 * var object = { 'a': 1, 'b': 2 };
 * var other = { 'c': 3, 'd': 4 };
 *
 * var values = _.memoize(_.values);
 * values(object);
 * // => [1, 2]
 *
 * values(other);
 * // => [3, 4]
 *
 * object.a = 2;
 * values(object);
 * // => [1, 2]
 *
 * // Modify the result cache.
 * values.cache.set(object, ['a', 'b']);
 * values(object);
 * // => ['a', 'b']
 *
 * // Replace `_.memoize.Cache`.
 * _.memoize.Cache = WeakMap;
 */

function memoize(func, resolver) {
  if (typeof func !== 'function' || resolver != null && typeof resolver !== 'function') {
    throw new TypeError(FUNC_ERROR_TEXT);
  }

  var memoized = function () {
    let args = arguments,
      key = resolver ? resolver.apply(this, args) : args[0],
      cache = memoized.cache;

    if (cache.has(key)) {
      return cache.get(key);
    }

    let result = func.apply(this, args);
    memoized.cache = cache.set(key, result) || cache;
    return result;
  };

  memoized.cache = new (memoize.Cache || _MapCache)();
  return memoized;
} // Expose `MapCache`.


memoize.Cache = _MapCache;
let memoize_1 = memoize;

/** Used as the maximum memoize cache size. */

let MAX_MEMOIZE_SIZE = 500;
/**
 * A specialized version of `_.memoize` which clears the memoized function's
 * cache when it exceeds `MAX_MEMOIZE_SIZE`.
 *
 * @private
 * @param {Function} func The function to have its output memoized.
 * @returns {Function} Returns the new memoized function.
 */

function memoizeCapped(func) {
  let result = memoize_1(func, (key) => {
    if (cache.size === MAX_MEMOIZE_SIZE) {
      cache.clear();
    }

    return key;
  });
  var cache = result.cache;
  return result;
}

let _memoizeCapped = memoizeCapped;

/** Used to match property names within property paths. */

let rePropName = /[^.[\]]+|\[(?:(-?\d+(?:\.\d+)?)|(["'])((?:(?!\2)[^\\]|\\.)*?)\2)\]|(?=(?:\.|\[\])(?:\.|\[\]|$))/g;
/** Used to match backslashes in property paths. */

let reEscapeChar = /\\(\\)?/g;
/**
 * Converts `string` to a property path array.
 *
 * @private
 * @param {string} string The string to convert.
 * @returns {Array} Returns the property path array.
 */

let stringToPath = _memoizeCapped((string) => {
  var result = [];

  if (string.charCodeAt(0) === 46
  /* . */
  ) {
      result.push('');
    }

  string.replace(rePropName, function (match, number, quote, subString) {
    result.push(quote ? subString.replace(reEscapeChar, '$1') : number || match);
  });
  return result;
});
let _stringToPath = stringToPath;

/**
 * A specialized version of `_.map` for arrays without support for iteratee
 * shorthands.
 *
 * @private
 * @param {Array} [array] The array to iterate over.
 * @param {Function} iteratee The function invoked per iteration.
 * @returns {Array} Returns the new mapped array.
 */
function arrayMap(array, iteratee) {
  let index = -1,
    length = array == null ? 0 : array.length,
    result = Array(length);

  while (++index < length) {
    result[index] = iteratee(array[index], index, array);
  }

  return result;
}

let _arrayMap = arrayMap;

/** Used as references for various `Number` constants. */

let INFINITY = 1 / 0;
/** Used to convert symbols to primitives and strings. */

let symbolProto$1 = _Symbol ? _Symbol.prototype : undefined;
let symbolToString = symbolProto$1 ? symbolProto$1.toString : undefined;
/**
 * The base implementation of `_.toString` which doesn't convert nullish
 * values to empty strings.
 *
 * @private
 * @param {*} value The value to process.
 * @returns {string} Returns the string.
 */

function baseToString(value) {
  // Exit early for strings to avoid a performance hit in some environments.
  if (typeof value === 'string') {
    return value;
  }

  if (isArray_1(value)) {
    // Recursively convert values (susceptible to call stack limits).
    return `${_arrayMap(value, baseToString)  }`;
  }

  if (isSymbol_1(value)) {
    return symbolToString ? symbolToString.call(value) : '';
  }

  let result = `${value  }`;
  return result == '0' && 1 / value == -INFINITY ? '-0' : result;
}

let _baseToString = baseToString;

/**
 * Converts `value` to a string. An empty string is returned for `null`
 * and `undefined` values. The sign of `-0` is preserved.
 *
 * @static
 * @memberOf _
 * @since 4.0.0
 * @category Lang
 * @param {*} value The value to convert.
 * @returns {string} Returns the converted string.
 * @example
 *
 * _.toString(null);
 * // => ''
 *
 * _.toString(-0);
 * // => '-0'
 *
 * _.toString([1, 2, 3]);
 * // => '1,2,3'
 */

function toString(value) {
  return value == null ? '' : _baseToString(value);
}

let toString_1 = toString;

/**
 * Casts `value` to a path array if it's not one.
 *
 * @private
 * @param {*} value The value to inspect.
 * @param {Object} [object] The object to query keys on.
 * @returns {Array} Returns the cast property path array.
 */

function castPath(value, object) {
  if (isArray_1(value)) {
    return value;
  }

  return _isKey(value, object) ? [value] : _stringToPath(toString_1(value));
}

let _castPath = castPath;

/** Used as references for various `Number` constants. */

let INFINITY$1 = 1 / 0;
/**
 * Converts `value` to a string key if it's not a string or symbol.
 *
 * @private
 * @param {*} value The value to inspect.
 * @returns {string|symbol} Returns the key.
 */

function toKey(value) {
  if (typeof value === 'string' || isSymbol_1(value)) {
    return value;
  }

  let result = `${value  }`;
  return result == '0' && 1 / value == -INFINITY$1 ? '-0' : result;
}

let _toKey = toKey;

/**
 * The base implementation of `_.get` without support for default values.
 *
 * @private
 * @param {Object} object The object to query.
 * @param {Array|string} path The path of the property to get.
 * @returns {*} Returns the resolved value.
 */

function baseGet(object, path) {
  path = _castPath(path, object);
  let index = 0,
    length = path.length;

  while (object != null && index < length) {
    object = object[_toKey(path[index++])];
  }

  return index && index == length ? object : undefined;
}

let _baseGet = baseGet;

/**
 * Gets the value at `path` of `object`. If the resolved value is
 * `undefined`, the `defaultValue` is returned in its place.
 *
 * @static
 * @memberOf _
 * @since 3.7.0
 * @category Object
 * @param {Object} object The object to query.
 * @param {Array|string} path The path of the property to get.
 * @param {*} [defaultValue] The value returned for `undefined` resolved values.
 * @returns {*} Returns the resolved value.
 * @example
 *
 * var object = { 'a': [{ 'b': { 'c': 3 } }] };
 *
 * _.get(object, 'a[0].b.c');
 * // => 3
 *
 * _.get(object, ['a', '0', 'b', 'c']);
 * // => 3
 *
 * _.get(object, 'a.b.c', 'default');
 * // => 'default'
 */

function get(object, path, defaultValue) {
  let result = object == null ? undefined : _baseGet(object, path);
  return result === undefined ? defaultValue : result;
}

let get_1 = get;

/**
 * The base implementation of `_.hasIn` without support for deep paths.
 *
 * @private
 * @param {Object} [object] The object to query.
 * @param {Array|string} key The key to check.
 * @returns {boolean} Returns `true` if `key` exists, else `false`.
 */
function baseHasIn(object, key) {
  return object != null && key in Object(object);
}

let _baseHasIn = baseHasIn;

/**
 * Checks if `path` exists on `object`.
 *
 * @private
 * @param {Object} object The object to query.
 * @param {Array|string} path The path to check.
 * @param {Function} hasFunc The function to check properties.
 * @returns {boolean} Returns `true` if `path` exists, else `false`.
 */

function hasPath(object, path, hasFunc) {
  path = _castPath(path, object);
  let index = -1,
    length = path.length,
    result = false;

  while (++index < length) {
    var key = _toKey(path[index]);

    if (!(result = object != null && hasFunc(object, key))) {
      break;
    }

    object = object[key];
  }

  if (result || ++index != length) {
    return result;
  }

  length = object == null ? 0 : object.length;
  return !!length && isLength_1(length) && _isIndex(key, length) && (isArray_1(object) || isArguments_1(object));
}

let _hasPath = hasPath;

/**
 * Checks if `path` is a direct or inherited property of `object`.
 *
 * @static
 * @memberOf _
 * @since 4.0.0
 * @category Object
 * @param {Object} object The object to query.
 * @param {Array|string} path The path to check.
 * @returns {boolean} Returns `true` if `path` exists, else `false`.
 * @example
 *
 * var object = _.create({ 'a': _.create({ 'b': 2 }) });
 *
 * _.hasIn(object, 'a');
 * // => true
 *
 * _.hasIn(object, 'a.b');
 * // => true
 *
 * _.hasIn(object, ['a', 'b']);
 * // => true
 *
 * _.hasIn(object, 'b');
 * // => false
 */

function hasIn(object, path) {
  return object != null && _hasPath(object, path, _baseHasIn);
}

let hasIn_1 = hasIn;

/** Used to compose bitmasks for value comparisons. */

let COMPARE_PARTIAL_FLAG$5 = 1;
let COMPARE_UNORDERED_FLAG$3 = 2;
/**
 * The base implementation of `_.matchesProperty` which doesn't clone `srcValue`.
 *
 * @private
 * @param {string} path The path of the property to get.
 * @param {*} srcValue The value to match.
 * @returns {Function} Returns the new spec function.
 */

function baseMatchesProperty(path, srcValue) {
  if (_isKey(path) && _isStrictComparable(srcValue)) {
    return _matchesStrictComparable(_toKey(path), srcValue);
  }

  return function (object) {
    let objValue = get_1(object, path);
    return objValue === undefined && objValue === srcValue ? hasIn_1(object, path) : _baseIsEqual(srcValue, objValue, COMPARE_PARTIAL_FLAG$5 | COMPARE_UNORDERED_FLAG$3);
  };
}

let _baseMatchesProperty = baseMatchesProperty;

/**
 * This method returns the first argument it receives.
 *
 * @static
 * @since 0.1.0
 * @memberOf _
 * @category Util
 * @param {*} value Any value.
 * @returns {*} Returns `value`.
 * @example
 *
 * var object = { 'a': 1 };
 *
 * console.log(_.identity(object) === object);
 * // => true
 */
function identity(value) {
  return value;
}

let identity_1 = identity;

/**
 * The base implementation of `_.property` without support for deep paths.
 *
 * @private
 * @param {string} key The key of the property to get.
 * @returns {Function} Returns the new accessor function.
 */
function baseProperty(key) {
  return function (object) {
    return object == null ? undefined : object[key];
  };
}

let _baseProperty = baseProperty;

/**
 * A specialized version of `baseProperty` which supports deep paths.
 *
 * @private
 * @param {Array|string} path The path of the property to get.
 * @returns {Function} Returns the new accessor function.
 */

function basePropertyDeep(path) {
  return function (object) {
    return _baseGet(object, path);
  };
}

let _basePropertyDeep = basePropertyDeep;

/**
 * Creates a function that returns the value at `path` of a given object.
 *
 * @static
 * @memberOf _
 * @since 2.4.0
 * @category Util
 * @param {Array|string} path The path of the property to get.
 * @returns {Function} Returns the new accessor function.
 * @example
 *
 * var objects = [
 *   { 'a': { 'b': 2 } },
 *   { 'a': { 'b': 1 } }
 * ];
 *
 * _.map(objects, _.property('a.b'));
 * // => [2, 1]
 *
 * _.map(_.sortBy(objects, _.property(['a', 'b'])), 'a.b');
 * // => [1, 2]
 */

function property(path) {
  return _isKey(path) ? _baseProperty(_toKey(path)) : _basePropertyDeep(path);
}

let property_1 = property;

/**
 * The base implementation of `_.iteratee`.
 *
 * @private
 * @param {*} [value=_.identity] The value to convert to an iteratee.
 * @returns {Function} Returns the iteratee.
 */

function baseIteratee(value) {
  // Don't store the `typeof` result in a variable to avoid a JIT bug in Safari 9.
  // See https://bugs.webkit.org/show_bug.cgi?id=156034 for more details.
  if (typeof value === 'function') {
    return value;
  }

  if (value == null) {
    return identity_1;
  }

  if (typeof value === 'object') {
    return isArray_1(value) ? _baseMatchesProperty(value[0], value[1]) : _baseMatches(value);
  }

  return property_1(value);
}

let _baseIteratee = baseIteratee;

/**
 * Creates a function like `_.groupBy`.
 *
 * @private
 * @param {Function} setter The function to set accumulator values.
 * @param {Function} [initializer] The accumulator object initializer.
 * @returns {Function} Returns the new aggregator function.
 */

function createAggregator(setter, initializer) {
  return function (collection, iteratee) {
    let func = isArray_1(collection) ? _arrayAggregator : _baseAggregator,
      accumulator = initializer ? initializer() : {};
    return func(collection, setter, _baseIteratee(iteratee, 2), accumulator);
  };
}

let _createAggregator = createAggregator;

/**
 * Creates an object composed of keys generated from the results of running
 * each element of `collection` thru `iteratee`. The corresponding value of
 * each key is the last element responsible for generating the key. The
 * iteratee is invoked with one argument: (value).
 *
 * @static
 * @memberOf _
 * @since 4.0.0
 * @category Collection
 * @param {Array|Object} collection The collection to iterate over.
 * @param {Function} [iteratee=_.identity] The iteratee to transform keys.
 * @returns {Object} Returns the composed aggregate object.
 * @example
 *
 * var array = [
 *   { 'dir': 'left', 'code': 97 },
 *   { 'dir': 'right', 'code': 100 }
 * ];
 *
 * _.keyBy(array, function(o) {
 *   return String.fromCharCode(o.code);
 * });
 * // => { 'a': { 'dir': 'left', 'code': 97 }, 'd': { 'dir': 'right', 'code': 100 } }
 *
 * _.keyBy(array, 'dir');
 * // => { 'left': { 'dir': 'left', 'code': 97 }, 'right': { 'dir': 'right', 'code': 100 } }
 */

let keyBy = _createAggregator((result, value, key) => {
  _baseAssignValue(result, key, value);
});
let keyBy_1 = keyBy;

/**
 * Creates an object with the same keys as `object` and values generated
 * by running each own enumerable string keyed property of `object` thru
 * `iteratee`. The iteratee is invoked with three arguments:
 * (value, key, object).
 *
 * @static
 * @memberOf _
 * @since 2.4.0
 * @category Object
 * @param {Object} object The object to iterate over.
 * @param {Function} [iteratee=_.identity] The function invoked per iteration.
 * @returns {Object} Returns the new mapped object.
 * @see _.mapKeys
 * @example
 *
 * var users = {
 *   'fred':    { 'user': 'fred',    'age': 40 },
 *   'pebbles': { 'user': 'pebbles', 'age': 1 }
 * };
 *
 * _.mapValues(users, function(o) { return o.age; });
 * // => { 'fred': 40, 'pebbles': 1 } (iteration order is not guaranteed)
 *
 * // The `_.property` iteratee shorthand.
 * _.mapValues(users, 'age');
 * // => { 'fred': 40, 'pebbles': 1 } (iteration order is not guaranteed)
 */

function mapValues(object, iteratee) {
  let result = {};
  iteratee = _baseIteratee(iteratee, 3);
  _baseForOwn(object, (value, key, object) => {
    _baseAssignValue(result, key, iteratee(value, key, object));
  });
  return result;
}

let mapValues_1 = mapValues;

function _classCallCheck(instance, Constructor) {
  if (!(instance instanceof Constructor)) {
    throw new TypeError('Cannot call a class as a function');
  }
}

function _defineProperties(target, props) {
  for (let i = 0; i < props.length; i++) {
    let descriptor = props[i];
    descriptor.enumerable = descriptor.enumerable || false;
    descriptor.configurable = true;
    if ('value' in descriptor) descriptor.writable = true;
    Object.defineProperty(target, descriptor.key, descriptor);
  }
}

function _createClass(Constructor, protoProps, staticProps) {
  if (protoProps) _defineProperties(Constructor.prototype, protoProps);
  if (staticProps) _defineProperties(Constructor, staticProps);
  return Constructor;
}

function _defineProperty$2(obj, key, value) {
  if (key in obj) {
    Object.defineProperty(obj, key, {
      value,
      enumerable: true,
      configurable: true,
      writable: true,
    });
  }
 else {
    obj[key] = value;
  }

  return obj;
}

function _objectSpread(target) {
  for (let i = 1; i < arguments.length; i++) {
    var source = arguments[i] != null ? arguments[i] : {};
    let ownKeys = Object.keys(source);

    if (typeof Object.getOwnPropertySymbols === 'function') {
      ownKeys = ownKeys.concat(Object.getOwnPropertySymbols(source).filter((sym) => {
        return Object.getOwnPropertyDescriptor(source, sym).enumerable;
      }));
    }

    ownKeys.forEach((key) => {
      _defineProperty$2(target, key, source[key]);
    });
  }

  return target;
}

function _toConsumableArray(arr) {
  return _arrayWithoutHoles(arr) || _iterableToArray(arr) || _nonIterableSpread();
}

function _arrayWithoutHoles(arr) {
  if (Array.isArray(arr)) {
    for (var i = 0, arr2 = new Array(arr.length); i < arr.length; i++) arr2[i] = arr[i];

    return arr2;
  }
}

function _iterableToArray(iter) {
  if (Symbol.iterator in Object(iter) || Object.prototype.toString.call(iter) === '[object Arguments]') return Array.from(iter);
}

function _nonIterableSpread() {
  throw new TypeError('Invalid attempt to spread non-iterable instance');
}

let parser = createCommonjsModule((module, exports) => {
  // Generated by CoffeeScript 2.0.2
  (function () {
    exports.load = function (received, defaults, onto = {}) {
      var k, ref, v;

      for (k in defaults) {
        v = defaults[k];
        onto[k] = (ref = received[k]) != null ? ref : v;
      }

      return onto;
    };

    exports.overwrite = function (received, defaults, onto = {}) {
      var k, v;

      for (k in received) {
        v = received[k];

        if (defaults[k] !== void 0) {
          onto[k] = v;
        }
      }

      return onto;
    };
  }).call(commonjsGlobal);
});
let parser_1 = parser.load;
let parser_2 = parser.overwrite;

let DLList = createCommonjsModule((module) => {
  // Generated by CoffeeScript 2.0.2
  (function () {
    var DLList;
    DLList = class DLList {
      constructor() {
        this._first = null;
        this._last = null;
        this.length = 0;
      }

      push(value) {
        var node;
        this.length++;
        node = {
          value,
          next: null
        };

        if (this._last != null) {
          this._last.next = node;
          this._last = node;
        } else {
          this._first = this._last = node;
        }

        return void 0;
      }

      shift() {
        var ref1, value;

        if (this._first == null) {
          return void 0;
        } else {
          this.length--;
        }

        value = this._first.value;
        this._first = (ref1 = this._first.next) != null ? ref1 : this._last = null;
        return value;
      }

      first() {
        if (this._first != null) {
          return this._first.value;
        }
      }

      getArray() {
        var node, ref, results;
        node = this._first;
        results = [];

        while (node != null) {
          results.push((ref = node, node = node.next, ref.value));
        }

        return results;
      }

    };
    module.exports = DLList;
  }).call(commonjsGlobal);
});

let BottleneckError = createCommonjsModule((module) => {
  // Generated by CoffeeScript 2.0.2
  (function () {
    var BottleneckError;
    BottleneckError = class BottleneckError extends Error {};
    module.exports = BottleneckError;
  }).call(commonjsGlobal);
});

let Local = createCommonjsModule((module) => {
  // Generated by CoffeeScript 2.0.2
  (function () {
    var BottleneckError$$1, DLList$$1, Local, parser$$1;
    parser$$1 = parser;
    BottleneckError$$1 = BottleneckError;
    Local = class Local {
      constructor(options) {
        parser$$1.load(options, options, this);
        this._nextRequest = Date.now();
        this._running = 0;
        this._executing = {};
        this._unblockTime = 0;
        this.ready = this.yieldLoop();
        this.clients = {};
      }

      disconnect(flush) {
        return this;
      }

      yieldLoop(t = 0) {
        return new this.Promise(function (resolve, reject) {
          return setTimeout(resolve, t);
        });
      }

      computePenalty() {
        var ref;
        return (ref = this.penalty) != null ? ref : 15 * this.minTime || 5000;
      }

      async __updateSettings__(options) {
        await this.yieldLoop();
        parser$$1.overwrite(options, options, this);
        return true;
      }

      async __running__() {
        await this.yieldLoop();
        return this._running;
      }

      async __groupCheck__() {
        await this.yieldLoop();
        return this._nextRequest;
      }

      conditionsCheck(weight) {
        return (this.maxConcurrent == null || this._running + weight <= this.maxConcurrent) && (this.reservoir == null || this.reservoir - weight >= 0);
      }

      async __incrementReservoir__(incr) {
        await this.yieldLoop();
        return this.reservoir += incr;
      }

      async __currentReservoir__() {
        await this.yieldLoop();
        return this.reservoir;
      }

      isBlocked(now) {
        return this._unblockTime >= now;
      }

      check(weight, now) {
        return this.conditionsCheck(weight) && this._nextRequest - now <= 0;
      }

      async __check__(weight) {
        var now;
        await this.yieldLoop();
        now = Date.now();
        return this.check(weight, now);
      }

      async __register__(index, weight, expiration) {
        var now, wait;
        await this.yieldLoop();
        now = Date.now();

        if (this.conditionsCheck(weight)) {
          this._running += weight;
          this._executing[index] = {
            timeout: expiration != null ? setTimeout(() => {
              if (!this._executing[index].freed) {
                this._executing[index].freed = true;
                return this._running -= weight;
              }
            }, expiration) : void 0,
            freed: false
          };

          if (this.reservoir != null) {
            this.reservoir -= weight;
          }

          wait = Math.max(this._nextRequest - now, 0);
          this._nextRequest = now + wait + this.minTime;
          return {
            success: true,
            wait
          };
        } else {
          return {
            success: false
          };
        }
      }

      strategyIsBlock() {
        return this.strategy === 3;
      }

      async __submit__(queueLength, weight) {
        var blocked, now, reachedHWM;
        await this.yieldLoop();

        if (this.maxConcurrent != null && weight > this.maxConcurrent) {
          throw new BottleneckError$$1(`Impossible to add a job having a weight of ${weight} to a limiter having a maxConcurrent setting of ${this.maxConcurrent}`);
        }

        now = Date.now();
        reachedHWM = this.highWater != null && queueLength === this.highWater && !this.check(weight, now);
        blocked = this.strategyIsBlock() && (reachedHWM || this.isBlocked(now));

        if (blocked) {
          this._unblockTime = now + this.computePenalty();
          this._nextRequest = this._unblockTime + this.minTime;
        }

        return {
          reachedHWM,
          blocked,
          strategy: this.strategy
        };
      }

      async __free__(index, weight) {
        await this.yieldLoop();
        clearTimeout(this._executing[index].timeout);

        if (!this._executing[index].freed) {
          this._executing[index].freed = true;
          this._running -= weight;
        }

        return {
          running: this._running
        };
      }

    };
    module.exports = Local;
  }).call(commonjsGlobal);
});

let lua = {
  'check.lua': "local settings_key = KEYS[1]\nlocal running_key = KEYS[2]\nlocal executing_key = KEYS[3]\n\nlocal weight = tonumber(ARGV[1])\nlocal now = tonumber(ARGV[2])\n\nlocal running = tonumber(refresh_running(executing_key, running_key, settings_key, now))\nlocal settings = redis.call('hmget', settings_key,\n  'maxConcurrent',\n  'reservoir',\n  'nextRequest'\n)\nlocal maxConcurrent = tonumber(settings[1])\nlocal reservoir = tonumber(settings[2])\nlocal nextRequest = tonumber(settings[3])\n\nlocal conditionsCheck = conditions_check(weight, maxConcurrent, running, reservoir)\n\nlocal result = conditionsCheck and nextRequest - now <= 0\n\nreturn result\n",
  'conditions_check.lua': 'local conditions_check = function (weight, maxConcurrent, running, reservoir)\n  return (\n    (maxConcurrent == nil or running + weight <= maxConcurrent) and\n    (reservoir == nil or reservoir - weight >= 0)\n  )\nend\n',
  'current_reservoir.lua': "local settings_key = KEYS[1]\n\nreturn tonumber(redis.call('hget', settings_key, 'reservoir'))\n",
  'free.lua': "local settings_key = KEYS[1]\nlocal running_key = KEYS[2]\nlocal executing_key = KEYS[3]\n\nlocal index = ARGV[1]\nlocal now = ARGV[2]\n\nredis.call('zadd', executing_key, 0, index)\n\nreturn refresh_running(executing_key, running_key, settings_key, now)\n",
  'get_time.lua': "redis.replicate_commands()\n\nlocal get_time = function ()\n  local time = redis.call('time')\n\n  return tonumber(time[1]..string.sub(time[2], 1, 3))\nend\n",
  'group_check.lua': "local settings_key = KEYS[1]\n\nreturn redis.call('hget', settings_key, 'nextRequest')\n",
  'increment_reservoir.lua': "local settings_key = KEYS[1]\nlocal incr = ARGV[1]\n\nreturn redis.call('hincrby', settings_key, 'reservoir', incr)\n",
  'init.lua': "local settings_key = KEYS[1]\nlocal running_key = KEYS[2]\nlocal executing_key = KEYS[3]\n\nlocal clear = tonumber(ARGV[1])\n\nif clear == 1 then\n  redis.call('del', settings_key, running_key, executing_key)\nend\n\nif redis.call('exists', settings_key) == 0 then\n  local args = {'hmset', settings_key}\n\n  for i = 2, #ARGV do\n    table.insert(args, ARGV[i])\n  end\n\n  redis.call(unpack(args))\nend\n\nreturn {}\n",
  'refresh_running.lua': "local refresh_running = function (executing_key, running_key, settings_key, now)\n\n  local expired = redis.call('zrangebyscore', executing_key, '-inf', '('..now)\n\n  if #expired == 0 then\n    return redis.call('hget', settings_key, 'running')\n  else\n    redis.call('zremrangebyscore', executing_key, '-inf', '('..now)\n\n    local args = {'hmget', running_key}\n    for i = 1, #expired do\n      table.insert(args, expired[i])\n    end\n\n    local weights = redis.call(unpack(args))\n\n    args[1] = 'hdel'\n    local deleted = redis.call(unpack(args))\n\n    local total = 0\n    for i = 1, #weights do\n      total = total + (tonumber(weights[i]) or 0)\n    end\n    local incr = -total\n    if total == 0 then\n      incr = 0\n    else\n      redis.call('publish', 'bottleneck', 'freed:'..total)\n    end\n\n    return redis.call('hincrby', settings_key, 'running', incr)\n  end\n\nend\n",
  'register.lua': "local settings_key = KEYS[1]\nlocal running_key = KEYS[2]\nlocal executing_key = KEYS[3]\n\nlocal index = ARGV[1]\nlocal weight = tonumber(ARGV[2])\nlocal expiration = tonumber(ARGV[3])\nlocal now = tonumber(ARGV[4])\n\nlocal running = tonumber(refresh_running(executing_key, running_key, settings_key, now))\nlocal settings = redis.call('hmget', settings_key,\n  'maxConcurrent',\n  'reservoir',\n  'nextRequest',\n  'minTime'\n)\nlocal maxConcurrent = tonumber(settings[1])\nlocal reservoir = tonumber(settings[2])\nlocal nextRequest = tonumber(settings[3])\nlocal minTime = tonumber(settings[4])\n\nif conditions_check(weight, maxConcurrent, running, reservoir) then\n\n  if expiration ~= nil then\n    redis.call('zadd', executing_key, now + expiration, index)\n  end\n  redis.call('hset', running_key, index, weight)\n  redis.call('hincrby', settings_key, 'running', weight)\n\n  local wait = math.max(nextRequest - now, 0)\n\n  if reservoir == nil then\n    redis.call('hset', settings_key,\n    'nextRequest', now + wait + minTime\n    )\n  else\n    redis.call('hmset', settings_key,\n      'reservoir', reservoir - weight,\n      'nextRequest', now + wait + minTime\n    )\n  end\n\n  return {true, wait}\n\nelse\n  return {false}\nend\n",
  'running.lua': 'local settings_key = KEYS[1]\nlocal running_key = KEYS[2]\nlocal executing_key = KEYS[3]\nlocal now = ARGV[1]\n\nreturn tonumber(refresh_running(executing_key, running_key, settings_key, now))\n',
  'submit.lua': "local settings_key = KEYS[1]\nlocal running_key = KEYS[2]\nlocal executing_key = KEYS[3]\n\nlocal queueLength = tonumber(ARGV[1])\nlocal weight = tonumber(ARGV[2])\nlocal now = tonumber(ARGV[3])\n\nlocal running = tonumber(refresh_running(executing_key, running_key, settings_key, now))\nlocal settings = redis.call('hmget', settings_key,\n  'maxConcurrent',\n  'highWater',\n  'reservoir',\n  'nextRequest',\n  'strategy',\n  'unblockTime',\n  'penalty',\n  'minTime'\n)\nlocal maxConcurrent = tonumber(settings[1])\nlocal highWater = tonumber(settings[2])\nlocal reservoir = tonumber(settings[3])\nlocal nextRequest = tonumber(settings[4])\nlocal strategy = tonumber(settings[5])\nlocal unblockTime = tonumber(settings[6])\nlocal penalty = tonumber(settings[7])\nlocal minTime = tonumber(settings[8])\n\nif maxConcurrent ~= nil and weight > maxConcurrent then\n  return redis.error_reply('OVERWEIGHT:'..weight..':'..maxConcurrent)\nend\n\nlocal reachedHWM = (highWater ~= nil and queueLength == highWater\n  and not (\n    conditions_check(weight, maxConcurrent, running, reservoir)\n    and nextRequest - now <= 0\n  )\n)\n\nlocal blocked = strategy == 3 and (reachedHWM or unblockTime >= now)\n\nif blocked then\n  local computedPenalty = penalty\n  if computedPenalty == nil then\n    if minTime == 0 then\n      computedPenalty = 5000\n    else\n      computedPenalty = 15 * minTime\n    end\n  end\n\n  redis.call('hmset', settings_key,\n    'unblockTime', now + computedPenalty,\n    'nextRequest', unblockTime + minTime\n  )\nend\n\nreturn {reachedHWM, blocked, strategy}\n",
  'update_settings.lua': "local settings_key = KEYS[1]\n\nlocal args = {'hmset', settings_key}\n\nfor i = 1, #ARGV do\n  table.insert(args, ARGV[i])\nend\n\nredis.call(unpack(args))\n\nreturn {}\n",
};

let lua$1 = Object.freeze({
  default: lua,
});

let require$$3$1 = (lua$1 && lua) || lua$1;

let RedisStorage = createCommonjsModule((module) => {
  // Generated by CoffeeScript 2.0.2
  (function () {
    var BottleneckError$$2, DLList$$2, RedisStorage, libraries, lua, parser$$2, scripts;
    parser$$2 = parser;
    BottleneckError$$2 = BottleneckError;
    lua = require$$3$1;
    libraries = {
      get_time: lua['get_time.lua'],
      refresh_running: lua['refresh_running.lua'],
      conditions_check: lua['conditions_check.lua']
    };
    scripts = {
      init: {
        keys: ['b_settings', 'b_running', 'b_executing'],
        libs: [],
        code: lua['init.lua']
      },
      update_settings: {
        keys: ['b_settings'],
        libs: [],
        code: lua['update_settings.lua']
      },
      running: {
        keys: ['b_settings', 'b_running', 'b_executing'],
        libs: ['refresh_running'],
        code: lua['running.lua']
      },
      group_check: {
        keys: ['b_settings'],
        libs: [],
        code: lua['group_check.lua']
      },
      check: {
        keys: ['b_settings', 'b_running', 'b_executing'],
        libs: ['refresh_running', 'conditions_check'],
        code: lua['check.lua']
      },
      submit: {
        keys: ['b_settings', 'b_running', 'b_executing'],
        libs: ['refresh_running', 'conditions_check'],
        code: lua['submit.lua']
      },
      register: {
        keys: ['b_settings', 'b_running', 'b_executing'],
        libs: ['refresh_running', 'conditions_check'],
        code: lua['register.lua']
      },
      free: {
        keys: ['b_settings', 'b_running', 'b_executing'],
        libs: ['refresh_running'],
        code: lua['free.lua']
      },
      current_reservoir: {
        keys: ['b_settings'],
        libs: [],
        code: lua['current_reservoir.lua']
      },
      increment_reservoir: {
        keys: ['b_settings'],
        libs: [],
        code: lua['increment_reservoir.lua']
      }
    };
    RedisStorage = class RedisStorage {
      constructor(instance, initSettings, options) {
        var redis$$1;
        this.loadAll = this.loadAll.bind(this);
        this.instance = instance;
        redis$$1 = redis;
        parser$$2.load(options, options, this);
        this.client = redis$$1.createClient(this.clientOptions);
        this.subClient = redis$$1.createClient(this.clientOptions);
        this.shas = {};
        this.clients = {
          client: this.client,
          subscriber: this.subClient
        };
        this.ready = new this.Promise((resolve, reject) => {
          var count, done, errorListener;

          errorListener = function (e) {
            return reject(e);
          };

          count = 0;

          done = () => {
            count++;

            if (count === 2) {
              [this.client, this.subClient].forEach(client => {
                client.removeListener('error', errorListener);
                return client.on('error', e => {
                  return this.instance._trigger('error', [e]);
                });
              });
              return resolve();
            }
          };

          this.client.on('error', errorListener);
          this.client.on('ready', function () {
            return done();
          });
          this.subClient.on('error', errorListener);
          return this.subClient.on('ready', () => {
            this.subClient.on('subscribe', function () {
              return done();
            });
            return this.subClient.subscribe('bottleneck');
          });
        }).then(this.loadAll).then(() => {
          var args;
          this.subClient.on('message', (channel, message) => {
            var info, type;
            [type, info] = message.split(':');

            if (type === 'freed') {
              return this.instance._drainAll(~~info);
            }
          });
          initSettings.nextRequest = Date.now();
          initSettings.running = 0;
          initSettings.unblockTime = 0;
          initSettings.version = this.instance.version;
          args = this.prepareObject(initSettings);
          args.unshift(options.clearDatastore ? 1 : 0);
          return this.runScript('init', args);
        }).then(results => {
          return this.clients;
        });
      }

      disconnect(flush) {
        this.client.end(flush);
        this.subClient.end(flush);
        return this;
      }

      loadScript(name) {
        return new this.Promise((resolve, reject) => {
          var payload;
          payload = scripts[name].libs.map(function (lib) {
            return libraries[lib];
          }).join('\n') + scripts[name].code;
          return this.client.multi([['script', 'load', payload]]).exec((err, replies) => {
            if (err != null) {
              return reject(err);
            }

            this.shas[name] = replies[0];
            return resolve(replies[0]);
          });
        });
      }

      loadAll() {
        var k, v;
        return this.Promise.all(function () {
          var results1;
          results1 = [];

          for (k in scripts) {
            v = scripts[k];
            results1.push(this.loadScript(k));
          }

          return results1;
        }.call(this));
      }

      prepareArray(arr) {
        return arr.map(function (x) {
          if (x != null) {
            return x.toString();
          } else {
            return '';
          }
        });
      }

      prepareObject(obj) {
        var arr, k, v;
        arr = [];

        for (k in obj) {
          v = obj[k];
          arr.push(k, v != null ? v.toString() : '');
        }

        return arr;
      }

      runScript(name, args) {
        return new this.Promise((resolve, reject) => {
          var arr, script;
          script = scripts[name];
          arr = [this.shas[name], script.keys.length].concat(script.keys, args, function (err, replies) {
            if (err != null) {
              return reject(err);
            }

            return resolve(replies);
          });
          return this.client.evalsha.bind(this.client).apply({}, arr);
        });
      }

      convertBool(b) {
        return !!b;
      }

      async __updateSettings__(options) {
        return await this.runScript('update_settings', this.prepareObject(options));
      }

      async __running__() {
        return await this.runScript('running', [Date.now()]);
      }

      async __groupCheck__() {
        return parseInt((await this.runScript('group_check', [])), 10);
      }

      async __incrementReservoir__(incr) {
        return await this.runScript('increment_reservoir', [incr]);
      }

      async __currentReservoir__() {
        return await this.runScript('current_reservoir', []);
      }

      async __check__(weight) {
        return this.convertBool((await this.runScript('check', this.prepareArray([weight, Date.now()]))));
      }

      async __register__(index, weight, expiration) {
        var success, wait;
        [success, wait] = await this.runScript('register', this.prepareArray([index, weight, expiration, Date.now()]));
        return {
          success: this.convertBool(success),
          wait
        };
      }

      async __submit__(queueLength, weight) {
        var blocked, e, maxConcurrent, overweight, reachedHWM, strategy;

        try {
          [reachedHWM, blocked, strategy] = await this.runScript('submit', this.prepareArray([queueLength, weight, Date.now()]));
          return {
            reachedHWM: this.convertBool(reachedHWM),
            blocked: this.convertBool(blocked),
            strategy
          };
        } catch (error) {
          e = error;

          if (e.message.indexOf('OVERWEIGHT') === 0) {
            [overweight, weight, maxConcurrent] = e.message.split(':');
            throw new BottleneckError$$2(`Impossible to add a job having a weight of ${weight} to a limiter having a maxConcurrent setting of ${maxConcurrent}`);
          } else {
            throw e;
          }
        }
      }

      async __free__(index, weight) {
        var result;
        result = await this.runScript('free', this.prepareArray([index, Date.now()]));
        return {
          running: result
        };
      }

    };
    module.exports = RedisStorage;
  }).call(commonjsGlobal);
});

let Sync = createCommonjsModule((module) => {
  // Generated by CoffeeScript 2.0.2
  (function () {
    var DLList$$2,
        Sync,
        slice = [].slice;
    DLList$$2 = DLList;
    Sync = class Sync {
      constructor(name) {
        this.submit = this.submit.bind(this);
        this.schedule = this.schedule.bind(this);
        this.wrap = this.wrap.bind(this);
        this.name = name;
        this._running = 0;
        this._queue = new DLList$$2();
      }

      _tryToRun() {
        var next;

        if (this._running < 1 && this._queue.length > 0) {
          this._running++;
          next = this._queue.shift();
          return next.task.apply({}, next.args.concat((...args) => {
            var ref;
            this._running--;

            this._tryToRun();

            return (ref = next.cb) != null ? ref.apply({}, args) : void 0;
          }));
        }
      }

      submit(task, ...args) {
        var cb, i, ref;
        ref = args, args = 2 <= ref.length ? slice.call(ref, 0, i = ref.length - 1) : (i = 0, []), cb = ref[i++];

        this._queue.push({
          task,
          args,
          cb
        });

        return this._tryToRun();
      }

      schedule(task, ...args) {
        var wrapped;

        wrapped = function (...args) {
          var cb, i, ref;
          ref = args, args = 2 <= ref.length ? slice.call(ref, 0, i = ref.length - 1) : (i = 0, []), cb = ref[i++];
          return task.apply({}, args).then(function (...args) {
            return cb.apply({}, Array.prototype.concat(null, args));
          }).catch(function (...args) {
            return cb.apply({}, args);
          });
        };

        return new Promise((resolve, reject) => {
          return this.submit.apply({}, Array.prototype.concat(wrapped, args, function (...args) {
            return (args[0] != null ? reject : (args.shift(), resolve)).apply({}, args);
          }));
        });
      }

      wrap(fn) {
        return (...args) => {
          return this.schedule.apply({}, Array.prototype.concat(fn, args));
        };
      }

    };
    module.exports = Sync;
  }).call(commonjsGlobal);
});

let _from = 'bottleneck@2.0.1';
let _id = 'bottleneck@2.0.1';
let _inBundle = false;
let _integrity = 'sha512-QtKe8dc5bzZ8G7Cn3q4OySCJonKtl979DajHZoHW0at60x+hq4gT7iHtNUUlNo6CZBv6Av4z1KpcfBY/2/GHpw==';
let _location = '/bottleneck';
let _phantomChildren = {};
let _requested = { type: 'version', registry: true, raw: 'bottleneck@2.0.1', name: 'bottleneck', escapedName: 'bottleneck', rawSpec: '2.0.1', saveSpec: null, fetchSpec: '2.0.1' };
let _requiredBy = ['#USER', '/'];
let _resolved = 'https://registry.npmjs.org/bottleneck/-/bottleneck-2.0.1.tgz';
let _shasum = '2296570b8242ab492c0eecef61224b860ac09288';
let _spec = 'bottleneck@2.0.1';
let _where = '/Users/pixelwhip/Project/intercept-client';
let author = { name: 'Simon Grondin' };
let bugs = { url: 'https://github.com/SGrondin/bottleneck/issues' };
let bundleDependencies = false;
let deprecated = false;
let description = 'Distributed task scheduler and rate limiter';
let devDependencies = { '@types/es6-promise': '0.0.33', assert: '1.4.x', browserify: '*', coffeescript: '2.0.x', 'ejs-cli': 'git://github.com/SGrondin/ejs-cli.git', mocha: '4.x', redis: '^2.8.0', typescript: '^2.6.2', 'uglify-es': '3.x' };
let homepage = 'https://github.com/SGrondin/bottleneck#readme';
let keywords = ['async rate limiter', 'rate limiter', 'rate limiting', 'async', 'rate', 'limiting', 'limiter', 'throttle', 'throttling', 'load', 'ddos'];
let license = 'MIT';
let main = 'lib/index.js';
let name = 'bottleneck';
let repository = { type: 'git', url: 'git+https://github.com/SGrondin/bottleneck.git' };
let scripts = { build: './scripts/build.sh', compile: './scripts/build.sh compile', test: './node_modules/mocha/bin/mocha test' };
let typings = 'bottleneck.d.ts';
let version = '2.0.1';
let _package = {
  _from,
  _id,
  _inBundle,
  _integrity,
  _location,
  _phantomChildren,
  _requested,
  _requiredBy,
  _resolved,
  _shasum,
  _spec,
  _where,
  author,
  bugs,
  bundleDependencies,
  deprecated,
  description,
  devDependencies,
  homepage,
  keywords,
  license,
  main,
  name,
  repository,
  scripts,
  typings,
  version,
};

let _package$1 = Object.freeze({
  _from,
  _id,
  _inBundle,
  _integrity,
  _location,
  _phantomChildren,
  _requested,
  _requiredBy,
  _resolved,
  _shasum,
  _spec,
  _where,
  author,
  bugs,
  bundleDependencies,
  deprecated,
  description,
  devDependencies,
  homepage,
  keywords,
  license,
  main,
  name,
  repository,
  scripts,
  typings,
  version,
  default: _package,
});

let Group = createCommonjsModule((module) => {
  // Generated by CoffeeScript 2.0.2
  (function () {
    var Group, parser$$2;
    parser$$2 = parser;

    Group = function () {
      class Group {
        constructor(limiterOptions = {}, groupOptions = {}) {
          this.key = this.key.bind(this);
          this.deleteKey = this.deleteKey.bind(this);
          this.limiters = this.limiters.bind(this);
          this.keys = this.keys.bind(this);
          this.startAutoCleanup = this.startAutoCleanup.bind(this);
          this.stopAutoCleanup = this.stopAutoCleanup.bind(this);
          this.updateSettings = this.updateSettings.bind(this);
          this.limiterOptions = limiterOptions;
          parser$$2.load(groupOptions, this.defaults, this);
          this.instances = {};
          this.Bottleneck = Bottleneck;
          this.startAutoCleanup();
        }

        key(key = '') {
          var ref;
          return (ref = this.instances[key]) != null ? ref : this.instances[key] = new this.Bottleneck(this.limiterOptions);
        }

        deleteKey(key = '') {
          var ref;

          if ((ref = this.instances[key]) != null) {
            ref.disconnect();
          }

          return delete this.instances[key];
        }

        limiters() {
          var k, ref, results, v;
          ref = this.instances;
          results = [];

          for (k in ref) {
            v = ref[k];
            results.push({
              key: k,
              limiter: v
            });
          }

          return results;
        }

        keys() {
          return Object.keys(this.instances);
        }

        startAutoCleanup() {
          var base;
          this.stopAutoCleanup();
          return typeof (base = this.interval = setInterval(async () => {
            var check, e, k, ref, results, time, v;
            time = Date.now();
            ref = this.instances;
            results = [];

            for (k in ref) {
              v = ref[k];

              try {
                check = await v._store.__groupCheck__();

                if (check + this.timeout < time) {
                  results.push(this.deleteKey(k));
                } else {
                  results.push(void 0);
                }
              } catch (error) {
                e = error;
                results.push(v._trigger('error', [e]));
              }
            }

            return results;
          }, this.timeout / 2)).unref === 'function' ? base.unref() : void 0;
        }

        stopAutoCleanup() {
          return clearInterval(this.interval);
        }

        updateSettings(options = {}) {
          parser$$2.overwrite(options, this.defaults, this);

          if (options.timeout != null) {
            return this.startAutoCleanup();
          }
        }

      }


      Group.prototype.defaults = {
        timeout: 1000 * 60 * 5
      };
      return Group;
    }();

    module.exports = Group;
  }).call(commonjsGlobal);
});

let require$$5 = (_package$1 && _package) || _package$1;

var Bottleneck = createCommonjsModule((module) => {
  // Generated by CoffeeScript 2.0.2
  (function () {
    var Bottleneck,
        DEFAULT_PRIORITY,
        DLList$$1,
        Local$$1,
        NUM_PRIORITIES,
        RedisStorage$$1,
        Sync$$1,
        packagejson,
        parser$$1,
        slice = [].slice;
    NUM_PRIORITIES = 10;
    DEFAULT_PRIORITY = 5;
    parser$$1 = parser;
    Local$$1 = Local;
    RedisStorage$$1 = RedisStorage;
    DLList$$1 = DLList;
    Sync$$1 = Sync;
    packagejson = require$$5;

    Bottleneck = function () {
      class Bottleneck {
        constructor(options = {}, ...invalid) {
          var sDefaults;
          this.ready = this.ready.bind(this);
          this.clients = this.clients.bind(this);
          this.disconnect = this.disconnect.bind(this);
          this.chain = this.chain.bind(this);
          this.queued = this.queued.bind(this);
          this.running = this.running.bind(this);
          this.check = this.check.bind(this);
          this._drainOne = this._drainOne.bind(this);
          this.submit = this.submit.bind(this);
          this.schedule = this.schedule.bind(this);
          this.wrap = this.wrap.bind(this);
          this.updateSettings = this.updateSettings.bind(this);
          this.currentReservoir = this.currentReservoir.bind(this);
          this.incrementReservoir = this.incrementReservoir.bind(this);
          this.on = this.on.bind(this);
          this.once = this.once.bind(this);
          this.removeAllListeners = this.removeAllListeners.bind(this);

          if (!(options != null && typeof options === 'object' && invalid.length === 0)) {
            throw new Bottleneck.prototype.BottleneckError("Bottleneck v2 takes a single object argument. Refer to https://github.com/SGrondin/bottleneck#upgrading-to-v2 if you're upgrading from Bottleneck v1.");
          }

          parser$$1.load(options, this.instanceDefaults, this);
          this._queues = this._makeQueues();
          this._executing = {};
          this._limiter = null;
          this._events = {};
          this._submitLock = new Sync$$1('submit');
          this._registerLock = new Sync$$1('register');
          sDefaults = parser$$1.load(options, this.storeDefaults, {});

          this._store = function () {
            if (this.datastore === 'local') {
              return new Local$$1(parser$$1.load(options, this.storeInstanceDefaults, sDefaults));
            } else if (this.datastore === 'redis') {
              return new RedisStorage$$1(this, sDefaults, parser$$1.load(options, this.storeInstanceDefaults, {}));
            } else {
              throw new Bottleneck.prototype.BottleneckError(`Invalid datastore type: ${this.datastore}`);
            }
          }.call(this);
        }

        ready() {
          return this._store.ready;
        }

        clients() {
          return this._store.clients;
        }

        async disconnect(flush = true) {
          return await this._store.disconnect(flush);
        }

        _addListener(name, status, cb) {
          var base;

          if ((base = this._events)[name] == null) {
            base[name] = [];
          }

          this._events[name].push({
            cb,
            status
          });

          return this;
        }

        _trigger(name, args) {
          if (name !== 'debug') {
            this._trigger('debug', [`Event triggered: ${name}`, args]);
          }

          if (name === 'dropped' && this.rejectOnDrop) {
            args.forEach(function (job) {
              return job.cb.apply({}, [new Bottleneck.prototype.BottleneckError('This job has been dropped by Bottleneck')]);
            });
          }

          if (this._events[name] == null) {
            return;
          }

          this._events[name] = this._events[name].filter(function (listener) {
            return listener.status !== 'none';
          });
          return this._events[name].forEach(function (listener) {
            if (listener.status === 'none') {
              return;
            }

            if (listener.status === 'once') {
              listener.status = 'none';
            }

            return listener.cb.apply({}, args);
          });
        }

        _makeQueues() {
          var i, j, ref, results;
          results = [];

          for (i = j = 1, ref = NUM_PRIORITIES; 1 <= ref ? j <= ref : j >= ref; i = 1 <= ref ? ++j : --j) {
            results.push(new DLList$$1());
          }

          return results;
        }

        chain(_limiter) {
          this._limiter = _limiter;
          return this;
        }

        _sanitizePriority(priority) {
          var sProperty;
          sProperty = ~~priority !== priority ? DEFAULT_PRIORITY : priority;

          if (sProperty < 0) {
            return 0;
          } else if (sProperty > NUM_PRIORITIES - 1) {
            return NUM_PRIORITIES - 1;
          } else {
            return sProperty;
          }
        }

        _find(arr, fn) {
          var ref;
          return (ref = function () {
            var i, j, len, x;

            for (i = j = 0, len = arr.length; j < len; i = ++j) {
              x = arr[i];

              if (fn(x)) {
                return x;
              }
            }
          }()) != null ? ref : [];
        }

        queued(priority) {
          if (priority != null) {
            return this._queues[priority].length;
          } else {
            return this._queues.reduce(function (a, b) {
              return a + b.length;
            }, 0);
          }
        }

        async running() {
          return await this._store.__running__();
        }

        _getFirst(arr) {
          return this._find(arr, function (x) {
            return x.length > 0;
          });
        }

        _randomIndex() {
          return Math.random().toString(36).slice(2);
        }

        async check(weight = 1) {
          return await this._store.__check__(weight);
        }

        _run(next, wait, index) {
          var completed, done;

          this._trigger('debug', [`Scheduling ${next.options.id}`, {
            args: next.args,
            options: next.options
          }]);

          done = false;

          completed = async (...args) => {
            var e, ref, running;

            if (!done) {
              try {
                done = true;
                clearTimeout(this._executing[index].expiration);
                delete this._executing[index];

                this._trigger('debug', [`Completed ${next.options.id}`, {
                  args: next.args,
                  options: next.options
                }]);

                (((({
                  running
                } = await this._store.__free__(index, next.options.weight)))));

                this._trigger('debug', [`Freed ${next.options.id}`, {
                  args: next.args,
                  options: next.options
                }]);

                this._drainAll().catch(e => {
                  return this._trigger('error', [e]);
                });

                if (running === 0 && this.queued() === 0) {
                  this._trigger('idle', []);
                }

                return (ref = next.cb) != null ? ref.apply({}, args) : void 0;
              } catch (error) {
                e = error;
                return this._trigger('error', [e]);
              }
            }
          };

          return this._executing[index] = {
            timeout: setTimeout(() => {
              this._trigger('debug', [`Executing ${next.options.id}`, {
                args: next.args,
                options: next.options
              }]);

              if (this._limiter != null) {
                return this._limiter.submit.apply(this._limiter, Array.prototype.concat(next.options, next.task, next.args, completed));
              } else {
                return next.task.apply({}, next.args.concat(completed));
              }
            }, wait),
            expiration: next.options.expiration != null ? setTimeout(() => {
              return completed(new Bottleneck.prototype.BottleneckError(`This job timed out after ${next.options.expiration} ms.`));
            }, next.options.expiration) : void 0,
            job: next
          };
        }

        _drainOne(freed) {
          return this._registerLock.schedule(() => {
            var args, index, options, queue;

            if (this.queued() === 0) {
              return this.Promise.resolve(false);
            }

            queue = this._getFirst(this._queues);
            (((({
              options,
              args
            } = queue.first()))));

            if (freed != null && options.weight > freed) {
              return this.Promise.resolve(false);
            }

            this._trigger('debug', [`Draining ${options.id}`, {
              args,
              options
            }]);

            index = this._randomIndex();
            return this._store.__register__(index, options.weight, options.expiration).then(({
              success,
              wait
            }) => {
              var next;

              this._trigger('debug', [`Drained ${options.id}`, {
                success,
                args,
                options
              }]);

              if (success) {
                next = queue.shift();

                if (this.queued() === 0 && this._submitLock._queue.length === 0) {
                  this._trigger('empty', []);
                }

                this._run(next, wait, index);
              }

              return this.Promise.resolve(success);
            });
          });
        }

        _drainAll(freed) {
          return this._drainOne(freed).then(success => {
            if (success) {
              return this._drainAll();
            } else {
              return this.Promise.resolve(success);
            }
          }).catch(e => {
            return this._trigger('error', [e]);
          });
        }

        submit(...args) {
          var cb, j, job, k, options, ref, ref1, task;

          if (typeof args[0] === 'function') {
            ref = args, task = ref[0], args = 3 <= ref.length ? slice.call(ref, 1, j = ref.length - 1) : (j = 1, []), cb = ref[j++];
            options = this.jobDefaults;
          } else {
            ref1 = args, options = ref1[0], task = ref1[1], args = 4 <= ref1.length ? slice.call(ref1, 2, k = ref1.length - 1) : (k = 2, []), cb = ref1[k++];
            options = parser$$1.load(options, this.jobDefaults);
          }

          job = {
            options,
            task,
            args,
            cb
          };
          options.priority = this._sanitizePriority(options.priority);

          this._trigger('debug', [`Queueing ${options.id}`, {
            args,
            options
          }]);

          return this._submitLock.schedule(async () => {
            var blocked, e, reachedHWM, shifted, strategy;

            try {
              (((({
                reachedHWM,
                blocked,
                strategy
              } = await this._store.__submit__(this.queued(), options.weight)))));

              this._trigger('debug', [`Queued ${options.id}`, {
                args,
                options,
                reachedHWM,
                blocked
              }]);
            } catch (error) {
              e = error;

              this._trigger('debug', [`Could not queue ${options.id}`, {
                args,
                options,
                error: e
              }]);

              job.cb(e);
              return false;
            }

            if (blocked) {
              this._queues = this._makeQueues();

              this._trigger('dropped', [job]);

              return true;
            } else if (reachedHWM) {
              shifted = strategy === Bottleneck.prototype.strategy.LEAK ? this._getFirst(this._queues.slice(options.priority).reverse()).shift() : strategy === Bottleneck.prototype.strategy.OVERFLOW_PRIORITY ? this._getFirst(this._queues.slice(options.priority + 1).reverse()).shift() : strategy === Bottleneck.prototype.strategy.OVERFLOW ? job : void 0;

              if (shifted != null) {
                this._trigger('dropped', [shifted]);
              }

              if (shifted == null || strategy === Bottleneck.prototype.strategy.OVERFLOW) {
                return reachedHWM;
              }
            }

            this._queues[options.priority].push(job);

            await this._drainAll();
            return reachedHWM;
          });
        }

        schedule(...args) {
          var options, task, wrapped;

          if (typeof args[0] === 'function') {
            [task, ...args] = args;
            options = this.jobDefaults;
          } else {
            [options, task, ...args] = args;
            options = parser$$1.load(options, this.jobDefaults);
          }

          wrapped = function (...args) {
            var cb, j, ref;
            ref = args, args = 2 <= ref.length ? slice.call(ref, 0, j = ref.length - 1) : (j = 0, []), cb = ref[j++];
            return task.apply({}, args).then(function (...args) {
              return cb.apply({}, Array.prototype.concat(null, args));
            }).catch(function (...args) {
              return cb.apply({}, args);
            });
          };

          return new this.Promise((resolve, reject) => {
            return this.submit.apply({}, Array.prototype.concat(options, wrapped, args, function (...args) {
              return (args[0] != null ? reject : (args.shift(), resolve)).apply({}, args);
            })).catch(e => {
              return this._trigger('error', [e]);
            });
          });
        }

        wrap(fn) {
          return (...args) => {
            return this.schedule.apply({}, Array.prototype.concat(fn, args));
          };
        }

        async updateSettings(options = {}) {
          await this._store.__updateSettings__(parser$$1.overwrite(options, this.storeDefaults));
          parser$$1.overwrite(options, this.instanceDefaults, this);

          this._drainAll().catch(e => {
            return this._trigger('error', [e]);
          });

          return this;
        }

        async currentReservoir() {
          return await this._store.__currentReservoir__();
        }

        async incrementReservoir(incr = 0) {
          await this._store.__incrementReservoir__(incr);

          this._drainAll().catch(e => {
            return this._trigger('error', [e]);
          });

          return this;
        }

        on(name, cb) {
          return this._addListener(name, 'many', cb);
        }

        once(name, cb) {
          return this._addListener(name, 'once', cb);
        }

        removeAllListeners(name = null) {
          if (name != null) {
            delete this._events[name];
          } else {
            this._events = {};
          }

          return this;
        }

      }


      Bottleneck.default = Bottleneck;
      Bottleneck.version = Bottleneck.prototype.version = packagejson.version;
      Bottleneck.strategy = Bottleneck.prototype.strategy = {
        LEAK: 1,
        OVERFLOW: 2,
        OVERFLOW_PRIORITY: 4,
        BLOCK: 3
      };
      Bottleneck.BottleneckError = Bottleneck.prototype.BottleneckError = BottleneckError;
      Bottleneck.Group = Bottleneck.prototype.Group = Group;
      Bottleneck.prototype.jobDefaults = {
        priority: DEFAULT_PRIORITY,
        weight: 1,
        expiration: null,
        id: '<no-id>'
      };
      Bottleneck.prototype.storeDefaults = {
        maxConcurrent: null,
        minTime: 0,
        highWater: null,
        strategy: Bottleneck.prototype.strategy.LEAK,
        penalty: null,
        reservoir: null
      };
      Bottleneck.prototype.storeInstanceDefaults = {
        clientOptions: {},
        clearDatastore: false,
        Promise: Promise
      };
      Bottleneck.prototype.instanceDefaults = {
        datastore: 'local',
        id: '<no-id>',
        rejectOnDrop: true,
        Promise: Promise
      };
      return Bottleneck;
    }();

    module.exports = Bottleneck;
  }).call(commonjsGlobal);
});

let lib = createCommonjsModule((module) => {
  // Generated by CoffeeScript 2.0.2
  (function () {
    module.exports = Bottleneck;
  }).call(commonjsGlobal);
});

// this is pretty straight-forward - we use the crypto API.

let rng = function nodeRNG() {
  return crypto.randomBytes(16);
};

/**
 * Convert array of 16 byte values to UUID string format of the form:
 * XXXXXXXX-XXXX-XXXX-XXXX-XXXXXXXXXXXX
 */
let byteToHex = [];

for (let i = 0; i < 256; ++i) {
  byteToHex[i] = (i + 0x100).toString(16).substr(1);
}

function bytesToUuid(buf, offset) {
  let i = offset || 0;
  let bth = byteToHex;
  return `${bth[buf[i++]] + bth[buf[i++]] + bth[buf[i++]] + bth[buf[i++]]  }-${  bth[buf[i++]]  }${bth[buf[i++]]  }-${  bth[buf[i++]]  }${bth[buf[i++]]  }-${  bth[buf[i++]]  }${bth[buf[i++]]  }-${  bth[buf[i++]]  }${bth[buf[i++]]  }${bth[buf[i++]]  }${bth[buf[i++]]  }${bth[buf[i++]]  }${bth[buf[i++]]}`;
}

let bytesToUuid_1 = bytesToUuid;

function v4(options, buf, offset) {
  let i = buf && offset || 0;

  if (typeof options === 'string') {
    buf = options === 'binary' ? new Array(16) : null;
    options = null;
  }

  options = options || {};
  let rnds = options.random || (options.rng || rng)(); // Per 4.4, set bits for version and `clock_seq_hi_and_reserved`

  rnds[6] = rnds[6] & 0x0f | 0x40;
  rnds[8] = rnds[8] & 0x3f | 0x80; // Copy bytes to buffer, if provided

  if (buf) {
    for (let ii = 0; ii < 16; ++ii) {
      buf[i + ii] = rnds[ii];
    }
  }

  return buf || bytesToUuid_1(rnds);
}

let v4_1 = v4;

/** Used for built-in method references. */

let objectProto$12 = Object.prototype;
/** Used to check objects for own properties. */

let hasOwnProperty$9 = objectProto$12.hasOwnProperty;
/**
 * Assigns `value` to `key` of `object` if the existing value is not equivalent
 * using [`SameValueZero`](http://ecma-international.org/ecma-262/7.0/#sec-samevaluezero)
 * for equality comparisons.
 *
 * @private
 * @param {Object} object The object to modify.
 * @param {string} key The key of the property to assign.
 * @param {*} value The value to assign.
 */

function assignValue(object, key, value) {
  let objValue = object[key];

  if (!(hasOwnProperty$9.call(object, key) && eq_1(objValue, value)) || value === undefined && !(key in object)) {
    _baseAssignValue(object, key, value);
  }
}

let _assignValue = assignValue;

/**
 * Copies properties of `source` to `object`.
 *
 * @private
 * @param {Object} source The object to copy properties from.
 * @param {Array} props The property identifiers to copy.
 * @param {Object} [object={}] The object to copy properties to.
 * @param {Function} [customizer] The function to customize copied values.
 * @returns {Object} Returns `object`.
 */

function copyObject(source, props, object, customizer) {
  let isNew = !object;
  object || (object = {});
  let index = -1,
    length = props.length;

  while (++index < length) {
    let key = props[index];
    let newValue = customizer ? customizer(object[key], source[key], key, object, source) : undefined;

    if (newValue === undefined) {
      newValue = source[key];
    }

    if (isNew) {
      _baseAssignValue(object, key, newValue);
    }
 else {
      _assignValue(object, key, newValue);
    }
  }

  return object;
}

let _copyObject = copyObject;

/**
 * A faster alternative to `Function#apply`, this function invokes `func`
 * with the `this` binding of `thisArg` and the arguments of `args`.
 *
 * @private
 * @param {Function} func The function to invoke.
 * @param {*} thisArg The `this` binding of `func`.
 * @param {Array} args The arguments to invoke `func` with.
 * @returns {*} Returns the result of `func`.
 */
function apply(func, thisArg, args) {
  switch (args.length) {
    case 0:
      return func.call(thisArg);

    case 1:
      return func.call(thisArg, args[0]);

    case 2:
      return func.call(thisArg, args[0], args[1]);

    case 3:
      return func.call(thisArg, args[0], args[1], args[2]);
  }

  return func.apply(thisArg, args);
}

let _apply = apply;

/* Built-in method references for those with the same name as other `lodash` methods. */

let nativeMax = Math.max;
/**
 * A specialized version of `baseRest` which transforms the rest array.
 *
 * @private
 * @param {Function} func The function to apply a rest parameter to.
 * @param {number} [start=func.length-1] The start position of the rest parameter.
 * @param {Function} transform The rest array transform.
 * @returns {Function} Returns the new function.
 */

function overRest(func, start, transform) {
  start = nativeMax(start === undefined ? func.length - 1 : start, 0);
  return function () {
    let args = arguments,
      index = -1,
      length = nativeMax(args.length - start, 0),
      array = Array(length);

    while (++index < length) {
      array[index] = args[start + index];
    }

    index = -1;
    let otherArgs = Array(start + 1);

    while (++index < start) {
      otherArgs[index] = args[index];
    }

    otherArgs[start] = transform(array);
    return _apply(func, this, otherArgs);
  };
}

let _overRest = overRest;

/**
 * Creates a function that returns `value`.
 *
 * @static
 * @memberOf _
 * @since 2.4.0
 * @category Util
 * @param {*} value The value to return from the new function.
 * @returns {Function} Returns the new constant function.
 * @example
 *
 * var objects = _.times(2, _.constant({ 'a': 1 }));
 *
 * console.log(objects);
 * // => [{ 'a': 1 }, { 'a': 1 }]
 *
 * console.log(objects[0] === objects[1]);
 * // => true
 */
function constant(value) {
  return function () {
    return value;
  };
}

let constant_1 = constant;

/**
 * The base implementation of `setToString` without support for hot loop shorting.
 *
 * @private
 * @param {Function} func The function to modify.
 * @param {Function} string The `toString` result.
 * @returns {Function} Returns `func`.
 */

let baseSetToString = !_defineProperty ? identity_1 : function (func, string) {
  return _defineProperty(func, 'toString', {
    configurable: true,
    enumerable: false,
    value: constant_1(string),
    writable: true,
  });
};
let _baseSetToString = baseSetToString;

/** Used to detect hot functions by number of calls within a span of milliseconds. */
let HOT_COUNT = 800;
let HOT_SPAN = 16;
/* Built-in method references for those with the same name as other `lodash` methods. */

let nativeNow = Date.now;
/**
 * Creates a function that'll short out and invoke `identity` instead
 * of `func` when it's called `HOT_COUNT` or more times in `HOT_SPAN`
 * milliseconds.
 *
 * @private
 * @param {Function} func The function to restrict.
 * @returns {Function} Returns the new shortable function.
 */

function shortOut(func) {
  let count = 0,
    lastCalled = 0;
  return function () {
    let stamp = nativeNow(),
      remaining = HOT_SPAN - (stamp - lastCalled);
    lastCalled = stamp;

    if (remaining > 0) {
      if (++count >= HOT_COUNT) {
        return arguments[0];
      }
    }
 else {
      count = 0;
    }

    return func(...arguments);
  };
}

let _shortOut = shortOut;

/**
 * Sets the `toString` method of `func` to return `string`.
 *
 * @private
 * @param {Function} func The function to modify.
 * @param {Function} string The `toString` result.
 * @returns {Function} Returns `func`.
 */

let setToString = _shortOut(_baseSetToString);
let _setToString = setToString;

/**
 * The base implementation of `_.rest` which doesn't validate or coerce arguments.
 *
 * @private
 * @param {Function} func The function to apply a rest parameter to.
 * @param {number} [start=func.length-1] The start position of the rest parameter.
 * @returns {Function} Returns the new function.
 */

function baseRest(func, start) {
  return _setToString(_overRest(func, start, identity_1), `${func  }`);
}

let _baseRest = baseRest;

/**
 * Checks if the given arguments are from an iteratee call.
 *
 * @private
 * @param {*} value The potential iteratee value argument.
 * @param {*} index The potential iteratee index or key argument.
 * @param {*} object The potential iteratee object argument.
 * @returns {boolean} Returns `true` if the arguments are from an iteratee call,
 *  else `false`.
 */

function isIterateeCall(value, index, object) {
  if (!isObject_1(object)) {
    return false;
  }

  let type = typeof index;

  if (type == 'number' ? isArrayLike_1(object) && _isIndex(index, object.length) : type == 'string' && index in object) {
    return eq_1(object[index], value);
  }

  return false;
}

let _isIterateeCall = isIterateeCall;

/**
 * Creates a function like `_.assign`.
 *
 * @private
 * @param {Function} assigner The function to assign values.
 * @returns {Function} Returns the new assigner function.
 */

function createAssigner(assigner) {
  return _baseRest((object, sources) => {
    var index = -1,
        length = sources.length,
        customizer = length > 1 ? sources[length - 1] : undefined,
        guard = length > 2 ? sources[2] : undefined;
    customizer = assigner.length > 3 && typeof customizer == 'function' ? (length--, customizer) : undefined;

    if (guard && _isIterateeCall(sources[0], sources[1], guard)) {
      customizer = length < 3 ? undefined : customizer;
      length = 1;
    }

    object = Object(object);

    while (++index < length) {
      var source = sources[index];

      if (source) {
        assigner(object, source, index, customizer);
      }
    }

    return object;
  });
}

let _createAssigner = createAssigner;

/** Used for built-in method references. */

let objectProto$13 = Object.prototype;
/** Used to check objects for own properties. */

let hasOwnProperty$10 = objectProto$13.hasOwnProperty;
/**
 * Assigns own enumerable string keyed properties of source objects to the
 * destination object. Source objects are applied from left to right.
 * Subsequent sources overwrite property assignments of previous sources.
 *
 * **Note:** This method mutates `object` and is loosely based on
 * [`Object.assign`](https://mdn.io/Object/assign).
 *
 * @static
 * @memberOf _
 * @since 0.10.0
 * @category Object
 * @param {Object} object The destination object.
 * @param {...Object} [sources] The source objects.
 * @returns {Object} Returns `object`.
 * @see _.assignIn
 * @example
 *
 * function Foo() {
 *   this.a = 1;
 * }
 *
 * function Bar() {
 *   this.c = 3;
 * }
 *
 * Foo.prototype.b = 2;
 * Bar.prototype.d = 4;
 *
 * _.assign({ 'a': 0 }, new Foo, new Bar);
 * // => { 'a': 1, 'c': 3 }
 */

let assign = _createAssigner((object, source) => {
  if (_isPrototype(source) || isArrayLike_1(source)) {
    _copyObject(source, keys_1(source), object);
    return;
  }

  for (var key in source) {
    if (hasOwnProperty$10.call(source, key)) {
      _assignValue(object, key, source[key]);
    }
  }
});
let assign_1 = assign;

/**
 * The base implementation of `_.filter` without support for iteratee shorthands.
 *
 * @private
 * @param {Array|Object} collection The collection to iterate over.
 * @param {Function} predicate The function invoked per iteration.
 * @returns {Array} Returns the new filtered array.
 */

function baseFilter(collection, predicate) {
  let result = [];
  _baseEach(collection, (value, index, collection) => {
    if (predicate(value, index, collection)) {
      result.push(value);
    }
  });
  return result;
}

let _baseFilter = baseFilter;

/**
 * Iterates over elements of `collection`, returning an array of all elements
 * `predicate` returns truthy for. The predicate is invoked with three
 * arguments: (value, index|key, collection).
 *
 * **Note:** Unlike `_.remove`, this method returns a new array.
 *
 * @static
 * @memberOf _
 * @since 0.1.0
 * @category Collection
 * @param {Array|Object} collection The collection to iterate over.
 * @param {Function} [predicate=_.identity] The function invoked per iteration.
 * @returns {Array} Returns the new filtered array.
 * @see _.reject
 * @example
 *
 * var users = [
 *   { 'user': 'barney', 'age': 36, 'active': true },
 *   { 'user': 'fred',   'age': 40, 'active': false }
 * ];
 *
 * _.filter(users, function(o) { return !o.active; });
 * // => objects for ['fred']
 *
 * // The `_.matches` iteratee shorthand.
 * _.filter(users, { 'age': 36, 'active': true });
 * // => objects for ['barney']
 *
 * // The `_.matchesProperty` iteratee shorthand.
 * _.filter(users, ['active', false]);
 * // => objects for ['fred']
 *
 * // The `_.property` iteratee shorthand.
 * _.filter(users, 'active');
 * // => objects for ['barney']
 */

function filter(collection, predicate) {
  let func = isArray_1(collection) ? _arrayFilter : _baseFilter;
  return func(collection, _baseIteratee(predicate, 3));
}

let filter_1 = filter;

/**
 * A specialized version of `_.forEach` for arrays without support for
 * iteratee shorthands.
 *
 * @private
 * @param {Array} [array] The array to iterate over.
 * @param {Function} iteratee The function invoked per iteration.
 * @returns {Array} Returns `array`.
 */
function arrayEach(array, iteratee) {
  let index = -1,
    length = array == null ? 0 : array.length;

  while (++index < length) {
    if (iteratee(array[index], index, array) === false) {
      break;
    }
  }

  return array;
}

let _arrayEach = arrayEach;

/**
 * Casts `value` to `identity` if it's not a function.
 *
 * @private
 * @param {*} value The value to inspect.
 * @returns {Function} Returns cast function.
 */

function castFunction(value) {
  return typeof value === 'function' ? value : identity_1;
}

let _castFunction = castFunction;

/**
 * Iterates over elements of `collection` and invokes `iteratee` for each element.
 * The iteratee is invoked with three arguments: (value, index|key, collection).
 * Iteratee functions may exit iteration early by explicitly returning `false`.
 *
 * **Note:** As with other "Collections" methods, objects with a "length"
 * property are iterated like arrays. To avoid this behavior use `_.forIn`
 * or `_.forOwn` for object iteration.
 *
 * @static
 * @memberOf _
 * @since 0.1.0
 * @alias each
 * @category Collection
 * @param {Array|Object} collection The collection to iterate over.
 * @param {Function} [iteratee=_.identity] The function invoked per iteration.
 * @returns {Array|Object} Returns `collection`.
 * @see _.forEachRight
 * @example
 *
 * _.forEach([1, 2], function(value) {
 *   console.log(value);
 * });
 * // => Logs `1` then `2`.
 *
 * _.forEach({ 'a': 1, 'b': 2 }, function(value, key) {
 *   console.log(key);
 * });
 * // => Logs 'a' then 'b' (iteration order is not guaranteed).
 */

function forEach(collection, iteratee) {
  let func = isArray_1(collection) ? _arrayEach : _baseEach;
  return func(collection, _castFunction(iteratee));
}

let forEach_1 = forEach;

/** Used for built-in method references. */

let objectProto$14 = Object.prototype;
/** Used to check objects for own properties. */

let hasOwnProperty$11 = objectProto$14.hasOwnProperty;
/**
 * Creates an object composed of keys generated from the results of running
 * each element of `collection` thru `iteratee`. The order of grouped values
 * is determined by the order they occur in `collection`. The corresponding
 * value of each key is an array of elements responsible for generating the
 * key. The iteratee is invoked with one argument: (value).
 *
 * @static
 * @memberOf _
 * @since 0.1.0
 * @category Collection
 * @param {Array|Object} collection The collection to iterate over.
 * @param {Function} [iteratee=_.identity] The iteratee to transform keys.
 * @returns {Object} Returns the composed aggregate object.
 * @example
 *
 * _.groupBy([6.1, 4.2, 6.3], Math.floor);
 * // => { '4': [4.2], '6': [6.1, 6.3] }
 *
 * // The `_.property` iteratee shorthand.
 * _.groupBy(['one', 'two', 'three'], 'length');
 * // => { '3': ['one', 'two'], '5': ['three'] }
 */

let groupBy = _createAggregator((result, value, key) => {
  if (hasOwnProperty$11.call(result, key)) {
    result[key].push(value);
  } else {
    _baseAssignValue(result, key, [value]);
  }
});
let groupBy_1 = groupBy;

/**
 * A specialized version of `_.reduce` for arrays without support for
 * iteratee shorthands.
 *
 * @private
 * @param {Array} [array] The array to iterate over.
 * @param {Function} iteratee The function invoked per iteration.
 * @param {*} [accumulator] The initial value.
 * @param {boolean} [initAccum] Specify using the first element of `array` as
 *  the initial value.
 * @returns {*} Returns the accumulated value.
 */
function arrayReduce(array, iteratee, accumulator, initAccum) {
  let index = -1,
    length = array == null ? 0 : array.length;

  if (initAccum && length) {
    accumulator = array[++index];
  }

  while (++index < length) {
    accumulator = iteratee(accumulator, array[index], index, array);
  }

  return accumulator;
}

let _arrayReduce = arrayReduce;

/**
 * The base implementation of `_.reduce` and `_.reduceRight`, without support
 * for iteratee shorthands, which iterates over `collection` using `eachFunc`.
 *
 * @private
 * @param {Array|Object} collection The collection to iterate over.
 * @param {Function} iteratee The function invoked per iteration.
 * @param {*} accumulator The initial value.
 * @param {boolean} initAccum Specify using the first or last element of
 *  `collection` as the initial value.
 * @param {Function} eachFunc The function to iterate over `collection`.
 * @returns {*} Returns the accumulated value.
 */
function baseReduce(collection, iteratee, accumulator, initAccum, eachFunc) {
  eachFunc(collection, (value, index, collection) => {
    accumulator = initAccum ? (initAccum = false, value) : iteratee(accumulator, value, index, collection);
  });
  return accumulator;
}

let _baseReduce = baseReduce;

/**
 * Reduces `collection` to a value which is the accumulated result of running
 * each element in `collection` thru `iteratee`, where each successive
 * invocation is supplied the return value of the previous. If `accumulator`
 * is not given, the first element of `collection` is used as the initial
 * value. The iteratee is invoked with four arguments:
 * (accumulator, value, index|key, collection).
 *
 * Many lodash methods are guarded to work as iteratees for methods like
 * `_.reduce`, `_.reduceRight`, and `_.transform`.
 *
 * The guarded methods are:
 * `assign`, `defaults`, `defaultsDeep`, `includes`, `merge`, `orderBy`,
 * and `sortBy`
 *
 * @static
 * @memberOf _
 * @since 0.1.0
 * @category Collection
 * @param {Array|Object} collection The collection to iterate over.
 * @param {Function} [iteratee=_.identity] The function invoked per iteration.
 * @param {*} [accumulator] The initial value.
 * @returns {*} Returns the accumulated value.
 * @see _.reduceRight
 * @example
 *
 * _.reduce([1, 2], function(sum, n) {
 *   return sum + n;
 * }, 0);
 * // => 3
 *
 * _.reduce({ 'a': 1, 'b': 2, 'c': 1 }, function(result, value, key) {
 *   (result[value] || (result[value] = [])).push(key);
 *   return result;
 * }, {});
 * // => { '1': ['a', 'c'], '2': ['b'] } (iteration order is not guaranteed)
 */

function reduce(collection, iteratee, accumulator) {
  let func = isArray_1(collection) ? _arrayReduce : _baseReduce,
    initAccum = arguments.length < 3;
  return func(collection, _baseIteratee(iteratee, 4), accumulator, initAccum, _baseEach);
}

let reduce_1 = reduce;

function getDelay(initial, step, jitter) {
  return Math.floor(initial * Math.pow(2, step) * (1 + jitter * (Math.random() * 2 - 1)));
}

function exponentialBackoff(callable) {
  let max = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : 8;
  let initial = arguments.length > 2 && arguments[2] !== undefined ? arguments[2] : 200;
  let jitter = arguments.length > 3 && arguments[3] !== undefined ? arguments[3] : 0.2;
  return new Promise(((resolve, reject) => {
    var tries = 0;

    var caller = function caller() {
      var delay = getDelay(initial, tries, jitter);
      callable().then(function (res) {
        resolve(res);
      }).catch(function (err) {
        if (tries >= max) {
          reject(err);
        } else {
          tries += 1;
          setTimeout(function () {
            caller();
          }, delay);
        }
      });
    };

    caller();
  }));
}
/**
 * Normalizes the resolution and rejection of a fetch request.
 * @param  {Request} request The request object. https://developer.mozilla.org/en-US/docs/Web/API/Request
 * @param  {responseHandlerCallback} responseHandler - The callback that handles the response.
 * @param  {errorHandlerCallback} errorHandler - The callback that handles the error from a failed request.
 * @return {Promise}         A promise that will resolve if the fetch response is OK. It will reject otherwise.
 */


/**
 * Retries a failed request using Exponential Backoff.
 * @param  {Request} request The request object. https://developer.mozilla.org/en-US/docs/Web/API/Request
 * @param  {responseHandlerCallback} responseHandler - The callback that handles the response.
 * @param  {errorHandlerCallback} errorHandler - The callback that handles the error from a failed request.
 * @return {Promise}  A wrapped fetch request that will retry if it encounters a failure.
 */

/**
 * The base implementation of methods like `_.findKey` and `_.findLastKey`,
 * without support for iteratee shorthands, which iterates over `collection`
 * using `eachFunc`.
 *
 * @private
 * @param {Array|Object} collection The collection to inspect.
 * @param {Function} predicate The function invoked per iteration.
 * @param {Function} eachFunc The function to iterate over `collection`.
 * @returns {*} Returns the found element or its key, else `undefined`.
 */
function baseFindKey(collection, predicate, eachFunc) {
  let result;
  eachFunc(collection, (value, key, collection) => {
    if (predicate(value, key, collection)) {
      result = key;
      return false;
    }
  });
  return result;
}

let _baseFindKey = baseFindKey;

/**
 * This method is like `_.find` except that it returns the key of the first
 * element `predicate` returns truthy for instead of the element itself.
 *
 * @static
 * @memberOf _
 * @since 1.1.0
 * @category Object
 * @param {Object} object The object to inspect.
 * @param {Function} [predicate=_.identity] The function invoked per iteration.
 * @returns {string|undefined} Returns the key of the matched element,
 *  else `undefined`.
 * @example
 *
 * var users = {
 *   'barney':  { 'age': 36, 'active': true },
 *   'fred':    { 'age': 40, 'active': false },
 *   'pebbles': { 'age': 1,  'active': true }
 * };
 *
 * _.findKey(users, function(o) { return o.age < 40; });
 * // => 'barney' (iteration order is not guaranteed)
 *
 * // The `_.matches` iteratee shorthand.
 * _.findKey(users, { 'age': 1, 'active': true });
 * // => 'pebbles'
 *
 * // The `_.matchesProperty` iteratee shorthand.
 * _.findKey(users, ['active', false]);
 * // => 'fred'
 *
 * // The `_.property` iteratee shorthand.
 * _.findKey(users, 'active');
 * // => 'barney'
 */

function findKey(object, predicate) {
  return _baseFindKey(object, _baseIteratee(predicate, 3), _baseForOwn);
}

let findKey_1 = findKey;

/**
 * The base implementation of `_.set`.
 *
 * @private
 * @param {Object} object The object to modify.
 * @param {Array|string} path The path of the property to set.
 * @param {*} value The value to set.
 * @param {Function} [customizer] The function to customize path creation.
 * @returns {Object} Returns `object`.
 */

function baseSet(object, path, value, customizer) {
  if (!isObject_1(object)) {
    return object;
  }

  path = _castPath(path, object);
  let index = -1,
    length = path.length,
    lastIndex = length - 1,
    nested = object;

  while (nested != null && ++index < length) {
    let key = _toKey(path[index]),
      newValue = value;

    if (index != lastIndex) {
      let objValue = nested[key];
      newValue = customizer ? customizer(objValue, key, nested) : undefined;

      if (newValue === undefined) {
        newValue = isObject_1(objValue) ? objValue : _isIndex(path[index + 1]) ? [] : {};
      }
    }

    _assignValue(nested, key, newValue);
    nested = nested[key];
  }

  return object;
}

let _baseSet = baseSet;

/**
 * The base implementation of  `_.pickBy` without support for iteratee shorthands.
 *
 * @private
 * @param {Object} object The source object.
 * @param {string[]} paths The property paths to pick.
 * @param {Function} predicate The function invoked per property.
 * @returns {Object} Returns the new object.
 */

function basePickBy(object, paths, predicate) {
  let index = -1,
    length = paths.length,
    result = {};

  while (++index < length) {
    let path = paths[index],
      value = _baseGet(object, path);

    if (predicate(value, path)) {
      _baseSet(result, _castPath(path, object), value);
    }
  }

  return result;
}

let _basePickBy = basePickBy;

/** Built-in value references. */

let getPrototype = _overArg(Object.getPrototypeOf, Object);
let _getPrototype = getPrototype;

/* Built-in method references for those with the same name as other `lodash` methods. */

let nativeGetSymbols$1 = Object.getOwnPropertySymbols;
/**
 * Creates an array of the own and inherited enumerable symbols of `object`.
 *
 * @private
 * @param {Object} object The object to query.
 * @returns {Array} Returns the array of symbols.
 */

let getSymbolsIn = !nativeGetSymbols$1 ? stubArray_1 : function (object) {
  let result = [];

  while (object) {
    _arrayPush(result, _getSymbols(object));
    object = _getPrototype(object);
  }

  return result;
};
let _getSymbolsIn = getSymbolsIn;

/**
 * This function is like
 * [`Object.keys`](http://ecma-international.org/ecma-262/7.0/#sec-object.keys)
 * except that it includes inherited enumerable properties.
 *
 * @private
 * @param {Object} object The object to query.
 * @returns {Array} Returns the array of property names.
 */
function nativeKeysIn(object) {
  let result = [];

  if (object != null) {
    for (let key in Object(object)) {
      result.push(key);
    }
  }

  return result;
}

let _nativeKeysIn = nativeKeysIn;

/** Used for built-in method references. */

let objectProto$15 = Object.prototype;
/** Used to check objects for own properties. */

let hasOwnProperty$12 = objectProto$15.hasOwnProperty;
/**
 * The base implementation of `_.keysIn` which doesn't treat sparse arrays as dense.
 *
 * @private
 * @param {Object} object The object to query.
 * @returns {Array} Returns the array of property names.
 */

function baseKeysIn(object) {
  if (!isObject_1(object)) {
    return _nativeKeysIn(object);
  }

  let isProto = _isPrototype(object),
    result = [];

  for (let key in object) {
    if (!(key == 'constructor' && (isProto || !hasOwnProperty$12.call(object, key)))) {
      result.push(key);
    }
  }

  return result;
}

let _baseKeysIn = baseKeysIn;

/**
 * Creates an array of the own and inherited enumerable property names of `object`.
 *
 * **Note:** Non-object values are coerced to objects.
 *
 * @static
 * @memberOf _
 * @since 3.0.0
 * @category Object
 * @param {Object} object The object to query.
 * @returns {Array} Returns the array of property names.
 * @example
 *
 * function Foo() {
 *   this.a = 1;
 *   this.b = 2;
 * }
 *
 * Foo.prototype.c = 3;
 *
 * _.keysIn(new Foo);
 * // => ['a', 'b', 'c'] (iteration order is not guaranteed)
 */

function keysIn(object) {
  return isArrayLike_1(object) ? _arrayLikeKeys(object, true) : _baseKeysIn(object);
}

let keysIn_1 = keysIn;

/**
 * Creates an array of own and inherited enumerable property names and
 * symbols of `object`.
 *
 * @private
 * @param {Object} object The object to query.
 * @returns {Array} Returns the array of property names and symbols.
 */

function getAllKeysIn(object) {
  return _baseGetAllKeys(object, keysIn_1, _getSymbolsIn);
}

let _getAllKeysIn = getAllKeysIn;

/**
 * Creates an object composed of the `object` properties `predicate` returns
 * truthy for. The predicate is invoked with two arguments: (value, key).
 *
 * @static
 * @memberOf _
 * @since 4.0.0
 * @category Object
 * @param {Object} object The source object.
 * @param {Function} [predicate=_.identity] The function invoked per property.
 * @returns {Object} Returns the new object.
 * @example
 *
 * var object = { 'a': 1, 'b': '2', 'c': 3 };
 *
 * _.pickBy(object, _.isNumber);
 * // => { 'a': 1, 'c': 3 }
 */

function pickBy(object, predicate) {
  if (object == null) {
    return {};
  }

  let props = _arrayMap(_getAllKeysIn(object), (prop) => {
    return [prop];
  });
  predicate = _baseIteratee(predicate);
  return _basePickBy(object, props, (value, path) => {
    return predicate(value, path[0]);
  });
}

let pickBy_1 = pickBy;

/**
 * The base implementation of `_.map` without support for iteratee shorthands.
 *
 * @private
 * @param {Array|Object} collection The collection to iterate over.
 * @param {Function} iteratee The function invoked per iteration.
 * @returns {Array} Returns the new mapped array.
 */

function baseMap(collection, iteratee) {
  let index = -1,
    result = isArrayLike_1(collection) ? Array(collection.length) : [];
  _baseEach(collection, (value, key, collection) => {
    result[++index] = iteratee(value, key, collection);
  });
  return result;
}

let _baseMap = baseMap;

/**
 * Creates an array of values by running each element in `collection` thru
 * `iteratee`. The iteratee is invoked with three arguments:
 * (value, index|key, collection).
 *
 * Many lodash methods are guarded to work as iteratees for methods like
 * `_.every`, `_.filter`, `_.map`, `_.mapValues`, `_.reject`, and `_.some`.
 *
 * The guarded methods are:
 * `ary`, `chunk`, `curry`, `curryRight`, `drop`, `dropRight`, `every`,
 * `fill`, `invert`, `parseInt`, `random`, `range`, `rangeRight`, `repeat`,
 * `sampleSize`, `slice`, `some`, `sortBy`, `split`, `take`, `takeRight`,
 * `template`, `trim`, `trimEnd`, `trimStart`, and `words`
 *
 * @static
 * @memberOf _
 * @since 0.1.0
 * @category Collection
 * @param {Array|Object} collection The collection to iterate over.
 * @param {Function} [iteratee=_.identity] The function invoked per iteration.
 * @returns {Array} Returns the new mapped array.
 * @example
 *
 * function square(n) {
 *   return n * n;
 * }
 *
 * _.map([4, 8], square);
 * // => [16, 64]
 *
 * _.map({ 'a': 4, 'b': 8 }, square);
 * // => [16, 64] (iteration order is not guaranteed)
 *
 * var users = [
 *   { 'user': 'barney' },
 *   { 'user': 'fred' }
 * ];
 *
 * // The `_.property` iteratee shorthand.
 * _.map(users, 'user');
 * // => ['barney', 'fred']
 */

function map(collection, iteratee) {
  let func = isArray_1(collection) ? _arrayMap : _baseMap;
  return func(collection, _baseIteratee(iteratee, 3));
}

let map_1 = map;

/* eslint no-param-reassign: ["error", { "props": false }] */

/* eslint no-return-assign: ["error", "except-parens"] */
let JsonApiSchema =
/*#__PURE__ */
(function () {
  function JsonApiSchema(resource, schema) {
    _classCallCheck(this, JsonApiSchema);

    this.type = resource;
    this.schema = schema;
  }
  /**
   * Sets one or more values on a model returning a new instance.
   * @param {string|object} field If a string, this is the field from the schema to set.
   *                              If an object, it's a set of field value pairs.
   * @param {string|array|onject|boolean} value The value to be set. Ignored if field is an object.
   */


  _createClass(JsonApiSchema, [{
    key: 'setValue',
    value: function setValue(field, value) {
      var newModel = new this.constructor();

      if (typeof field === 'string') {
        newModel.setField(field, value);
      } else {
        forEach_1(field, function (val, key) {
          newModel.setField(key, val);
        });
      }

      return assign_1(new this.constructor(), this, newModel);
    }
    /**
     * Sets a single field value on a model
     * @param {string} field The field from the schema to set.
     * @param {string|array|object|boolean} value The value to be set
     */

  }, {
    key: 'setField',
    value: function setField(field, value) {
      var schema = this.schema;

      if (field in schema === false) {
        return this;
      }

      switch (schema.type) {
        case 'relationship':
          this.relationships[field] = {
            data: {
              type: schema[field].ref,
              id: value
            }
          };
          break;

        default:
          this.attributes[field] = value;
      }
    }
  }]);

  return JsonApiSchema;
}());
/**
 * Get a JSON_API attribute getter for a resource.
 * @param  {Object} resource  JSON_API formatted resource.
 * @param  {String} attribute The resource attribute to get
 * @return {function} A getter for the attribute.
 */

function getAttribute(resource, attribute) {
  return function () {
    return resource._data.attributes[attribute];
  };
}
/**
 * Get a JSON_API attribute setter for a resource.
 * @param  {Object} resource  JSON_API formatted resource.
 * @param  {String} attribute The resource attribute to set
 * @return {function} A setter for the attribute.
 */


function setAttribute(resource, attribute) {
  return function (value) {
    return resource._data.attributes[attribute] = value;
  };
}
/**
 * Get a JSON_API relationship getter for a resource.
 * @param  {Object}   resource      JSON_API formatted resource.
 * @param  {String}   relationship  The resource relationship to get
 * @param  {Boolean}  multiple      Whether or nohis is a multi-value relationship
 * @return {function} A getter for the relationship.
 */


function getRelationship(resource, relationship, multiple) {
  return function () {
    if (relationship in resource._data.relationships === false) {
      return undefined;
    } //
    // Handle multi-value fields
    //


    if (multiple) {
      return map_1(resource._data.relationships[relationship].data, (d) => {
        return d ? d.id : d;
      });
    } //
    // Handle single value fields
    //


    return resource._data.relationships[relationship].data ? resource._data.relationships[relationship].data.id : resource._data.relationships[relationship].data;
  };
}
/**
 * Get a JSON_API relationship setter for a resource.
 * @param  {Object}   resource      JSON_API formatted resource.
 * @param  {String}   relationship  The resource relationship to set
 * @param  {Boolean}  multiple      Whether or not this is a multi-value relationship
 * @return {function} A setter for the relationship.
 */


function setRelationship(resource, relationship, type, multiple) {
  return function (value) {
    if (relationship in resource._data.relationships === false) {
      resource._data.relationships[relationship] = {};
    } //
    // Handle multi-value fields
    //


    if (multiple) {
      let valueArray = [].concat(value);
      return resource._data.relationships[relationship].data = map_1(valueArray, (v) => {
        return {
          type: type,
          id: v
        };
      });
    } //
    // Handle single value fields
    //


    return resource._data.relationships[relationship].data = {
      type,
      id: value,
    };
  };
}

let JsonApiModel = function JsonApiModel(schema) {
  let _this = this;

  _classCallCheck(this, JsonApiModel);

  this._data = {
    type: schema.type,
    attributes: {},
    relationships: {},
  }; // Create getters and setters for each prop.

  forEach_1(schema.schema, (value, key) => {
    Object.defineProperty(_this, value.alias ? value.alias : key, {
      get: value.type === 'relationship' ? getRelationship(_this, key, value.multiple) : getAttribute(_this, key),
      set: value.type === 'relationship' ? setRelationship(_this, key, value.ref, value.multiple) : setAttribute(_this, key)
    });
  });
};

let Registrar =
/* #__PURE__*/
(function () {
  function Registrar(name) {
    _classCallCheck(this, Registrar);

    this.name = name;
    this.manifest = {};
    this.register = this.register.bind(this);
    this.getByResource = this.get.bind(this);
  }

  _createClass(Registrar, [{
    key: 'register',
    value: function register(id, instance) {
      this.manifest[id] = instance;
    }
  }, {
    key: 'get',
    value: function get(id) {
      return this.manifest[id];
    }
  }]);

  return Registrar;
}());

let modelRegistrar = new Registrar('entityManagers');
function loadEntity(resource, id, state) {
  // if (!state.hasOwnProperty(resource)) {
  //   logger.log(new Error(`Tried to load an entity from a resource, ${resource}, that does not exist.`));
  //   return null;
  // }
  //
  // const entity = state[resource].items[uuid];
  //
  // if (!entity) {
  //   logger.log(new Error(`Tried to load a ${resource} entity, ${uuid}, that does not exist.`));
  //   return null;
  // }
  //
  // return entity;
  return state[resource].items[id];
}
function loadEntityFromState(state) {
  return function (resource, id) {
    return loadEntity(resource, id, state);
  };
}
/**
 * An EntityModel is used to manage the back and forth transformation between
 *  JSON_API structured resources and locally stored entity objects.
 *  EntityModel instances are created on a per resource type basis.
 *
 * @type {EntityModel}
 */

let EntityModel =
/* #__PURE__*/
(function () {
  /**
   * Entity Manager constructor
   * @param  {String} type    Drupal entity type. ex. 'node', 'taxonomy_term'
   * @param  {String} bundle  Drupal bundle type. ex. 'article', 'tags'
   * @param  {Object} schema  Schema definition describing how fields map to JSON_API
   */
  function EntityModel(type, bundle, schema) {
    _classCallCheck(this, EntityModel);

    var resource = ''.concat(type, '--').concat(bundle);
    this.type = type;
    this.bundle = bundle;
    this.resource = resource; // JSON_API resource type

    this.schema = schema;
    this.jsonApiSchema = new JsonApiSchema(resource, schema);
    this.model = new JsonApiModel(this.jsonApiSchema);
    this.getDependentFields = this.getDependentFields.bind(this);
    this.dependentFields = this.getDependentFields();
    this.getFields = this.getFields.bind(this);
    modelRegistrar.register(resource, this);
  }
  /**
   * Create a new entity POJO to store data and state.
   * @param  {Object} data Initial entity data fields.
   * @return {Object}      Standard entity formatted data.
   */


  _createClass(EntityModel, [{
    key: 'getDependentUuids',

    /**
     * Returns a flat array of dependent entity uuids
     * @params
     */
    value: function getDependentUuids(id, state) {
      var dependentFields = this.dependentFields,
          resource = this.resource;
      var item = loadEntityFromState(state)(resource, id);
      return dependentFields.reduce(function (acc, field) {
        return (// Ignore null values.
          acc.concat([].concat(item.data[field]).filter(function (i) {
            return i;
          }))
        );
      }, []);
    }
    /**
     * Returns a flat array of field names. Uses alias if available.
     * @param {boolean}
     *   If aliases should be used or the root field name.
     */

  }, {
    key: 'getFields',
    value: function getFields(useAlias) {
      var schema = this.schema;
      var fields = Object.keys(schema);
      return !useAlias ? fields : fields.map(function (field) {
        return schema[field].alias || field;
      });
    }
    /**
     * Returns a flat array of dependent field names. Uses alias if available.
     * @params
     */

  }, {
    key: 'getDependentFields',
    value: function getDependentFields() {
      var schema = this.schema;
      var dependentFields = Object.keys(schema).filter(function (field) {
        return schema[field].dependency;
      }).map(function (field) {
        return schema[field].alias || field;
      });
      return dependentFields;
    }
    /**
     * Returns an array of relationships
     */

  }, {
    key: 'getRelationships',
    value: function getRelationships() {
      return keys_1(pickBy_1(this.schema, function (o) {
        return o.type === 'relationship';
      }));
    }
    /**
     * Returns an array of relationships with their aliases.
     */

  }, {
    key: 'getRelationshipAliases',
    value: function getRelationshipAliases() {
      var _this = this;

      return this.getRelationships().map(function (r) {
        return _this.getPropertyAlias(r);
      });
    }
    /**
     * Returns a property alias if it exist.
     */

  }, {
    key: 'getPropertyAlias',
    value: function getPropertyAlias(property) {
      if (property in this.schema && 'alias' in this.schema[property]) {
        return this.schema[property].alias;
      }

      return property;
    }
    /**
     * Returns a property alias if it exist.
     */

  }, {
    key: 'getPropertyFromAlias',
    value: function getPropertyFromAlias(alias) {
      return findKey_1(this.schema, function (property) {
        return 'alias' in property && property.alias === alias;
      }) || alias;
    }
    /**
     * Converts JSON_API formated Entity into a plain object.
     * @param  {Object} entity JSON_API formatted object
     * @return {Object}        Plain object representation of the data.
     */

  }], [{
    key: 'create',
    value: function create(data) {
      var id = data.attributes.uuid || v4_1();
      var mergedData = Object.assign({}, data);
      mergedData.attributes = mergedData.attributes || {};
      mergedData.attributes.uuid = id;
      return {
        id: id,
        data: mergedData,
        state: {
          saved: false,
          // Exists remotely.
          syncing: null,
          // Request sent, response not yet received
          error: null,
          // { status: '403', message: 'Forbidden'}
          dirty: true // Local Changes, not yet synced

        }
      };
    }
  }, {
    key: 'import',
    value: function _import(entity) {
      return entity;
    }
    /**
     * Converts plain object into a JSON_API representation based on a schema.
     * @param  {Object} entity Plain object representation of the data.
     * @return {Object}        JSON_API formatted object
     */

  }, {
    key: 'export',
    value: function _export(entity) {
      // const model = new JsonApiModel(this.jsonApiSchema);
      var data = _objectSpread({}, entity.data);

      var ignoredAttributes = ['created', 'changed', 'nid', 'tid', 'id'];
      var ignoredRelationships = ['node_type'];
      ignoredAttributes.forEach(function (prop) {
        delete data.attributes[prop];
      });
      ignoredRelationships.forEach(function (prop) {
        delete data.relationships[prop];
      });
      return {
        data: data
      };
    }
  }]);

  return EntityModel;
}());

let _arguments = arguments;

/* eslint prefer-destructuring: "off" */
let logger = {};
logger.canLog = null;
logger.canApply = null;
logger.canGroup = null;
logger.canError = null; // define contexts and whether they can console.log or not
// import {LOG} from './../config';

let LOG = false;
logger.debugSettings = LOG;
/**
 * Check if we are in a console capable system
 */

logger.init = function () {
  logger.canLog = typeof console !== 'undefined' && typeof console.log !== 'undefined';
  logger.canApply = typeof console.log.apply !== 'undefined';
  logger.canGroup = typeof console.group !== 'undefined';
  logger.canError = typeof console.error !== 'undefined';
};
/**
 * Log a message, taking context and loggability into account.
 */


logger.log = function () {
  let context = 'master';
  let thisArguments = Array.prototype.slice.call(_arguments);

  if (logger.canLog === null) {
    logger.init();
  }

  if (_arguments.length > 1) {
    if (typeof _arguments[0] === 'string' && typeof logger.debugSettings[_arguments[0]] !== 'undefined') {
      context = _arguments[0];
      thisArguments.shift();
    }
  }

  if (typeof logger.debugSettings[context] !== 'undefined' && logger.debugSettings[context]) {
    if (logger.canLog) {
      if (logger.canApply) {
        let _console;

        return (_console = console).log.apply(_console, _toConsumableArray(thisArguments));
      } // non-apply version for some browsers (*cough* ie)


      console.log(thisArguments);
    }
  }
};
/**
 * Log a message, taking context and loggability into account.
 */


logger.group = function () {
  let context = 'master';
  let thisArguments = Array.prototype.slice.call(_arguments);

  if (logger.canLog === null) {
    logger.init();
  }

  if (_arguments.length > 1) {
    if (typeof _arguments[0] === 'string' && typeof logger.debugSettings[_arguments[0]] !== 'undefined') {
      context = _arguments[0];
      thisArguments.shift();
    }
  }

  if (typeof logger.debugSettings[context] !== 'undefined' && logger.debugSettings[context]) {
    if (logger.canGroup) {
      // non-apply version for some browsers (*cough* ie)
      console.group(thisArguments);
    }
  }
};
/**
 * Log a message, taking context and loggability into account.
 */


logger.groupEnd = function () {
  let context = 'master';
  let thisArguments = Array.prototype.slice.call(_arguments);

  if (logger.canLog === null) {
    logger.init();
  }

  if (_arguments.length > 1) {
    if (typeof _arguments[0] === 'string' && typeof logger.debugSettings[_arguments[0]] !== 'undefined') {
      context = _arguments[0];
      thisArguments.shift();
    }
  }

  if (typeof logger.debugSettings[context] !== 'undefined' && logger.debugSettings[context]) {
    if (logger.canGroup) {
      // non-apply version for some browsers (*cough* ie)
      console.groupEnd(thisArguments);
    }
  }
};
/**
 * Log a message, taking context and loggability into account.
 */


logger.error = function () {
  let context = 'master';
  let thisArguments = Array.prototype.slice.call(_arguments);

  if (logger.canError === null) {
    logger.init();
  }

  if (_arguments.length > 1) {
    if (typeof _arguments[0] === 'string' && typeof logger.debugSettings[_arguments[0]] !== 'undefined') {
      context = _arguments[0];
      thisArguments.shift();
    }
  }

  if (typeof logger.debugSettings[context] !== 'undefined' && logger.debugSettings[context]) {
    if (logger.canError) {
      // non-apply version for some browsers (*cough* ie)
      console.error(thisArguments);
    }
  }
};

let url = require('url');

let URL = require('url-parse');

let apiRegistrar = new Registrar('api'); // Limit file reads to 4 per second.

let readFileLimiter = new lib({
  maxConcurrent: 4,
  minTime: 1000 / 4,
}); // Limit reads to 6 per second.

let readLimiter = new lib({
  maxConcurrent: 6,
  minTime: 1000 / 6,
}); // Limit writes to 2 at a time and 3 per second.

let writeLimiter = new lib({
  maxConcurrent: 2,
  minTime: 1000 / 3,
}); // Limit validates to 1 at a time and 3 per second.

let validateLimiter = new lib({
  maxConcurrent: 1,
  minTime: 1000 / 3,
});
/**
 * Generic fetch resolve handler.
 * @param  {Request} request The request object. https://developer.mozilla.org/en-US/docs/Web/API/Request
 * @param  {function} resolve Promise.prototype.resolve()
 * @param  {function} reject Promise.prototype.reject()
 * @return {function} A handler that takes the error object from a failed fetch.
 */

function responseHandler(request$$1, resolve, reject) {
  return function (resp) {
    // Handle a successful response.
    if (resp.ok) {
      resolve(resp);
    }
 else {
      // Handle a failed response.
      switch (resp.status) {
        // Don't retry:
        //   403 - Permission denied responses.
        //   404 - Not Found.
        //   409 - Conflict.
        //   422 - Failed validation.
        //   504 - Timeout.
        case 403:
        case 404:
        case 409:
        case 422:
        case 504:
          resolve(resp);
          break;
        // Retry all other responses.

        default:
          reject(new Error(resp.status.toString()));
      }
    }
  };
}
/**
 * Generic fetch rejection handler.
 * @param  {Request} request The request object. https://developer.mozilla.org/en-US/docs/Web/API/Request
 * @param  {function} resolve Promise.prototype.resolve()
 * @param  {function} reject Promise.prototype.reject()
 * @return {function} A handler that takes the error object from a failed fetch.
 */


function errorHandler(request$$1, resolve, reject) {
  return function (err) {
    logger.log('network', 'Rejected fetch', err);
    reject(err);
  };
}
/**
 * Handles dispatching a successful API call's response.
 * @param  {Object}   resp     Response object instance.
 * @param  {Function} dispatch Redux dispatch function.
 * @param  {String}   resource Resource type
 * @param  {Object}   model  EntityManager for this resource.
 * @param  {String}   [uuid]     UUID of the resource
 */


/**
 * Handles dispatching a failed API call's response.
 * @param  {Object}   resp     Response object instance.
 */


/**
 * Handles dispatching a failed API call's response.
 * @param  {Object}   resp     Response object instance.
 * @param  {Function} dispatch Redux dispatch function.
 * @param  {String}   resource Resource type
 * @param  {Object}   model  EntityManager for this resource.
 * @param  {String}   [uuid]     UUID of the resource
 */


/**
 * Handles dispatching a failed API call's response.
 * @param  {Object}   resp     Response object instance.
 * @param  {Function} dispatch Redux dispatch function.
 * @param  {String}   resource Resource type
 * @param  {String}   [uuid]     UUID of the resource
 */

function handleFailedResponse(resp, request$$1, dispatch, resource, uuid) {
  return resp.json().then((json) => {
    logger.log('network', 'Failed json response', json);
    var err;
    if (json.errors) {
      err = json.errors;
    }
    else {
      err = [''.concat(resp.status, ': ').concat(resp.statusText || 'No status message provided')]
    }
    if (request$$1.method === 'PATCH' && resp.status === 404) {
      dispatch(failure(err, resource, uuid));
      return dispatch(setSaved(false, resource, uuid));
    }
    // Mark an existing entity as saved.
    if (request$$1.method === 'POST' && resp.status === 409) {
      dispatch(failure(err, resource, uuid));
      return dispatch(setSaved(true, resource, uuid));
    }

    if (request$$1.method === 'PATCH' && resp.status === 422) {
      return dispatch(failure(err, resource, uuid));
    }

    return dispatch(failure(err, resource, uuid));
  }).catch((err) => {
    return dispatch(failure(err, resource, uuid));
  });
}
/**
 * Processes included resources, adding them to the store.
 * @param {Function} dispatch
 *   Redux dispatch function
 * @returns {Function}
 *   Accepts an array of included resource objects, groups them by resource type and
 *   dispatches a receive action for each type.
 */

function processIncludes(dispatch) {
  return function (includes) {
    let resources = groupBy_1(includes, (record) => {
      return record.type;
    });
    forEach_1(resources, (records, resource) => {
      dispatch(receive({
        data: records.map(EntityModel.import)
      }, resource));
    });
  };
}
/**
 * Handles dispatching a successful API call's response.
 * @param  {Object}   resp     Response object instance.
 * @param  {Function} dispatch Redux dispatch function.
 * @param  {String}   resource Resource type
 * @param  {Object}   model  EntityManager for this resource.
 * @param  {String}   [uuid]     UUID of the resource
 */

function handleSuccessResponse(resp, dispatch, resource, model, uuid) {
  return resp.json().then((json) => {
    logger.group('network', 'response');
    logger.log('network', 'Response: '.concat(resource), json); // Handle cases where the response doesn't have a nested data object,
    //  such as file uploads.

    var data = 'data' in json ? json.data : json;
    var output = {
      data: Array.isArray(data) ? data.map(EntityModel.import) : EntityModel.import(data)
    };
    logger.log('network', output.data);
    logger.groupEnd('network', 'response');

    if ('included' in json) {
      processIncludes(dispatch)(json.included);
    }

    return dispatch(receive(output, resource, uuid));
  });
}
/**
 * Handles dispatching a failed API call's response.
 * @param  {Object}   resp     Response object instance.
 * @param  {Function} dispatch Redux dispatch function.
 * @param  {String}   resource Resource type
 * @param  {Object}   model  EntityManager for this resource.
 * @param  {String}   [uuid]     UUID of the resource
 */

function handleResponse(resp, request$$1, dispatch, resource, model, uuid) {
  logger.log('network', 'Response', resp, resource, uuid);

  if (resp.ok) {
    return handleSuccessResponse(resp, dispatch, resource, model, uuid);
  }

  return handleFailedResponse(resp, request$$1, dispatch, resource, uuid);
}
/**
 * Handles a network error in a request.
 * @param  {Function} dispatch Redux dispatch function.
 * @param  {String}   resource [description]
 * @param  {[type]} id       [description]
 * @return {[type]}          [description]
 */

function handleNetworkError(dispatch, resource, id) {
  return function (error) {
    let message = 'There has been a problem with your connection: '.concat(error.message);
    dispatch(failure(message, resource, id));
    logger.log('network', message, error);
  };
}
let ApiManager =
/*#__PURE__ */
(function () {
  function ApiManager(options) {
    _classCallCheck(this, ApiManager);

    var model = options.model;
    this.model = model;
    this.type = model.type;
    this.bundle = model.bundle;
    this.resource = model.resource;
    this.fields = _defineProperty$2({}, model.resource, model.getFields(false));
    this.include = options.include || [];
    this.namespace = options.namespace || 'jsonapi';
    this.priority = options.priority || 9;
    this.latestFetch = null; // Bind methods.

    this.getLatestFetch = this.getLatestFetch.bind(this);
    this.setLatestFetch = this.setLatestFetch.bind(this);
    this.getEndpoint = this.getEndpoint.bind(this);
    this.getEndpointPath = this.getEndpointPath.bind(this);
    this.getRelationshipEndpoint = this.getRelationshipEndpoint.bind(this);
    this.getTimestampEndpoint = this.getTimestampEndpoint.bind(this);
    this.fetchAll = this.fetchAll.bind(this);
    this.fetchResource = this.fetchResource.bind(this);
    this.fetchTranslations = this.fetchTranslations.bind(this);
    this.sync = this.sync.bind(this);
    this.updateRelationshipsIfNeeded = this.updateRelationshipsIfNeeded.bind(this);
    this.backoffFetch = this.backoffFetch.bind(this);
    this.wrapFetch = this.wrapFetch.bind(this); // Register this instance.

    apiRegistrar.register(model.resource, this);
  }
  /**
   * Creates a url string for making JSON_API requests
   * @param  {object|string} options
   *  A url object as returned by url.parse() or a fully constructed url which will be passed through url.parse
   * @return {string} Fully formed url origin segment ex. https://www.example.com.
   */


  _createClass(ApiManager, [{
    key: 'getEndpointPath',

    /**
     * Creates a url string for making JSON_API requests
     * @param  {object|string} options
     *  A url object as returned by url.parse() or a fully constructed url which will be passed through url.parse
     * @return {string}
     *  Fully formed url origin segment ex. https://www.example.com.
     */
    value: function getEndpointPath() {
      return [this.namespace, this.type, this.bundle].join('/');
    }
    /**
     * Creates a query parameter object formatted for filters
     * See https://www.drupal.org/docs/8/modules/json-api/filtering
     * @param  {Object} filters
     *  @todo document
     * @return {Object}
     *  An object of query param key|value pairs
     */

  }, {
    key: 'getEndpointQueryParams',

    /**
     * Creates a query parameter object formatted for sparse fieldsets
     * See https://www.drupal.org/docs/8/modules/json-api/collections-filtering-and-sorting
     * See http://jsonapi.org/format/#fetching-sparse-fieldsets
     * @param  {Object} fields
     *  @todo document
     * @return {Object}
     *  An object of query param key|value pairs
     */
    value: function getEndpointQueryParams(options) {
      // Generate query params.
      var params = options.params || {}; // Set filters if they exist.

      var filters = options.filters ? this.constructor.getEndpointFilters(options.filters) : {}; // Set fields if they exist.

      var fields = this.constructor.getEndpointFields(options.fields || options.fields === null || this.fields || {}); // Set include if they exist.

      var include = options.include ? this.constructor.getEndpointInclude(options.include) : {}; // Set sorts if they exist.

      var sort = options.sort ? this.constructor.getEndpointSort(options.sort) : {}; // Set limit if specified.

      var limit = options.limit ? this.constructor.getEndpointLimit(options.limit) : {}; // Set offset if specified.

      var offset = options.offset ? this.constructor.getEndpointOffset(options.offset) : {};
      return assign_1(params, filters, fields, limit, include, offset, sort);
    }
    /**
     * Creates a url string for making JSON_API requests
     * @param  {string} resource        The machine name of the resource.
     * @param  {object} options         Additional request parameters.
     * @param  {string} [options.lang]
     *  Translation language code. _ex. 'en'
     * @param  {string} options.bundle  Entity bundle. ex. 'article', 'tags'
     * @param  {Object} [options.fields]  An object in which the properties are resource types and the value is an array of attribute name strings
     * @param  {Array}  [options.include] An array of resource type strings.
     *
     * @return {string}           Fully formed url.
     */

  }, {
    key: 'getEndpoint',
    value: function getEndpoint(options) {
      var origin = this.constructor.getEndpointOrigin(); // Generate the path.

      var pathParts = ['']; // Add translation if needed.

      if (options.lang) {
        pathParts.push(options.lang);
      } // Add the collection specific path parts.


      pathParts.push(this.getEndpointPath()); // If this is resource specific, add the id.

      if (options.id) {
        pathParts.push(options.id);
      }

      var pathname = pathParts.join('/'); // Generate query params.

      var query = this.getEndpointQueryParams({
        params: options.params,
        fields: options.fields,
        filters: options.filters,
        include: options.include,
        limit: options.limit,
        offset: options.offset,
        sort: options.sort
      }); // Format the url string.

      return url.format({
        origin: origin,
        pathname: pathname,
        query: query
      });
    }
    /**
     * Creates a url string for making JSON_API relationship requests
     * @param  {object} options         Additional request parameters.
     * @param  {string} options.id      UUID o
     *
     * @return {string}           Fully formed url.
     */

  }, {
    key: 'getRelationshipEndpoint',
    value: function getRelationshipEndpoint(options) {
      var origin = this.constructor.getEndpointOrigin(); // Generate the path.

      var pathParts = ['']; // Add translation if needed.

      if (options.lang) {
        pathParts.push(options.lang);
      } // Add the collection specific path parts.


      pathParts.push(this.getEndpointPath()); // If this is resource specific, add the id.

      pathParts.push(options.id);
      pathParts.push('relationships');
      pathParts.push(options.relationship);
      var pathname = pathParts.join('/');
      var query = this.getEndpointQueryParams({
        params: options.params,
        fields: options.fields,
        filters: options.filters,
        include: options.include
      }); // Format the url string.

      return url.format({
        origin: origin,
        pathname: pathname,
        query: query
      });
    }
  }, {
    key: 'getTimestampEndpoint',
    value: function getTimestampEndpoint() {
      var origin = this.constructor.getEndpointOrigin();
      var pathname = 'intercept/time'; // Format the url string.

      return url.format({
        origin: origin,
        pathname: pathname
      });
    }
  }, {
    key: 'getLatestFetch',
    value: function getLatestFetch() {
      return this.latestFetch;
    }
  }, {
    key: 'setLatestFetch',
    value: function setLatestFetch(id) {
      this.latestFetch = id;
      return id;
    }
  }, {
    key: 'fetcher',
    value: function fetcher() {
      var _this = this;

      var options = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};
      var nextLink;
      var totalFetched = 0;
      var done = false;
      var replace = options.replace || false;

      var getNextLink = function getNextLink() {
        return nextLink;
      };

      var getDone = function getDone() {
        return done;
      };

      return {
        next: function next() {
          return _this.fetchAll(_objectSpread({}, options, {
            endpoint: getNextLink(),
            totalFetched: totalFetched,
            replace: replace,
            onNext: function onNext(endpoint, total) {
              nextLink = endpoint;
              totalFetched = total;
              replace = false;
            },
            onDone: function onDone() {
              if (options.onDone) {
                options.onDone();
              }

              done = true;
            }
          }));
        },
        isDone: function isDone() {
          return getDone();
        }
      };
    }
    /**
     * Fetches a resource collection.
     * @param {Object} options
     */

  }, {
    key: 'fetchAll',
    value: function fetchAll() {
      var _this2 = this;

      var options = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};
      // on successful JSON response, map data to this.EntityModel.import
      // then dispatch success, type, data (transformed data)
      var backoffFetch$$1 = this.backoffFetch,
          resource = this.resource,
          getLatestFetch = this.getLatestFetch,
          setLatestFetch = this.setLatestFetch;
      var fetchTimestamp = this.constructor.fetchTimestamp;
      var _this$constructor = this.constructor,
          getRequest = _this$constructor.getRequest,
          getTimestamp = _this$constructor.getTimestamp;
      var currentFetch = setLatestFetch(v4_1());
      var filters = options.filters || [];
      var include = options.include || [];
      var sort = options.sort || [];
      var count = options.count || 0;
      var totalFetched = options.totalFetched || 0;
      var fields = options.fields,
          limit = options.limit,
          offset = options.offset,
          onNext = options.onNext,
          onDone = options.onDone;

      var _fetchAll = this.fetchAll.bind(this);

      var replace = options.replace || false;
      return function (dispatch, getState) {
        var state = getState(); //
        // Handle request for recent content.
        //

        if (options.recent && state[resource].updated) {
          filters.push({
            path: 'changed',
            value: state[resource].updated,
            operator: '>'
          });
        } //
        // Construct and endpoint if one was not supplied
        //


        var endpoint = options.endpoint || _this2.getEndpoint({
          filters: filters,
          include: include,
          fields: fields,
          sort: sort,
          limit: limit,
          offset: offset
        }); //
        // Generate the request object
        //


        var request$$1 = getRequest(endpoint, options); //
        // Dispatch API collection request action.
        //

        dispatch(request(resource));
        logger.log('network', 'Request', request$$1); //
        // Make the actual API call
        //

        function makeApiCall() {
          //
          // Get the current timestamp
          // This is referenced later when fetching fresh data, or data changed after this timestamp.
          //
          var fetchTime = fetchTimestamp(getState).then(function (time) {
            return time;
          }).catch(function (err) {
            logger.log(err);
          }); //
          // Fetch the data.
          //

          var fetchData = backoffFetch$$1(request$$1, responseHandler, errorHandler).then(function (resp) {
            //
            // Handle an OK response
            //
            if (resp.ok) {
              resp.json().then(function (json) {
                //
                // Abort if there's a new request in route.
                //
                if (replace && currentFetch !== getLatestFetch()) {
                  return;
                }

                //
                // Ensure the response data is an Array
                //
                var output = {
                  data: [].concat(json.data)
                };
                totalFetched += output.data.length;

                //
                // Log network response
                //
                logger.group('network', 'response');
                logger.log('network', 'Response: '.concat(getTimestamp(), ' ').concat(resource), json);
                logger.log('network', output.data);
                logger.groupEnd('network', 'response');

                //
                // Process included resources.
                //
                if ('included' in json) {
                  processIncludes(dispatch)(json.included);
                }

                //
                // Purge store if replacing.
                //
                if (replace && currentFetch === getLatestFetch()) {
                  dispatch(purge(resource)); // Ensure it only purges once.
                  replace = false;
                }

                // Cancel receive action if the current fetch doesn't match the latest
                // This is to prevent paginated requests from being added to the results.
                if (currentFetch !== getLatestFetch()) {
                  return;
                }

                //
                // Dispatch Receive action
                //
                dispatch(receive(output, resource));
                var hasMore = json.links && json.links.next;

                if (!hasMore) {
                  // Call onDone() then exit.
                  if (onDone) {
                    onDone();
                  }

                  return;
                }

                //
                // Recursively fetch paginated items.
                //
                if (count === 0 || count > totalFetched && currentFetch === getLatestFetch()) {
                  dispatch(_fetchAll(_objectSpread({}, options, {
                    endpoint: json.links.next.href,
                    totalFetched: totalFetched,
                    replace: replace
                  })));
                } else if (onNext && currentFetch === getLatestFetch()) {
                  // Call onNext()
                  onNext(json.links.next.href, totalFetched);
                }
              });
            }

            //
            // Handle a NOT OK response
            //
            else {
              dispatch(failure(''.concat(resp.status, ': ').concat(resp.statusText || 'No status message provided'), resource));
            }

            return resp;
          }) //
          // Catch network error
          //
          .catch(handleNetworkError(dispatch, resource));
          return Promise.all([fetchTime, fetchData]).then(function (values) {
            //
            // Set the collection updated timestamp.
            //
            dispatch(setTimestamp(resource, values[0])); //
            // Return the fetched data.
            //

            return values[1];
          }).catch(function (err) {
            logger.log(err);
          });
        } //
        // Make the API call
        //


        return makeApiCall();
      };
    }
    /**
     * Fetches a resource collection.
     * @param {Object} options
     */

  }, {
    key: 'fetchResource',
    value: function fetchResource(uuid) {
      var _this3 = this;

      var options = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : {};
      // on successful JSON response, map data to this.EntityModel.import
      // then dispatch success, type, data (transformed data)
      var backoffFetch$$1 = this.backoffFetch,
          resource = this.resource;
      var _this$constructor2 = this.constructor,
          getRequest = _this$constructor2.getRequest,
          getTimestamp = _this$constructor2.getTimestamp;
      var include = options.include || [];
      var fields = options.fields;
      return function (dispatch) {
        //
        // Construct and endpoint if one was not supplied
        //
        var endpoint = options.endpoint || _this3.getEndpoint({
          include: include,
          fields: fields,
          id: uuid
        }); //
        // Generate the request object
        //


        var request$$1 = getRequest(endpoint, options); //
        // Dispatch API collection request action.
        //

        dispatch(request(resource, uuid));
        logger.log('network', 'Request', request$$1); //
        // Make the actual API call
        //

        function makeApiCall() {
          //
          // Fetch the data.
          //
          var fetchData = backoffFetch$$1(request$$1, responseHandler, errorHandler).then(function (resp) {
            //
            // Handle an OK response
            //
            if (resp.ok) {
              resp.json().then(function (json) {
                //
                // Ensure the response data is an Array
                //
                var output = {
                  data: json.data
                }; //
                // Log network response
                //

                logger.group('network', 'response');
                logger.log('network', 'Response: '.concat(getTimestamp(), ' ').concat(resource), json);
                logger.log('network', output.data);
                logger.groupEnd('network', 'response'); //
                // Process included resources.
                //

                if ('included' in json) {
                  processIncludes(dispatch)(json.included);
                } //
                // Dispatch Receive action
                //


                dispatch(receive(output, resource, uuid));
              });
            } //
            // Handle a NOT OK response
            //
            else {
                dispatch(failure(''.concat(resp.status, ': ').concat(resp.statusText || 'No status message provided'), resource, uuid));
              }

            return resp;
          }) //
          // Catch network error
          //
          .catch(handleNetworkError(dispatch, resource, uuid));
          return Promise.all([fetchData]) //
          // Return the fetched data.
          //
          .then(function (values) {
            return values[1];
          }).catch(function (err) {
            logger.log(err);
          });
        } //
        // Make the API call
        //


        return makeApiCall();
      };
    } // Fetch related translations.

  }, {
    key: 'fetchTranslations',
    value: function fetchTranslations() {
      var _this4 = this;

      var options = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : {};
      // on successful JSON response, map data to this.EntityModel.import
      // then dispatch success, type, data (transformed data)
      var backoffFetch$$1 = this.backoffFetch,
          fields = this.fields,
          include = this.include,
          resource = this.resource,
          priority = this.priority;
      var _this$constructor3 = this.constructor,
          getRequest = _this$constructor3.getRequest,
          getTimestamp = _this$constructor3.getTimestamp;

      var _fetchTranslations = this.fetchTranslations.bind(this);

      return function (dispatch, getState) {
        var state = getState(); // Exit if we're not logged in.

        if (!state.userData.auth.loggedIn) {
          logger.log('network', 'User is logged out: Aborting request.');
          return Promise.resolve('Aborted');
        }

        var limit = options.limit,
            offset = options.offset;

        var endpoint = options.endpoint || _this4.getEndpoint({
          fields: fields,
          include: include,
          langcode: options.langcode,
          filters: [].concat(options.filters, {
            path: 'langcode',
            value: options.langcode,
            operator: '='
          }),
          limit: limit,
          offset: offset
        });

        var request$$1 = getRequest(endpoint, options); // Dispatch generic api request action.

        dispatch(request(resource));
        logger.log('network', 'Request', request$$1);

        function makeApiCall() {
          return backoffFetch$$1(request$$1, responseHandler, errorHandler).then(function (resp) {
            if (resp.ok) {
              resp.json().then(function (json) {
                var output = {
                  data: [].concat(json.data).map(EntityModel.import)
                }; // @todo Handle transforming included resources.

                logger.group('network', 'response');
                logger.log('network', 'Priority: '.concat(priority));
                logger.log('network', 'Translation Response: '.concat(getTimestamp(), ' ').concat(options.langcode, ' ').concat(resource), json);
                logger.log('network', output.data);
                logger.groupEnd('network', 'response');
                dispatch(receiveTranslation(output, resource, options.langcode)); // Recursively fetch paginated items.

                if (json.links && json.links.next) {
                  dispatch(_fetchTranslations({
                    endpoint: json.links.next.href,
                    langcode: options.langcode
                  }));
                }
              });
            } else {
              dispatch(failure(''.concat(resp.status, ': ').concat(resp.statusText || 'No status message provided'), resource));
            }

            return resp;
          }) // Catch network error.
          .catch(handleNetworkError(dispatch, resource));
        }

        return makeApiCall();
      };
    } // Clear API Errors

  }, {
    key: 'clearErrors',
    value: function clearErrors$$1() {
      var resource = this.resource;
      logger.log('network', 'Running Clear errors on '.concat(resource, '.'));
      return function (dispatch) {
        dispatch(clearErrors(resource));
      };
    } // Clear API Errors

  }, {
    key: 'markDirty',
    value: function markDirty$$1() {
      var resource = this.resource;
      logger.log('network', 'Marking all '.concat(resource, ' items as dirty.'));
      return function (dispatch) {
        dispatch(markDirty(resource));
      };
    } // Purge local store

  }, {
    key: 'purge',
    value: function purge$$1() {
      var resource = this.resource;
      return function (dispatch) {
        dispatch(purge(resource));
      };
    } // Reset API store

  }, {
    key: 'reset',
    value: function reset$$1() {
      var resource = this.resource;
      return function (dispatch) {
        dispatch(reset(resource));
      };
    }
    /**
     * Syncs data using either POST or PATCH based on the saved status of the entity.
     * @param  {String} uuid   UUID of the entity to create remotely.
     * @return {Function}      Redux thunk.
     */

  }, {
    key: 'removeRelationship',
    value: function removeRelationship(relationship, uuid) {
      var backoffFetch$$1 = this.backoffFetch,
          bundle = this.bundle,
          model = this.model,
          resource = this.resource,
          type = this.type;
      var _this$constructor4 = this.constructor,
          getRequest = _this$constructor4.getRequest,
          getRelationshipEndpoint = _this$constructor4.getRelationshipEndpoint;
      return function (dispatch, getState) {
        var state = getState();
        var entity = state[resource].items[uuid]; // Abort if a request is already in progress.
        // or if this request previously errored.
        // @todo Determine a better way to handle errors. The current implementation
        // will prevent an infinite loop of error requests but will also prevent
        // reattempts in case of network errors.
        // if (entity.state.syncing) {

        if (entity.state.syncing || entity.state.error) {
          return Promise.resolve('Aborted');
        }

        var method = 'PATCH';
        var data = {
          data: null
        };
        var endpointParts = {
          type: type,
          bundle: bundle,
          relationship: relationship,
          uuid: uuid
        };
        var endpoint = getRelationshipEndpoint(endpointParts);
        var request$$1 = getRequest(endpoint, {
          method: method,
          body: JSON.stringify(data)
        });
        logger.log('network', method, request$$1); // Dispatch generic api request action.

        dispatch(request(resource, uuid));

        function makeApiCall() {
          return backoffFetch$$1(request$$1, responseHandler, errorHandler).then(function (resp) {
            return handleResponse(resp, request$$1, dispatch, resource, model, uuid);
          }) // Catch network error.
          .catch(handleNetworkError(dispatch, resource, uuid));
        }

        return makeApiCall();
      };
    }
    /**
     * Syncs data using either POST or PATCH based on the saved status of the entity.
     * @param  {String} uuid   UUID of the entity to create or update remotely.
     * @return {Function}      Redux thunk.
     */

  }, {
    key: 'sync',
    value: function sync(uuid, options) {
      var _this5 = this;

      var backoffFetch$$1 = this.backoffFetch,
          model = this.model,
          include = this.include,
          resource = this.resource;
      var getRequest = this.constructor.getRequest;
      var updateRelationshipsIfNeeded = this.updateRelationshipsIfNeeded.bind(this);
      return function (dispatch, getState) {
        var state = getState();
        var entity = state[resource].items[uuid]; // Abort if a request is already in progress.
        // or if this request previously errored.
        // @todo Determine a better way to handle errors. The current implementation
        // will prevent an infinite loop of error requests but will also prevent
        // reattempts in case of network errors.

        if (entity.state.syncing) {
          return Promise.reject(new Error('Entity already syncing.'));
        }

        if (entity.state.error) {
          return Promise.reject(new Error('Will not retry a request with an error state.'));
        } // Has this entity successfully saved to remotely?


        var saved = entity.state.saved; // Determine the HTTP method based on saved status.

        var method = saved ? 'PATCH' : 'POST'; // Format for local entity data for jsonapi

        var data = EntityModel.export(entity, state); //
        // Create API endpoint string
        //

        var endpointParts = {
          include: include
        }; // Add the uuid to the endpoint if this entity exists remotely.

        if (saved) {
          assign_1(endpointParts, {
            id: uuid
          });
        }

        var endpoint = options.endpoint || _this5.getEndpoint(endpointParts);

        var request$$1 = getRequest(endpoint, _objectSpread({}, options, {
          method: method,
          body: JSON.stringify(data)
        }));

        function makeApiCall() {
          return backoffFetch$$1(request$$1, responseHandler, errorHandler)
            .then(function (resp) {
              return handleResponse(resp, request$$1, dispatch, resource, model, uuid);
            })
            .then(function (action) {
              // Abort if this is a failure.
              if (action.type === FAILURE) {
                return;
              }
              // If this is an update operation, we need to update relationships as well.
              else if (saved) {
                return updateRelationshipsIfNeeded(dispatch, entity.data, action.resp.data);
              }
            })
            .catch(function (err) {
              logger.log('network', 'we give up', err);
              handleNetworkError(dispatch, resource, uuid)(err);
              return Promise.reject(err);
            });
        }

        logger.log('network', method, request$$1); // Dispatch generic api request action.

        dispatch(request(resource, uuid));
        return makeApiCall();
      };
    }
    /**
     * Compares local data with remote data to determine if we need to remove any entity references.
     * @param  {Function} dispatch Redux dispatch function.
     * @param  {Object}   localData   Entity data from the local redux store.
     * @param  {Object}   remoteData  Entity data from the remote server.
     */

  }, {
    key: 'updateRelationshipsIfNeeded',
    value: function updateRelationshipsIfNeeded(dispatch, localData, remoteData) {
      var _this6 = this;

      var relationships = this.model.getRelationshipAliases();
      var dirtyRelationships = filter_1(relationships, function (r) {
        return (// If a relationship exists remotely but not locally, it's dirty.
          !localData[r] && localData[r] !== remoteData[r]
        );
      });
      var removeRelationship = this.removeRelationship.bind(this);
      return Promise.all(dirtyRelationships.map(function (r) {
        return dispatch(removeRelationship(_this6.model.getPropertyFromAlias(r), localData.uuid));
      }));
    }
    /**
     * Normalizes the resolution and rejection of a fetch request.
     * @param  {Request} request The request object. https://developer.mozilla.org/en-US/docs/Web/API/Request
     * @param  {responseHandlerCallback} responseHandler - The callback that handles the response.
     * @param  {errorHandlerCallback} errorHandler - The callback that handles the error from a failed request.
     * @return {Promise}         A promise that will resolve if the fetch response is OK. It will reject otherwise.
     */

  }, {
    key: 'wrapFetch',
    value: function wrapFetch$$1(request$$1) {
      return function () {
        return new Promise(function (resolve, reject) {
          // Fetch the request.
          fetch(request$$1.clone()) // Handle a successful request.
          .then(responseHandler(request$$1, resolve, reject)).catch(errorHandler(request$$1, resolve, reject));
        });
      }
    }
    /**
     * Retries a failed request using Exponential Backoff.
     * @param  {Request} request The request object. https://developer.mozilla.org/en-US/docs/Web/API/Request
     * @param  {responseHandlerCallback} responseHandler - The callback that handles the response.
     * @param  {errorHandlerCallback} errorHandler - The callback that handles the error from a failed request.
     * @return {Promise}  A wrapped fetch request that will retry if it encounters a failure.
     */

  }, {
    key: 'backoffFetch',
    value: function backoffFetch$$1(request$$1) {
      return exponentialBackoff(this.wrapFetch(request$$1));
    }
  }], [{
    key: 'getEndpointOrigin',
    value: function getEndpointOrigin(options) {
      // Format the url string.
      return options ? new URL(url.format(options)).origin : '/';
    }
  }, {
    key: 'getEndpointFilters',
    value: function getEndpointFilters(filters) {
      return reduce_1(filters, function (query, value, key) {
        var output = assign_1({}, query);
        var type = value.type || 'condition';
        var multiOperators = ['IN', 'NOT IN', 'BETWEEN'];
        var useShorthand = 'operator' in value === false && 'condition' in value === false && 'memberOf' in value === false && 'type' in value === false; // Handle shorthand filters.

        if (useShorthand) {
          output['filter['.concat(value.path, '][value]')] = value.value;
          return output;
        } // Handle groups


        if (type === 'group') {
          // Default to AND if conjuction is not specified.
          output['filter['.concat(key, '][group][conjunction]')] = value.conjunction || 'AND';

          if ('memberOf' in value) {
            output['filter['.concat(key, '][group][memberOf]')] = value.memberOf;
          }

          return output;
        } // Handle default


        output['filter['.concat(key, '][condition][path]')] = value.path; // Handle multi-value operators.

        if (multiOperators.indexOf(value.operator) > -1) {
          output['filter['.concat(key, '][condition][value][]')] = value.value;
        } else {
          output['filter['.concat(key, '][condition][value]')] = value.value;
        }

        if ('operator' in value) {
          output['filter['.concat(key, '][condition][operator]')] = value.operator;
        }

        if ('memberOf' in value) {
          output['filter['.concat(key, '][condition][memberOf]')] = value.memberOf;
        }

        return output;
      }, {});
    }
    /**
     * Creates a query parameter object formatted for sorts
     * See https://www.drupal.org/docs/8/modules/json-api/collections-and-sorting
     * @param  {Object} sort
     *  @todo document
     * @return {Object}
     *  An object of query param key|value pairs
     */

  }, {
    key: 'getEndpointSort',
    value: function getEndpointSort(sort) {
      var output = reduce_1(sort, function (query, value) {
        var direction = value.direction === 'DESC' ? '-' : '';
        var sortParam = ''.concat(direction).concat(value.path);
        return query ? [].concat(query, sortParam).join(',') : sortParam;
      }, null);
      return output === null ? {} : {
        sort: output
      };
    }
    /**
     * Creates a query parameter object formatted for sparse fieldsets
     * See https://www.drupal.org/docs/8/modules/json-api/collections-filtering-and-sorting
     * See http://jsonapi.org/format/#fetching-sparse-fieldsets
     * @param  {Object} fields
     *  @todo document
     * @return {Object}
     *  An object of query param key|value pairs
     */

  }, {
    key: 'getEndpointFields',
    value: function getEndpointFields(fields) {
      if (fields === null) {
        return {};
      }

      return reduce_1(fields, function (query, value, key) {
        return assign_1(query, _defineProperty$2({}, 'fields['.concat(key, ']'), value.join(',')));
      }, {});
    }
    /**
     * Creates a query parameter object formatted for including related resources
     * See https://www.drupal.org/docs/8/modules/json-api/fetching-resources-get
     * See http://jsonapi.org/format/#fetching-includes
     * @param  {Object} include
     *  @todo document
     * @return {Object}
     *  An object of query param key|value pairs
     */

  }, {
    key: 'getEndpointInclude',
    value: function getEndpointInclude(include) {
      // Set includes if they exist.
      return Array.isArray(include) && include.length > 0 ? {
        include: include.join(',')
      } : {};
    }
    /**
     * Creates a query parameter object formatted for pagination limit
     * See https://www.drupal.org/docs/8/modules/json-api/pagination
     * See http://jsonapi.org/format/#fetching-pagination
     * @param  {number} limit
     *  @todo document
     * @return {Object}
     *  An object of query param key|value pairs
     */

  }, {
    key: 'getEndpointLimit',
    value: function getEndpointLimit(limit) {
      return limit ? {
        'page[limit]': limit
      } : {};
    }
    /**
     * Creates a query parameter object formatted for pagination offset
     * See https://www.drupal.org/docs/8/modules/json-api/pagination
     * See http://jsonapi.org/format/#fetching-pagination
     * @param  {number} offset
     *  @todo document
     * @return {Object}
     *  An object of query param key|value pairs
     */

  }, {
    key: 'getEndpointOffset',
    value: function getEndpointOffset(offset) {
      return offset ? {
        'page[offset]': offset
      } : {};
    }
  }, {
    key: 'getTimestamp',
    value: function getTimestamp() {
      var now = new Date();
      return Math.floor(now / 1000);
    }
    /**
     * Creates a resource object to make a JSON_API fetch request
     * @param  {string} endpoint    A fully formed url.
     * @param  {object} [options]   Additional request options.
     * @param  {string} [options.method = 'GET'] GET|POST|PATCH|DELETE
     * @param  {string} [options.token]   JWT token. If omited, the request will be unathenticated and will most likely fail.
     * @param  {string} [options.headers] Additional headers
     * @return {object}          A Request object https://developer.mozilla.org/en-US/docs/Web/API/Request
     */

  }, {
    key: 'getRequest',
    value: function getRequest(endpoint) {
      var options = arguments.length > 1 && arguments[1] !== undefined ? arguments[1] : {};
      // Assume we're talking JSON_API.
      var defaultHeaders = {
        Accept: 'application/vnd.api+json',
        'Content-Type': 'application/vnd.api+json'
      };
      var requestOptions = {
        method: options.method || 'GET',
        headers: assign_1({}, defaultHeaders, options.headers || {}),
        credentials: 'same-origin'
      }; // Add the body field if we have one.

      if (options.body) {
        assign_1(requestOptions, {
          body: options.body
        });
      } // Return the Request object.


      return new Request(endpoint, requestOptions);
    }
  }, {
    key: 'fetchTimestamp',
    value: function fetchTimestamp() {
      return Promise.resolve(Math.floor(new Date().getTime() / 1000));
    }
  }]);

  return ApiManager;
}());
/**
 * Initial state of an api data store.
 * @type {Object}
 */

let initialDataState = {
  items: {},
  validating: false,
  syncing: false,
  error: null,
  updated: null,
};
/**
 * Creates a new data item from an api call.
 * @param  {Object} data Data used to populate item.data.
 * @return {Object}      A new item with data and state properties populated.
 */

function itemImport(data) {
  let output = {
    data,
    state: {
      syncing: false,
      saved: true,
      error: null,
      dirty: false,
    },
  };
  return output;
}
/**
 * Creates a new data item from an existing item, overriding any specified data properties.
 * @param  {Object} item Existing item from the store
 * @param  {Object} data Data used to populate item.data.
 * @return {Object}      A new item with data and state properties populated.
 */


function itemEdit(item, data) {
  let output = assign_1({}, item);
  output.data = _objectSpread({}, item.data, data);
  output.state = _objectSpread({}, item.state, {
    error: null,
    dirty: true,
  });
  return output;
}

function editItems(items, data) {
  let output = assign_1({}, items); // Loop through each new data point.

  forEach_1(data, (d) => {
    output[d.uuid] = itemEdit(items[d.uuid], d);
  });
  return output;
}

function mergeProp(prop, x, y) {
  if (x[prop] || y[prop]) {
    return _objectSpread({}, x[prop], y[prop]);
  }
}
/**
 * Creates a new data item from an existing item, overriding data properties from an api response.
 * @param  {Object} item Existing item from the store
 * @param  {Object} data Data used to populate item.data.
 * @return {Object}      A new item with data and state properties populated.
 */


function itemUpdate(item, input) {
  let output = Object.assign({}, item);
  output.data = item.data || {};
  output.data.attributes = mergeProp('attributes', item.data, input);
  output.data.relationships = mergeProp('relationships', item.data, input);
  output.data.meta = mergeProp('meta', item.data, input);
  output.data.links = mergeProp('links', item.data, input);
  output.state = _objectSpread({}, item.state, {
    saved: true,
  });
  return output;
}
/**
 * Creates a new data item from an existing item, overriding data properties from an api response.
 * @param  {Object} item Existing item from the store
 * @param  {Object} data Data used to populate item.data.
 * @return {Object}      A new item with data and state properties populated.
 */


function itemUpdateTimestamps(item, data) {
  let output = assign_1({}, item);
  output.data = item.data;
  let limitFieldsTo = ['created', 'changed', 'id'];
  limitFieldsTo.forEach((field) => {
    if (data[field]) {
      output.data[field] = data[field];
    }
  });
  output.state = _objectSpread({}, item.state, {
    saved: true,
  });
  return output;
}
/**
 * Prepares an item from an api call to be merged into the items collection.
 * @param  {Object} items Collection of data items.
 * @param  {Object} data    The new data to be merged from an api response.
 * @param  {String} mergeStrategy  The merge strategy for handling incoming data
 * @return {Object}         A new item with data and state properties populated.
 */


function mergeItem(items, data, mergeStrategy) {
  switch (mergeStrategy) {
    case 'mergeNew':
      // Only add new items.
      return data.id in items ? items[data.id] : itemImport(data);

    default:
      // Update existing items.
      return data.id in items ? itemUpdate(items[data.id], data) : itemImport(data);
  }
}
/**
 * Merges items from an api response into an existing collection.
 * @param  {Object} items Collection of existing items.
 * @param  {Array}  data  An array of items from an api response.
 * @param  {String} mergeStrategy  The merge strategy for handling incoming data
 * @return {Object}       A new collection with items from the api response merged into the existing collection.
 */


function mergeItems(items, data, mergeStrategy) {
  let output = assign_1({}, items); // Loop through each new data point.

  forEach_1(data, (d) => {
    output[d.id] = mergeItem(items, d, mergeStrategy);
  });
  return output;
}
/**
 * Resets the error state of all items to null.
 *   When setting the dirty state we check for an error status as early < 1.1.4.1 versions
 *    of the app would not reset the dirty value to true after a failed response.
 * @param  {Object} items Collection of existing items.
 * @return {Object}       A new collection with items with errors set to null.
 */


function clearItemsErrors(items) {
  return mapValues_1(items, (item) => {
    return _objectSpread({}, item, {
      state: _objectSpread({}, item.state, {
        dirty: item.state.dirty || item.state.error !== null || item.state.syncing || false,
        syncing: false,
        error: null
      })
    });
  });
}
/**
 * Sets the state of all items to dirty.
 * @param  {Object} items Collection of existing items.
 * @return {Object}       A new collection with items with errors set to null.
 */


function markItemsDirty(items) {
  return mapValues_1(items, (item) => {
    return _objectSpread({}, item, {
      state: _objectSpread({}, item.state, {
        dirty: true
      })
    });
  });
}
/**
 * Prepares an item from an api call to be merged into the items collection.
 * @param  {Object} items Collection of data items.
 * @param  {Object} data    The new data to be merged from an api response.
 * @param  {String} mergeStrategy  The merge strategy for handling incoming data
 * @param  {String} lancode  The 2 letter ISO_639-1 language code.
 * @return {Object}         A new item with data and state properties populated.
 */


function mergeTranslation(items, data, mergeStrategy, langcode) {
  let output = assign_1({}, items[data.uuid]);
  let translations = 'translations' in output.data ? output.data.translations : {};
  output.data.translations = assign_1(translations, _defineProperty$2({}, langcode, data));
  return output;
}
/**
 * Merges items from an api response into an existing collection.
 * @param  {Object} items Collection of existing items.
 * @param  {Array}  data  An array of items from an api response.
 * @param  {String} mergeStrategy  The merge strategy for handling incoming data
 * @param  {String} lancode  The 2 letter ISO_639-1 language code.
 * @return {Object}       A new collection with items from the api response merged into the existing collection.
 */


function mergeTranslations(items, data, mergeStrategy, langcode) {
  let output = assign_1({}, items); // Loop through each new data point.

  forEach_1(data, (d) => {
    if (output[d.uuid]) {
      output[d.uuid] = mergeTranslation(items, d, mergeStrategy, langcode);
    }
  });
  return output;
}
/**
 * Generic API reducer for dealing with single items.
 * @param  {Object} state  Current state of the Redux store
 * @param  {Object} action Flux standard action
 * @return {Object}        The altered state of the store
 */


function dataReducer() {
  let state = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : initialDataState;
  let action = arguments.length > 1 ? arguments[1] : undefined;
  let mergeStrategy = arguments.length > 2 ? arguments[2] : undefined;
  // Grab common variables from the action payload.
  let id = action.id,
    data = action.data;
  let item = {}; // Exit if we the item doesn't already exist and we're trying to do something other than updating.

  if (id in state === false && [ADD, RECEIVE].indexOf(action.type) < 0) {
    return state;
  } // Create a copy of the item.


  item = assign_1({}, state[id]);

  switch (action.type) {
    case CLEAR_ERRORS:
      item.state = _objectSpread({}, item.state, {
        dirty: item.state.dirty || item.state.error !== null || item.state.syncing || false,
        syncing: false,
        error: null,
      });
      break;

    case SET_SAVED:
      item.state = _objectSpread({}, item.state, {
        dirty: true,
        error: null,
        saved: action.value,
      });
      break;

    case MARK_DIRTY:
      item.state = _objectSpread({}, item.state, {
        dirty: true,
      });
      break;

    case REQUEST:
      item.state = _objectSpread({}, item.state, {
        syncing: true,
        dirty: false,
      });
      break;

    case RECEIVE:
      if (id in state === false) {
        item.data = action.resp.data;
        item.state = {
          dirty: false,
          saved: true,
          syncing: false,
          error: null,
        };
      }
      else {
        // If the item is now dirty, let's not update it again as we will
        //   lose changes and need to sync again anyway.
        if (action.resp.data && !item.state.dirty) {
          item = mergeStrategy === 'mergeNew' ? itemUpdateTimestamps(item, action.resp.data) : itemUpdate(item, action.resp.data);
        }

        item.state = _objectSpread({}, item.state, {
          saved: true,
          syncing: false,
          error: null,
        });
      }

      break;

    case FAILURE:
      item.state = _objectSpread({}, item.state, {
        syncing: false,
        error: action.error,
        dirty: true,
      });
      break;

    case ADD:
      item.data = data;
      item.state = {
        saved: false,
        syncing: false,
        error: null,
        dirty: true,
      };
      break;

    case EDIT:
      item = itemEdit(item, data);
      break;

    default:
      break;
  }

  return assign_1({}, state, _defineProperty$2({}, id, item));
}
/**
 * Creates an api Redux reducer for a specific resource type
 * @param  {String} resource JSON_API resource type.
 * @param  {String} mergeStrategy  The merge strategy for handling incoming data
 * @return {Function}        Reducer function for handling API data
 */

function apiReducer(resource, mergeStrategy) {
  return function () {
    let state = arguments.length > 0 && arguments[0] !== undefined ? arguments[0] : initialDataState;
    let action = arguments.length > 1 ? arguments[1] : undefined;

    // Only respond to the actions we care about.
    if ([CLEAR_ERRORS, SET_SAVED, SET_VALIDATING, REQUEST, RECEIVE, RECEIVE_TRANSLATION, FAILURE, MARK_DIRTY, PURGE, RESET, SET_TIMESTAMP, ADD, EDIT].indexOf(action.type) === -1) {
      return state;
    } // Return State if this is not the resource we care about.


    if (action.resource !== resource) {
      return state;
    } //
    // Handle full collection actions.
    //


    if (!action.id) {
      switch (action.type) {
        case CLEAR_ERRORS:
          return _objectSpread({}, state, {
            items: clearItemsErrors(state.items),
            syncing: false,
            error: null,
          });

        case MARK_DIRTY:
          return _objectSpread({}, state, {
            items: markItemsDirty(state.items),
          });

        case REQUEST:
          return _objectSpread({}, state, {
            syncing: true,
          });

        case SET_TIMESTAMP:
          return _objectSpread({}, state, {
            updated: action.timestamp,
          });

        case RECEIVE:
          return _objectSpread({}, state, {
            items: mergeItems(state.items, action.resp.data, mergeStrategy),
            syncing: false,
            error: null,
          });

        case RECEIVE_TRANSLATION:
          return _objectSpread({}, state, {
            // @todo this will override all local data with the response.
            // Fine for read-only resources ie. Testlet, Testlet Items
            // Need a merge strategy for other Students, Classes, Assessments etc.
            items: mergeTranslations(state.items, action.resp.data, mergeStrategy, action.langcode),
            syncing: false,
            error: null,
          });

        case PURGE:
          // Removes all locally stored items!
          return _objectSpread({}, state, {
            items: {},
            syncing: false,
            error: null,
            updated: null,
          });

        case FAILURE:
          // Log failure remotely.
          return _objectSpread({}, state, {
            isFetching: false,
            error: action.error,
          });

        case RESET:
          return _objectSpread({}, state, {
            syncing: false,
            error: null,
            updated: null,
          });

        case SET_VALIDATING:
          return _objectSpread({}, state, {
            validating: action.value,
          });

        case EDIT:
          return _objectSpread({}, state, {
            items: editItems(state.items, action.data),
          });

        default:
          return state;
      }
    } //
    // Handle single entity requests
    //


    return _objectSpread({}, state, {
      items: dataReducer(state.items, action, mergeStrategy),
    });
  };
}

let schema = {
  'evaluation_criteria--evaluation_criteria': {
    drupal_internal__id: { type: 'integer' }, uuid: { type: 'string' }, text: { type: 'string' }, evaluation: { type: 'integer' }, status: { type: 'boolean' }, created: { type: 'number' }, changed: { type: 'number' }, author: { type: 'relationship', model: 'user--user', multiple: false }
  },
  'file--file': {
    drupal_internal__fid: { type: 'integer' }, uuid: { type: 'string' }, filename: { type: 'string' }, uri: { type: 'uri' }, filemime: { type: 'string' }, filesize: { type: 'integer' }, status: { type: 'boolean' }, created: { type: 'number' }, changed: { type: 'number' }, url: { type: 'string' }, uid: { type: 'relationship', model: 'user--user', multiple: false }
  },
  'flagging--saved_event': {
    drupal_internal__id: { type: 'integer' }, uuid: { type: 'string' }, entity_type: { type: 'string' }, entity_id: { type: 'integer' }, global: { type: 'boolean' }, session_id: { type: 'integer' }, created: { type: 'number' }, flagged_entity: { type: 'relationship', model: 'node--event', multiple: false }, uid: { type: 'relationship', model: 'user--user', multiple: false }
  },
  'event_registration--event_registration': {
    title: { type: 'string' },
    drupal_internal__id: { type: 'integer' },
    uuid: { type: 'string' },
    created: { type: 'number' },
    changed: { type: 'number' },
    status: { type: 'string' },
    author: { type: 'relationship', model: 'user--user', multiple: false },
    field_event: { type: 'relationship', model: 'node--event', multiple: false },
    field_registrants: { type: 'relationship', model: 'taxonomy_term--population_segment', multiple: true },
    field_user: { type: 'relationship', model: 'user--user', multiple: false }
  },
  'event_attendance--event_attendance': {
    title: { type: 'string' },
    drupal_internal__id: { type: 'integer' }, uuid: { type: 'string' }, created: { type: 'number' }, changed: { type: 'number' }, status: { type: 'string' }, author: { type: 'relationship', model: 'user--user', multiple: false }, field_event: { type: 'relationship', model: 'node--event', multiple: false }, field_attendees: { type: 'relationship', model: 'taxonomy_term--population_segment', multiple: true }, field_user: { type: 'relationship', model: 'user--user', multiple: false }
  },
  'event_recurrence--event_recurrence': {
    drupal_internal__id: { type: 'integer' }, uuid: { type: 'string' }, created: { type: 'number' }, changed: { type: 'number' }, author: { type: 'relationship', model: 'user--user', multiple: false }
  },
  'media--file': {
    drupal_internal__mid: { type: 'integer' }, uuid: { type: 'string' }, status: { type: 'boolean' }, name: { type: 'string' }, created: { type: 'number' }, changed: { type: 'number' }, path: { type: 'object' }, thumbnail: { type: 'relationship', model: 'file--file', multiple: false }, uid: { type: 'relationship', model: 'user--user', multiple: false }, field_media_file: { type: 'relationship', model: 'file--file', multiple: false }
  },
  'media--image': {
    drupal_internal__mid: { type: 'integer' }, uuid: { type: 'string' }, status: { type: 'boolean' }, name: { type: 'string' }, created: { type: 'number' }, changed: { type: 'number' }, path: { type: 'object' }, field_media_caption: { type: 'object' }, field_media_credit: { type: 'object' }, thumbnail: { type: 'relationship', model: 'file--file', multiple: false }, uid: { type: 'relationship', model: 'user--user', multiple: false }, field_media_image: { type: 'relationship', model: 'file--file', multiple: false }
  },
  'media--slideshow': {
    drupal_internal__mid: { type: 'integer' }, uuid: { type: 'string' }, status: { type: 'boolean' }, name: { type: 'string' }, created: { type: 'number' }, changed: { type: 'number' }, path: { type: 'object' }, thumbnail: { type: 'relationship', model: 'file--file', multiple: false }, uid: { type: 'relationship', model: 'user--user', multiple: false }, field_media_slideshow: { type: 'relationship', model: 'media--image', multiple: true }
  },
  'media--web_video': {
    drupal_internal__mid: { type: 'integer' }, uuid: { type: 'string' }, status: { type: 'boolean' }, name: { type: 'string' }, created: { type: 'number' }, changed: { type: 'number' }, path: { type: 'object' }, field_media_caption: { type: 'object' }, field_media_video_embed_field: { type: 'string' }, thumbnail: { type: 'relationship', model: 'file--file', multiple: false }, uid: { type: 'relationship', model: 'user--user', multiple: false }
  },
  'node--equipment': {
    drupal_internal__nid: { type: 'integer' },
    uuid: { type: 'string' }, status: { type: 'boolean' }, title: { type: 'string' }, created: { type: 'number' }, changed: { type: 'number' }, promote: { type: 'boolean' }, sticky: { type: 'boolean' }, path: { type: 'object' }, field_duration_min: { type: 'string' }, field_text_content: { type: 'object' }, type: { type: 'relationship', model: 'node_type--node_type', multiple: false }, uid: { type: 'relationship', model: 'user--user', multiple: false }, field_equipment_type: { type: 'relationship', model: 'taxonomy_term--equipment_type', multiple: false }, image_primary: { type: 'relationship', model: 'media--image', multiple: false }
  },
  'node--event': {
    drupal_internal__nid: { type: 'integer' },
    uuid: { type: 'string' },
    status: { type: 'boolean' },
    title: { type: 'string' },
    created: { type: 'number' },
    changed: { type: 'number' },
    promote: { type: 'boolean' },
    sticky: { type: 'boolean' },
    path: { type: 'object' },
    field_capacity_max: { type: 'integer' },
    field_date_time: { type: 'object' },
    field_event_is_template: { type: 'boolean' },
    field_event_register_period: { type: 'object' },
    field_event_user_reg_max: { type: 'integer' },
    field_featured: { type: 'boolean' },
    field_has_waitlist: { type: 'boolean' },
    field_must_register: { type: 'boolean' },
    field_text_content: { type: 'object' },
    field_text_intro: { type: 'object' },
    field_text_teaser: { type: 'string' },
    field_waitlist_max: { type: 'integer' },
    field_evanced_id: { type: 'string' }, registration: { type: 'object' }, type: { type: 'relationship', model: 'node_type--node_type', multiple: false }, uid: { type: 'relationship', model: 'user--user', multiple: false }, field_event_audience: { type: 'relationship', model: 'taxonomy_term--audience', multiple: true }, field_event_audience_primary: { type: 'relationship', model: 'taxonomy_term--audience', multiple: false }, field_event_recurrence: { type: 'relationship', model: 'event_recurrence--event_recurrence', multiple: false }, field_event_series: { type: 'relationship', model: 'node--event_series', multiple: false }, field_event_tags: { type: 'relationship', model: 'taxonomy_term--tag', multiple: true }, field_event_type: { type: 'relationship', model: 'taxonomy_term--event_type', multiple: true }, field_event_type_primary: { type: 'relationship', model: 'taxonomy_term--event_type', multiple: false }, image_primary: { type: 'relationship', model: 'media--image', multiple: false }, field_location: { type: 'relationship', model: 'node--location', multiple: false }, field_room: { type: 'relationship', model: 'node--room', multiple: false }, field_event_designation: { type: 'string' }
  },
  'node--event_series': {
    drupal_internal__nid: { type: 'integer' }, uuid: { type: 'string' }, status: { type: 'boolean' }, title: { type: 'string' }, created: { type: 'number' }, changed: { type: 'number' }, promote: { type: 'boolean' }, sticky: { type: 'boolean' }, path: { type: 'object' }, type: { type: 'relationship', model: 'node_type--node_type', multiple: false }, uid: { type: 'relationship', model: 'user--user', multiple: false }
  },
  'node--location': {
    drupal_internal__nid: { type: 'integer' }, uuid: { type: 'string' }, status: { type: 'boolean' }, title: { type: 'string' }, field_location_abbreviation: { type: 'string' }, created: { type: 'number' }, changed: { type: 'number' }, promote: { type: 'boolean' }, sticky: { type: 'boolean' }, path: { type: 'object' }, field_address: { type: 'object' }, field_affiliated: { type: 'boolean' }, field_contact_number: { type: 'string' }, field_features: { type: 'string' }, field_location_hours: { type: 'array' }, field_map_link: { type: 'object' }, field_text_content: { type: 'object' }, field_text_intro: { type: 'object' }, type: { type: 'relationship', model: 'node_type--node_type', multiple: false }, uid: { type: 'relationship', model: 'user--user', multiple: false }, image_primary: { type: 'relationship', model: 'media--image', multiple: false }
  },
  'node--room': {
    drupal_internal__nid: { type: 'integer' },
    uuid: { type: 'string' },
    status: { type: 'boolean' },
    title: { type: 'string' },
    created: { type: 'number' },
    changed: { type: 'number' },
    room_thumbnail: { type: 'string' },
    promote: { type: 'boolean' },
    sticky: { type: 'boolean' },
    path: { type: 'object' },
    field_capacity_max: { type: 'integer' },
    field_capacity_min: { type: 'integer' },
    field_reservable_online: { type: 'boolean' },
    field_room_fees: { type: 'object' },
    field_room_standard_equipment: { type: 'array' }, field_reservation_phone_number: { type: 'string' }, field_staff_use_only: { type: 'boolean' }, field_text_content: { type: 'object' }, field_text_intro: { type: 'object' }, field_text_teaser: { type: 'string' }, type: { type: 'relationship', model: 'node_type--node_type', multiple: false }, uid: { type: 'relationship', model: 'user--user', multiple: false }, image_primary: { type: 'relationship', model: 'media--image', multiple: false }, field_location: { type: 'relationship', model: 'node--location', multiple: false }, field_room_type: { type: 'relationship', model: 'taxonomy_term--room_type', multiple: false }
  },
  'room_reservation--room_reservation': {
    title: { type: 'string' },
    drupal_internal__id: { type: 'integer' },
    notes: { type: 'string' },
    uuid: { type: 'string' },
    status: { type: 'boolean' }, created: { type: 'number' }, changed: { type: 'number' }, location: { type: 'string' }, field_attendee_count: { type: 'integer' }, field_dates: { type: 'object' }, field_group_name: { type: 'string' }, field_meeting_purpose_details: { type: 'string' }, field_refreshments: { type: 'boolean' }, field_refreshments_description: { type: 'object' }, field_publicize: { type: 'boolean' }, field_status: { type: 'string' }, author: { type: 'relationship', model: 'user--user', multiple: false }, image: { type: 'relationship', model: 'media--image', multiple: false }, field_room: { type: 'relationship', model: 'node--room', multiple: false }, field_meeting_purpose: { type: 'relationship', model: 'taxonomy_term--meeting_purpose', multiple: false }, field_event: { type: 'relationship', model: 'node--event', multiple: false }, field_user: { type: 'relationship', model: 'user--user', multiple: false }
  },
  'taxonomy_term--audience': {
    drupal_internal__tid: { type: 'integer' }, uuid: { type: 'string' }, name: { type: 'string' }, description: { type: 'object' }, weight: { type: 'integer' }, changed: { type: 'number' }, path: { type: 'object' }, parent: { type: 'relationship', model: 'taxonomy_term--audience', multiple: true }
  },
  'taxonomy_term--equipment_type': {
    drupal_internal__tid: { type: 'integer' }, uuid: { type: 'string' }, name: { type: 'string' }, description: { type: 'object' }, weight: { type: 'integer' }, changed: { type: 'number' }, path: { type: 'object' }, parent: { type: 'relationship', model: 'taxonomy_term--equipment_type', multiple: true }
  },
  'taxonomy_term--evaluation_criteria': {
    drupal_internal__tid: { type: 'integer' }, uuid: { type: 'string' }, name: { type: 'string' }, description: { type: 'object' }, weight: { type: 'integer' }, changed: { type: 'number' }, path: { type: 'object' }, field_evaluation: { type: 'integer' }, parent: { type: 'relationship', model: 'taxonomy_term--evaluation_criteria', multiple: true }
  },
  'taxonomy_term--event_type': {
    drupal_internal__tid: { type: 'integer' }, uuid: { type: 'string' }, name: { type: 'string' }, description: { type: 'object' }, weight: { type: 'integer' }, changed: { type: 'number' }, path: { type: 'object' }, field_examples: { type: 'string' }, parent: { type: 'relationship', model: 'taxonomy_term--event_type', multiple: true }
  },
  'taxonomy_term--lc_subject': {
    drupal_internal__tid: { type: 'integer' }, uuid: { type: 'string' }, name: { type: 'string' }, description: { type: 'object' }, weight: { type: 'integer' }, changed: { type: 'number' }, path: { type: 'object' }, parent: { type: 'relationship', model: 'taxonomy_term--lc_subject', multiple: true }
  },
  'taxonomy_term--meeting_purpose': {
    drupal_internal__tid: { type: 'integer' }, uuid: { type: 'string' }, name: { type: 'string' }, description: { type: 'object' }, field_requires_explanation: { type: 'boolean' }, weight: { type: 'integer' }, changed: { type: 'number' }, path: { type: 'object' }, parent: { type: 'relationship', model: 'taxonomy_term--meeting_purpose', multiple: true }
  },
  'taxonomy_term--population_segment': {
    drupal_internal__tid: { type: 'integer' }, uuid: { type: 'string' }, name: { type: 'string' }, description: { type: 'object' }, weight: { type: 'integer' }, changed: { type: 'number' }, path: { type: 'object' }, parent: { type: 'relationship', model: 'taxonomy_term--population_segment', multiple: true }
  },
  'taxonomy_term--room_type': {
    drupal_internal__tid: { type: 'integer' }, uuid: { type: 'string' }, name: { type: 'string' }, description: { type: 'object' }, weight: { type: 'integer' }, changed: { type: 'number' }, path: { type: 'object' }, parent: { type: 'relationship', model: 'taxonomy_term--room_type', multiple: true }
  },
  'taxonomy_term--tag': {
    drupal_internal__tid: { type: 'integer' }, uuid: { type: 'string' }, name: { type: 'string' }, description: { type: 'object' }, weight: { type: 'integer' }, changed: { type: 'number' }, path: { type: 'object' }, parent: { type: 'relationship', model: 'taxonomy_term--tag', multiple: true }
  },
  'user--user': {
    drupal_internal__uid: { type: 'integer' }, uuid: { type: 'string' }, preferred_langcode: { type: 'object' }, preferred_admin_langcode: { type: 'object' }, name: { type: 'string' }, pass: { type: 'object' }, mail: { type: 'string' }, timezone: { type: 'string' }, status: { type: 'boolean' }, created: { type: 'number' }, changed: { type: 'number' }, access: { type: 'number' }, login: { type: 'number' }, init: { type: 'string' }, path: { type: 'object' }, roles: { type: 'relationship', model: 'user_role--user_role', multiple: true }
  },
};

let resources = Object.keys(schema).map((resource) => {
  return {
    resource: resource,
    strategy: 'overrideAll'
  };
});
let reducer = mapValues_1(keyBy_1(resources, (r) => {
  return r.resource;
}), (r) => {
  return apiReducer(r.resource, r.strategy);
});

let models = mapValues_1(schema, (model, resource) => {
  return new EntityModel(resource.split('--')[0], resource.split('--')[1], model);
});

let api = mapValues_1(models, (model) => {
  return new ApiManager({
    model: model,
    priority: 2
  });
});

let index = {
  actions,
  constants,
  reducer,
  api,
  models,
};

export default index;
// # sourceMappingURL=intercept-client.js.map
