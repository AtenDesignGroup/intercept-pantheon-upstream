'use strict';

var _configureUrlQuery = require('../configureUrlQuery');

var _configureUrlQuery2 = _interopRequireDefault(_configureUrlQuery);

var _urlQueryConfig = require('../urlQueryConfig');

var _urlQueryConfig2 = _interopRequireDefault(_urlQueryConfig);

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

it('updates the singleton query object', function () {
  (0, _configureUrlQuery2.default)({ test: 99 });
  expect(_urlQueryConfig2.default.test).toBe(99);

  (0, _configureUrlQuery2.default)({ history: 123 });
  expect(_urlQueryConfig2.default.history).toBe(123);
  expect(_urlQueryConfig2.default.test).toBe(99);
});

it('does not break on undefined options', function () {
  (0, _configureUrlQuery2.default)();
  expect(Object.keys(_urlQueryConfig2.default).length).toBeGreaterThan(0);
});

it('configures entrySeparator and keyValSeparator global values', function () {
  expect(_urlQueryConfig2.default.entrySeparator).toBe('_');
  expect(_urlQueryConfig2.default.keyValSeparator).toBe('-');

  (0, _configureUrlQuery2.default)({ entrySeparator: '__' });
  expect(_urlQueryConfig2.default.entrySeparator).toBe('__');
  expect(_urlQueryConfig2.default.keyValSeparator).toBe('-');

  (0, _configureUrlQuery2.default)({ keyValSeparator: '--' });
  expect(_urlQueryConfig2.default.entrySeparator).toBe('__');
  expect(_urlQueryConfig2.default.keyValSeparator).toBe('--');

  // Reset so it does not effect other tests
  (0, _configureUrlQuery2.default)({ entrySeparator: '_', keyValSeparator: '-' });
});