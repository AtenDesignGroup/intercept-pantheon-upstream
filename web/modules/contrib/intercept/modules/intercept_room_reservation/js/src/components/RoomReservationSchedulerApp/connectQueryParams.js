// React URL Query
import { addUrlProps, UrlQueryParamTypes, encode, decode, Serialize } from 'react-url-query';

// Lodash
import pickBy from 'lodash/pickBy';
import moment from 'moment';

/* eslint-disable */
import interceptClient from 'interceptClient';
import updateWithHistory from 'intercept/updateWithHistory';
/* eslint-enable */

const {
  decodeArray,
  encodeArray,
  decodeBoolean,
  encodeBoolean,
  decodeObject,
  encodeObject,
  decodeString,
  encodeString,
} = Serialize;

const { constants, utils } = interceptClient;
const c = constants;
const ATTENDEES = 'attendees';
const TIME = 'time';
const DURATION = 'duration';
const NOW = 'now';
const START = 'start';
const END = 'end';

const removeFalseyProps = obj => pickBy(obj, prop => prop);

const decodeDate = (value) => {
  // Convert the url param to a date object in local time.
  const date = decode(UrlQueryParamTypes.date, value) || null;

  if (date === null) {
    return undefined;
  }

  // Convert the date object to the start of the User's timezone day, in local time.
  const output = utils.convertDateFromLocal(date, utils.getUserTimezone());
  return output;
};

const encodeDate = (value) => {
  return moment(value)
    .format('YYYY-MM-DD') || null;
};

const encodeMultiValueExposedFilter = value => encodeArray(value, '[]') || [];
const decodeMultiValueExposedFilter = value => decodeArray(value, '[]') || [];

// Items for URL
// - view
// - room
// - date
// - location
// - time/day
// - # attendees

const urlPropsQueryConfig = {
  // Active View
  view: { type: UrlQueryParamTypes.string },
  // Active Calendar Date
  date: {
    type: {
      decode: decodeDate,
      encode: encodeDate,
    },
  },
  // Selected Room
  room: { type: UrlQueryParamTypes.string },
  // Room Detail
  roomDetail: { type: UrlQueryParamTypes.string },
  // Location exposed filter.
  location: { type: {
    decode: decodeMultiValueExposedFilter,
    encode: encodeMultiValueExposedFilter,
  } },
  // Type exposed filter.
  type: { type: {
    decode: decodeMultiValueExposedFilter,
    encode: encodeMultiValueExposedFilter,
  } },
  'max-capacity': { type: UrlQueryParamTypes.number },
  // Selected Start Time
  [START]: { type: UrlQueryParamTypes.string },
  // Selected End Time
  [END]: { type: UrlQueryParamTypes.string },
  // Detail View
  detail: { type: UrlQueryParamTypes.boolean },
  // Event
  event: { type: UrlQueryParamTypes.string },
};

const connectQueryParams = component =>
  updateWithHistory(addUrlProps({ urlPropsQueryConfig })(component));
export default connectQueryParams;
