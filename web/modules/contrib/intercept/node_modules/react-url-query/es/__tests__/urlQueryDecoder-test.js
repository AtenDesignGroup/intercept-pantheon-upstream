'use strict';

var _urlQueryDecoder = require('../urlQueryDecoder');

var _urlQueryDecoder2 = _interopRequireDefault(_urlQueryDecoder);

var _UrlQueryParamTypes = require('../UrlQueryParamTypes');

var _UrlQueryParamTypes2 = _interopRequireDefault(_UrlQueryParamTypes);

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

it('works with basic configuration', function () {
  var urlPropsQueryConfig = {
    foo: { type: _UrlQueryParamTypes2.default.number },
    bar: { type: _UrlQueryParamTypes2.default.string }
  };

  var decode = (0, _urlQueryDecoder2.default)(urlPropsQueryConfig);
  var decoded = decode({ foo: '137', bar: 'str' });

  expect(decoded).toEqual({ foo: 137, bar: 'str' });
});

it('works with different named query param', function () {
  var urlPropsQueryConfig = {
    foo: { type: _UrlQueryParamTypes2.default.number, queryParam: 'fooInUrl' },
    bar: { type: _UrlQueryParamTypes2.default.string }
  };

  var decode = (0, _urlQueryDecoder2.default)(urlPropsQueryConfig);
  var decoded = decode({ fooInUrl: '137', bar: 'str' });

  expect(decoded).toEqual({ foo: 137, bar: 'str' });
});

it('validate filters out invalid params', function () {
  var urlPropsQueryConfig = {
    foo: { type: _UrlQueryParamTypes2.default.number, validate: function validate(foo) {
        return foo > 100;
      } },
    bar: { type: _UrlQueryParamTypes2.default.string }
  };

  var decode = (0, _urlQueryDecoder2.default)(urlPropsQueryConfig);
  expect(decode({ foo: '137', bar: 'str' })).toEqual({ foo: 137, bar: 'str' });
  expect(decode({ foo: '99', bar: 'str' })).toEqual({ bar: 'str' });
});

it('uses cached decoded values if encoded values have not changed', function () {
  var urlPropsQueryConfig = {
    foo: { type: _UrlQueryParamTypes2.default.array },
    bar: { type: _UrlQueryParamTypes2.default.string }
  };

  var decode = (0, _urlQueryDecoder2.default)(urlPropsQueryConfig);
  var decoded = decode({ foo: '137_94', bar: 'str' });
  expect(decode({ foo: '137_94', bar: 'bar' }).foo).toBe(decoded.foo);
  expect(decode({ foo: '137_95', bar: 'bar' }).foo).not.toBe(decoded.foo);
});

it('respects the `defaultValue` configuration', function () {
  var urlPropsQueryConfig = {
    foo: { type: _UrlQueryParamTypes2.default.number, defaultValue: 42 },
    bar: { type: _UrlQueryParamTypes2.default.string }
  };

  var decode = (0, _urlQueryDecoder2.default)(urlPropsQueryConfig);
  var decoded = decode({ bar: 'str' });

  expect(decoded).toEqual({ foo: 42, bar: 'str' });
});

it('respects the `defaultValue` configuration for the properties with named `queryParam`', function () {
  var urlPropsQueryConfig = {
    foo: {
      type: _UrlQueryParamTypes2.default.number,
      queryParam: 'fooInUrl',
      defaultValue: 42
    },
    bar: { type: _UrlQueryParamTypes2.default.string }
  };

  var decode = (0, _urlQueryDecoder2.default)(urlPropsQueryConfig);
  var decoded = decode({ bar: 'str' });

  expect(decoded).toEqual({ foo: 42, bar: 'str' });
});

it('respects custom decoders', function () {
  var urlPropsQueryConfig = {
    foo: { type: function type(value) {
        return Number(value) + 1;
      } },
    bar: { type: _UrlQueryParamTypes2.default.string }
  };

  var decode = (0, _urlQueryDecoder2.default)(urlPropsQueryConfig);
  var decoded = decode({ foo: '137', bar: 'str' });

  expect(decoded).toEqual({ foo: 138, bar: 'str' });
});

it('respects custom decoders with named `queryParam`', function () {
  var urlPropsQueryConfig = {
    foo: { type: function type(value) {
        return Number(value) + 1;
      }, queryParam: 'fooInUrl' },
    bar: { type: _UrlQueryParamTypes2.default.string }
  };

  var decode = (0, _urlQueryDecoder2.default)(urlPropsQueryConfig);
  var decoded = decode({ fooInUrl: '137', bar: 'str' });

  expect(decoded).toEqual({ foo: 138, bar: 'str' });
});

it('respects the `defaultValue` configuration with a custom decoder', function () {
  var urlPropsQueryConfig = {
    foo: {
      type: function type(value, defaultValue) {
        return value === '137' ? defaultValue : Number(value);
      },
      defaultValue: 42
    },
    bar: { type: _UrlQueryParamTypes2.default.string }
  };

  var decode = (0, _urlQueryDecoder2.default)(urlPropsQueryConfig);
  var decoded = decode({ foo: '137', bar: 'str' });

  expect(decoded).toEqual({ foo: 42, bar: 'str' });
});

it('respects the `defaultValue` configuration with a custom decoder and a named `queryParam`', function () {
  var urlPropsQueryConfig = {
    foo: {
      type: function type(value, defaultValue) {
        return value === '137' ? defaultValue : Number(value);
      },
      defaultValue: 42,
      queryParam: 'fooInUrl'
    },
    bar: { type: _UrlQueryParamTypes2.default.string }
  };

  var decode = (0, _urlQueryDecoder2.default)(urlPropsQueryConfig);
  var decoded = decode({ fooInUrl: '137', bar: 'str' });

  expect(decoded).toEqual({ foo: 42, bar: 'str' });
});