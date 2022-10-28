'use strict';

var _serialize = require('../serialize');

var _configureUrlQuery = require('../configureUrlQuery');

var _configureUrlQuery2 = _interopRequireDefault(_configureUrlQuery);

function _interopRequireDefault(obj) { return obj && obj.__esModule ? obj : { default: obj }; }

// Resets the global configuration to prevent side-effects in other tests
var resetConfiguration = function resetConfiguration() {
  return (0, _configureUrlQuery2.default)({ entrySeparator: '_', keyValSeparator: '-' });
};

describe('utils', function () {
  describe('serialization', function () {
    describe('encodeDate', function () {
      it('produces the correct value', function () {
        var date = new Date(2016, 2, 1);
        var result = (0, _serialize.encodeDate)(date);
        expect(result).toBe('2016-03-01');
      });

      it('handles null and undefined', function () {
        var result = (0, _serialize.encodeDate)(null);
        expect(result).toBeNull();
        expect((0, _serialize.encodeDate)()).not.toBeDefined();
      });
    });

    describe('decodeDate', function () {
      it('produces the correct value', function () {
        var result = (0, _serialize.decodeDate)('2016-03-01');
        // result is a Date object
        expect(result.getFullYear()).toBe(2016);
        expect(result.getMonth()).toBe(2);
        expect(result.getDate()).toBe(1);

        // javascript likes to give us 2015-12-31 19:00, so test this doesn't.
        result = (0, _serialize.decodeDate)('2016');
        expect(result.getFullYear()).toBe(2016);
        expect(result.getMonth()).toBe(0);
        expect(result.getDate()).toBe(1);
      });

      it('handles null and undefined', function () {
        var result = (0, _serialize.decodeDate)(null);
        expect(result).not.toBeDefined();
        expect((0, _serialize.decodeDate)()).not.toBeDefined();
        expect((0, _serialize.decodeDate)('')).not.toBeDefined();
      });

      it('handles malformed input', function () {
        var result = (0, _serialize.decodeDate)('foo-one-two');
        expect(result).not.toBeDefined();
      });
    });

    describe('encodeBoolean', function () {
      it('produces the correct value', function () {
        expect((0, _serialize.encodeBoolean)(true)).toBe('1');
        expect((0, _serialize.encodeBoolean)(false)).toBe('0');
        expect((0, _serialize.encodeBoolean)()).not.toBeDefined();
      });
    });

    describe('decodeBoolean', function () {
      it('produces the correct value', function () {
        expect((0, _serialize.decodeBoolean)('1')).toBe(true);
        expect((0, _serialize.decodeBoolean)('0')).toBe(false);
        expect((0, _serialize.decodeBoolean)()).not.toBeDefined();
        expect((0, _serialize.decodeBoolean)('')).not.toBeDefined();
      });

      it('handles malformed input', function () {
        expect((0, _serialize.decodeBoolean)('foo')).not.toBeDefined();
      });
    });

    describe('encodeNumber', function () {
      it('produces the correct value', function () {
        expect((0, _serialize.encodeNumber)(123)).toBe('123');
        expect((0, _serialize.encodeNumber)(-32.12)).toBe('-32.12');
        expect((0, _serialize.encodeNumber)()).not.toBeDefined();
      });
    });

    describe('decodeNumber', function () {
      it('produces the correct value', function () {
        expect((0, _serialize.decodeNumber)('99')).toBe(99);
        expect((0, _serialize.decodeNumber)('-58.21')).toBe(-58.21);
        expect((0, _serialize.decodeNumber)()).not.toBeDefined();
        expect((0, _serialize.decodeNumber)('')).not.toBeDefined();
      });

      it('handles malformed input', function () {
        expect((0, _serialize.decodeNumber)('foo')).not.toBeDefined();
      });
    });

    describe('encodeString', function () {
      it('produces the correct value', function () {
        expect((0, _serialize.encodeString)('foo')).toBe('foo');
        expect((0, _serialize.encodeString)()).not.toBeDefined();
      });
    });

    describe('decodeString', function () {
      it('produces the correct value', function () {
        expect((0, _serialize.decodeString)('bar')).toBe('bar');
        expect((0, _serialize.decodeString)('')).toBe('');
        expect((0, _serialize.decodeString)()).not.toBeDefined();
        expect((0, _serialize.decodeString)(null)).not.toBeDefined();
      });
    });

    describe('encodeJson', function () {
      it('produces the correct value', function () {
        var input = { test: '123', foo: [1, 2, 3] };
        expect((0, _serialize.encodeJson)(input)).toBe(JSON.stringify(input));
        expect((0, _serialize.encodeJson)()).not.toBeDefined();
        expect((0, _serialize.encodeJson)(null)).not.toBeDefined();
        expect((0, _serialize.encodeJson)(0)).toBe('0');
        expect((0, _serialize.encodeJson)(false)).toBe('false');
      });
    });

    describe('decodeJson', function () {
      it('produces the correct value', function () {
        var output = (0, _serialize.decodeJson)('{"foo": "bar", "jim": ["grill"]}');
        var expectedOutput = {
          foo: 'bar',
          jim: ['grill']
        };
        expect(output).toEqual(expectedOutput);
        expect((0, _serialize.decodeJson)()).not.toBeDefined();
        expect((0, _serialize.decodeJson)('')).not.toBeDefined();
      });

      it('handles malformed input', function () {
        expect((0, _serialize.decodeJson)('foo')).not.toBeDefined();
      });
    });

    describe('encodeArray', function () {
      it('produces the correct value', function () {
        var input = ['a', 'b', 'c'];
        expect((0, _serialize.encodeArray)(input)).toBe('a_b_c');
        expect((0, _serialize.encodeArray)()).not.toBeDefined();
      });

      it('produces the correct value with a different global separator', function () {
        var input = ['a', 'b', 'c'];
        (0, _configureUrlQuery2.default)({ entrySeparator: '+' });

        expect((0, _serialize.encodeArray)(input)).toBe('a+b+c');
        expect((0, _serialize.encodeArray)()).not.toBeDefined();

        // Revert change so it does not effect other tests
        resetConfiguration();
      });
    });

    describe('decodeArray', function () {
      it('produces the correct value', function () {
        var output = (0, _serialize.decodeArray)('a_b_c');
        var expectedOutput = ['a', 'b', 'c'];

        expect(output).toEqual(expectedOutput);
        expect((0, _serialize.decodeArray)()).not.toBeDefined();
        expect((0, _serialize.decodeArray)('')).not.toBeDefined();
      });

      it('handles empty values', function () {
        expect((0, _serialize.decodeArray)('__')).toEqual([undefined, undefined, undefined]);
      });
    });

    describe('encodeObject', function () {
      it('produces the correct value', function () {
        var input = { test: 'bar', foo: 94 };
        var expectedOutput = 'test-bar_foo-94';
        expect((0, _serialize.encodeObject)(input, '-', '_')).toBe(expectedOutput);
        expect((0, _serialize.encodeObject)()).not.toBeDefined();
        expect((0, _serialize.encodeObject)({})).not.toBeDefined();
      });

      it('produces the correct value with different global separators', function () {
        (0, _configureUrlQuery2.default)({ entrySeparator: ',', keyValSeparator: ':' });
        var input = { test: 'bar', foo: 94 };
        var expectedOutput = 'test:bar,foo:94';

        expect((0, _serialize.encodeObject)(input)).toBe(expectedOutput);
        expect((0, _serialize.encodeObject)()).not.toBeDefined();
        expect((0, _serialize.encodeObject)({})).not.toBeDefined();

        // Revert change so it does not effect other tests
        resetConfiguration();
      });
    });

    describe('decodeObject', function () {
      it('produces the correct value', function () {
        var output = (0, _serialize.decodeObject)('foo-bar_jim-grill_iros-91');
        var expectedOutput = {
          foo: 'bar',
          jim: 'grill',
          iros: '91'
        };
        expect(output).toEqual(expectedOutput);
        expect((0, _serialize.decodeObject)()).not.toBeDefined();
        expect((0, _serialize.decodeObject)('')).not.toBeDefined();
      });

      it('handles malformed input', function () {
        expect((0, _serialize.decodeObject)('foo-bar-jim-grill')).toEqual({ foo: 'bar' });
        expect((0, _serialize.decodeObject)('foo_bar_jim_grill')).toEqual({
          foo: undefined,
          bar: undefined,
          jim: undefined,
          grill: undefined
        });
      });
    });

    describe('encodeNumericArray', function () {
      it('produces the correct value', function () {
        var input = [9, 4, 0];
        expect((0, _serialize.encodeNumericArray)(input)).toBe('9_4_0');
        expect((0, _serialize.encodeNumericArray)()).not.toBeDefined();
      });
    });

    describe('decodeNumericArray', function () {
      it('produces the correct value', function () {
        var output = (0, _serialize.decodeNumericArray)('9_4_0');
        var expectedOutput = [9, 4, 0];

        expect(output).toEqual(expectedOutput);
        expect((0, _serialize.decodeNumericArray)()).not.toBeDefined();
        expect((0, _serialize.decodeNumericArray)('')).not.toBeDefined();
      });

      it('handles empty values', function () {
        expect((0, _serialize.decodeNumericArray)('__')).toEqual([undefined, undefined, undefined]);
      });
    });

    describe('encodeNumericObject', function () {
      it('produces the correct value', function () {
        var input = { test: 55, foo: 94 };
        var expectedOutput = 'test-55_foo-94';
        expect((0, _serialize.encodeNumericObject)(input, '-', '_')).toBe(expectedOutput);
        expect((0, _serialize.encodeNumericObject)()).not.toBeDefined();
        expect((0, _serialize.encodeNumericObject)({})).not.toBeDefined();
      });
    });

    describe('decodeNumericObject', function () {
      it('produces the correct value', function () {
        var output = (0, _serialize.decodeNumericObject)('foo-55_jim-100_iros-94');
        var expectedOutput = {
          foo: 55,
          jim: 100,
          iros: 94
        };
        expect(output).toEqual(expectedOutput);
        expect((0, _serialize.decodeNumericObject)()).not.toBeDefined();
        expect((0, _serialize.decodeNumericObject)('')).not.toBeDefined();
      });

      it('handles malformed input', function () {
        expect((0, _serialize.decodeNumericObject)('foo-bar-jim-grill')).toEqual({ foo: NaN });
        expect((0, _serialize.decodeNumericObject)('foo_bar_jim_grill')).toEqual({
          foo: undefined,
          bar: undefined,
          jim: undefined,
          grill: undefined
        });
      });
    });

    describe('decode', function () {
      it('decodes by type', function () {
        var input = '91';
        expect((0, _serialize.decode)('number', input)).toBe(91);
      });

      it('decodes using default value', function () {
        var input = undefined;
        expect((0, _serialize.decode)('number', input, 94)).toBe(94);
        expect((0, _serialize.decode)('array', 'foo_bar', [])).toEqual(['foo', 'bar']);
        expect((0, _serialize.decode)('object', 'a-b_c-d', {})).toEqual({ a: 'b', c: 'd' });
      });

      it('decodes using custom function', function () {
        var input = '94';
        expect((0, _serialize.decode)(function (d) {
          return parseInt(d + d, 10);
        }, input)).toBe(9494);
      });

      it('decodes using object with .decode custom function', function () {
        var input = '94';
        expect((0, _serialize.decode)({ decode: function decode(d) {
            return parseInt(d + d, 10);
          } }, input)).toBe(9494);
      });

      it('handles no decoder found', function () {
        var input = '94';
        expect((0, _serialize.decode)('fancy', input)).toBe(input);
      });

      it('decodes an invalid number as undefined', function () {
        var input = 'notanumber';
        expect((0, _serialize.decode)('number', input, 94)).not.toBeDefined();
      });
    });

    describe('encode', function () {
      it('encodes by type', function () {
        var input = 91;
        expect((0, _serialize.encode)('number', input)).toBe('91');
      });

      it('encodes using custom function', function () {
        var input = 94;
        expect((0, _serialize.encode)(function (d) {
          return '' + d + d;
        }, input)).toBe('9494');
      });

      it('encodes using object with .encode custom function', function () {
        var input = 94;
        expect((0, _serialize.encode)({ encode: function encode(d) {
            return '' + d + d;
          } }, input)).toBe('9494');
      });

      it('handles no encoder found', function () {
        var input = 94;
        expect((0, _serialize.encode)('fancy', input)).toBe(input);
      });
    });

    describe('decode+encode', function () {
      it('encode(decode(number)) === number', function () {
        var input = '91';
        expect((0, _serialize.encode)('number', (0, _serialize.decode)('number', input))).toBe(input);
      });

      it('decode(encode(number)) === number', function () {
        var input = 91;
        expect((0, _serialize.decode)('number', (0, _serialize.encode)('number', input))).toBe(input);
      });

      it('encode(decode(boolean)) === boolean', function () {
        var input = '0';
        expect((0, _serialize.encode)('boolean', (0, _serialize.decode)('boolean', input))).toBe(input);
      });

      it('decode(encode(boolean)) === boolean', function () {
        var input = true;
        expect((0, _serialize.decode)('boolean', (0, _serialize.encode)('boolean', input))).toBe(input);
      });

      it('encode(decode(date)) === date', function () {
        var input = '2016-03-01';
        expect((0, _serialize.encode)('date', (0, _serialize.decode)('date', input))).toBe(input);
      });

      it('decode(encode(date)) === date', function () {
        var input = new Date(2016, 2, 1);
        expect((0, _serialize.decode)('date', (0, _serialize.encode)('date', input))).toEqual(input);
      });

      it('encode(decode(json)) === json', function () {
        var input = '{"foo":"bar","baz":["jim"]}';
        expect((0, _serialize.encode)('json', (0, _serialize.decode)('json', input))).toBe(input);
      });

      it('decode(encode(json)) === json', function () {
        var input = { foo: 'bar', baz: ['jim'] };
        expect((0, _serialize.decode)('json', (0, _serialize.encode)('json', input))).toEqual(input);
      });

      it('encode(decode(object)) === object', function () {
        var input = 'foo-bar_baz-jim';
        expect((0, _serialize.encode)('object', (0, _serialize.decode)('object', input))).toBe(input);
      });

      it('decode(encode(object)) === object', function () {
        var input = { foo: 'bar', baz: 'jim' };
        expect((0, _serialize.decode)('object', (0, _serialize.encode)('object', input))).toEqual(input);
      });

      it('encode(decode(array)) === array', function () {
        var input = 'A_R_R_A_Y';
        expect((0, _serialize.encode)('array', (0, _serialize.decode)('array', input))).toBe(input);
      });

      it('decode(encode(array)) === array', function () {
        var input = ['A', 'R', 'R', 'A', 'Y'];
        expect((0, _serialize.decode)('array', (0, _serialize.encode)('array', input))).toEqual(input);
      });

      it('encode(decode(numericObject)) === numericObject', function () {
        var input = 'foo-555_baz-999';
        expect((0, _serialize.encode)('numericObject', (0, _serialize.decode)('numericObject', input))).toBe(input);
      });

      it('decode(encode(numericObject)) === numericObject', function () {
        var input = { foo: 3, baz: 777 };
        expect((0, _serialize.decode)('numericObject', (0, _serialize.encode)('numericObject', input))).toEqual(input);
      });

      it('encode(decode(numericArray)) === numericArray', function () {
        var input = '1_2_3';
        expect((0, _serialize.encode)('numericArray', (0, _serialize.decode)('numericArray', input))).toBe(input);
      });

      it('decode(encode(numericArray)) === numericArray', function () {
        var input = [5, 6, 7];
        expect((0, _serialize.decode)('numericArray', (0, _serialize.encode)('numericArray', input))).toEqual(input);
      });
    });
  });
});