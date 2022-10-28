'use strict';

exports.__esModule = true;
exports.default = urlQueryEncoder;

var _serialize = require('./serialize');

/**
 * Encodes a query based on the config. Similarly to `encode`, it does not respect the `defaultValue`
 * field, so any missing values must be specified explicitly.
 *
 * @param {Object} query The query object (typically from props.location.query)
 *
 * @return {Object} the encoded values `{ key: encodedValue, ... }`
 */
function urlQueryEncoder(config) {
  return function encodeQuery(query) {
    // encode the query
    var encodedQuery = Object.keys(config).reduce(function (encoded, key) {
      var keyConfig = config[key];
      // read from the URL key if provided, otherwise use the key
      var _keyConfig$queryParam = keyConfig.queryParam;
      var queryParam = _keyConfig$queryParam === undefined ? key : _keyConfig$queryParam;

      var decodedValue = query[key];

      var encodedValue = (0, _serialize.encode)(keyConfig.type, decodedValue);

      encoded[queryParam] = encodedValue;
      return encoded;
    }, {});

    return encodedQuery;
  };
}