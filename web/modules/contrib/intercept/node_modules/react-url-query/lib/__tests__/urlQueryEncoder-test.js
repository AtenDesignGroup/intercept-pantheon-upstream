'use strict';

var _urlQueryEncoder = require('../urlQueryEncoder');

var _urlQueryEncoder2 = _interopRequireDefault(_urlQueryEncoder);

var _UrlQueryParamTypes = require('../UrlQueryParamTypes');

var _UrlQueryParamTypes2 = _interopRequireDefault(_UrlQueryParamTypes);

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

it('works with basic configuration', function () {
  var urlPropsQueryConfig = {
    foo: { type: _UrlQueryParamTypes2.default.number },
    bar: { type: _UrlQueryParamTypes2.default.string }
  };

  var encode = (0, _urlQueryEncoder2.default)(urlPropsQueryConfig);
  var encoded = encode({ foo: 137, bar: 'str' });

  expect(encoded).toEqual({ foo: '137', bar: 'str' });
});

it('works with different named query param', function () {
  var urlPropsQueryConfig = {
    foo: { type: _UrlQueryParamTypes2.default.number, queryParam: 'fooInUrl' },
    bar: { type: _UrlQueryParamTypes2.default.string }
  };

  var encode = (0, _urlQueryEncoder2.default)(urlPropsQueryConfig);
  var encoded = encode({ foo: 137, bar: 'str' });

  expect(encoded).toEqual({ fooInUrl: '137', bar: 'str' });
});

it('works when the object to encode has got missing properties', function () {
  var urlPropsQueryConfig = {
    foo: { type: _UrlQueryParamTypes2.default.number, queryParam: 'fooInUrl' },
    bar: { type: _UrlQueryParamTypes2.default.string }
  };

  var encode = (0, _urlQueryEncoder2.default)(urlPropsQueryConfig);
  var encoded = encode({ foo: 137 });

  expect(encoded).toEqual({ fooInUrl: '137' });
});

it('works when the object to encode has got missing properies with named `queryParam`', function () {
  var urlPropsQueryConfig = {
    foo: { type: _UrlQueryParamTypes2.default.number, queryParam: 'fooInUrl' },
    bar: { type: _UrlQueryParamTypes2.default.string }
  };

  var encode = (0, _urlQueryEncoder2.default)(urlPropsQueryConfig);
  var encoded = encode({ bar: 'str' });

  expect(encoded).toEqual({ bar: 'str' });
});

it('respects custom encoders', function () {
  var urlPropsQueryConfig = {
    foo: { type: function type(number) {
        return (number + 1).toString();
      } },
    bar: { type: _UrlQueryParamTypes2.default.string }
  };

  var encode = (0, _urlQueryEncoder2.default)(urlPropsQueryConfig);
  var encoded = encode({ foo: 137, bar: 'str' });

  expect(encoded).toEqual({ foo: '138', bar: 'str' });
});

it('respects custom encoders with named `queryParam`', function () {
  var urlPropsQueryConfig = {
    foo: { type: function type(number) {
        return (number + 1).toString();
      }, queryParam: 'fooInUrl' },
    bar: { type: _UrlQueryParamTypes2.default.string }
  };

  var encode = (0, _urlQueryEncoder2.default)(urlPropsQueryConfig);
  var encoded = encode({ foo: 137, bar: 'str' });

  expect(encoded).toEqual({ fooInUrl: '138', bar: 'str' });
});