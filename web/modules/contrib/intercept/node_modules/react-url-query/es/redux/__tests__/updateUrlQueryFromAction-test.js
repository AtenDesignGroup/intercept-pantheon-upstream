'use strict';

var _updateUrlQueryFromAction = require('../updateUrlQueryFromAction');

var _updateUrlQuery = require('../../updateUrlQuery');

// mock this module so we can test if it as called with correct args
jest.mock('../../updateUrlQuery');

it('replaceInUrlQueryFromAction extracts correct args from action', function () {
  (0, _updateUrlQueryFromAction.replaceInUrlQueryFromAction)({ payload: { queryParam: 'foo', encodedValue: '94' } }, 'location');
  expect(_updateUrlQuery.replaceInUrlQuery).toBeCalledWith('foo', '94', 'location');
});

it('pushInUrlQueryFromAction extracts correct args from action', function () {
  (0, _updateUrlQueryFromAction.pushInUrlQueryFromAction)({ payload: { queryParam: 'foo', encodedValue: '94' } }, 'location');
  expect(_updateUrlQuery.pushInUrlQuery).toBeCalledWith('foo', '94', 'location');
});

it('multiReplaceInUrlQueryFromAction extracts correct args from action', function () {
  (0, _updateUrlQueryFromAction.multiReplaceInUrlQueryFromAction)({ payload: { encodedQuery: { foo: '94' } } }, 'location');
  expect(_updateUrlQuery.multiReplaceInUrlQuery).toBeCalledWith({ foo: '94' }, 'location');
});

it('multiPushInUrlQueryFromAction extracts correct args from action', function () {
  (0, _updateUrlQueryFromAction.multiPushInUrlQueryFromAction)({ payload: { encodedQuery: { foo: '94' } } }, 'location');
  expect(_updateUrlQuery.multiPushInUrlQuery).toBeCalledWith({ foo: '94' }, 'location');
});

it('replaceUrlQueryFromAction extracts correct args from action', function () {
  (0, _updateUrlQueryFromAction.replaceUrlQueryFromAction)({ payload: { encodedQuery: { foo: '94' } } }, 'location');
  expect(_updateUrlQuery.replaceUrlQuery).toBeCalledWith({ foo: '94' }, 'location');
});

it('pushUrlQueryFromAction extracts correct args from action', function () {
  (0, _updateUrlQueryFromAction.pushUrlQueryFromAction)({ payload: { encodedQuery: { foo: '94' } } }, 'location');
  expect(_updateUrlQuery.pushUrlQuery).toBeCalledWith({ foo: '94' }, 'location');
});