import actionCreator from './util/actionCreator';
import * as t from './actionTypes';

export const request = actionCreator(t.REQUEST, 'resource', 'id');

export const receive = actionCreator(t.RECEIVE, 'resp', 'resource', 'id');

export const receiveTranslation = actionCreator(
  t.RECEIVE_TRANSLATION,
  'resp',
  'resource',
  'langcode',
  'id',
);

export const failure = actionCreator(t.FAILURE, 'error', 'resource', 'id');

// Removes all local items and resets API syncing state;
export const purge = actionCreator(t.PURGE, 'resource');

// Resets API syncing state;
export const reset = actionCreator(t.RESET, 'resource');

export const clearErrors = actionCreator(t.CLEAR_ERRORS, 'resource', 'id');

export const setSaved = actionCreator(t.SET_SAVED, 'value', 'resource', 'id');

export const markDirty = actionCreator(t.MARK_DIRTY, 'resource', 'id');

export const setTimestamp = actionCreator(
  t.SET_TIMESTAMP,
  'resource',
  'timestamp',
);

export const setValidating = actionCreator(
  t.SET_VALIDATING,
  'resource',
  'value',
);

export const add = actionCreator(t.ADD, 'data', 'resource', 'id');

export const edit = actionCreator(t.EDIT, 'data', 'resource', 'id');
