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

const removeFalseyProps = obj => pickBy(obj, prop => prop);

const decodeDate = (value) => {
  const date = decode(UrlQueryParamTypes.date, value) || null;

  if (date === null) {
    return date;
  }

  return moment.tz(value, utils.getUserTimezone()).toDate() || null;
};

const encodeDate = value =>
  moment(value)
    .tz(utils.getUserTimezone())
    .format('YYYY-MM-DD') || null;

const encodeFilters = (value) => {
  const filters = removeFalseyProps({
    location: encodeArray(value[c.TYPE_LOCATION], ','),
    type: encodeArray(value[c.TYPE_ROOM_TYPE], ','),
    attendees: encode(UrlQueryParamTypes.number, value[ATTENDEES]),
    [c.DATE]: !value[c.DATE] ? null : utils.getDayTimeStamp(value[c.DATE]),
    [TIME]: encodeString(value.time),
    [DURATION]: encodeString(value.duration),
    [c.KEYWORD]: encodeString(value.keyword),
    [NOW]: encodeBoolean(value[NOW]),
  });
  return encodeObject(filters, ':', '_');
};

const decodeFilters = (values) => {
  if (!values) {
    return {
      [c.TYPE_LOCATION]: [],
      [c.TYPE_ROOM_TYPE]: [],
      // [ATTENDEES]: decode(UrlQueryParamTypes.number, value.attendees),
      [c.DATE]: null,
      [c.KEYWORD]: '',
      // [TIME]: decodeString(value.time),
      // [DURATION]: decodeString(value.duration),
      // [NOW]: decodeBoolean(value[NOW]),
    };
  }
  const value = decodeObject(values, ':', '_');
  const filters = {
    [c.TYPE_LOCATION]: decodeArray(value.location, ',') || [],
    [c.TYPE_ROOM_TYPE]: decodeArray(value.type, ',') || [],
    [ATTENDEES]: decode(UrlQueryParamTypes.number, value.attendees),
    [c.DATE]: value[c.DATE] ? utils.getDateFromDayTimeStamp(value[c.DATE]) : null,
    [c.KEYWORD]: decodeString(value.keyword),
    [TIME]: decodeString(value.time),
    [DURATION]: decodeString(value.duration),
    [NOW]: decodeBoolean(value[NOW]),
  };
  return filters;
};

// Items for URL
// - steps
// - room
// - date
// - location
// - time/day
// - # attendees

const urlPropsQueryConfig = {
  // Active Step
  step: { type: UrlQueryParamTypes.number },
  // Selected Room
  room: { type: UrlQueryParamTypes.string },
  // Room Detail
  roomDetail: { type: UrlQueryParamTypes.string },
  // Detail View
  detail: { type: UrlQueryParamTypes.boolean },
  // Event
  event: { type: UrlQueryParamTypes.string },
  filters: {
    type: {
      decode: decodeFilters,
      encode: encodeFilters,
    },
  },
};

const connectQueryParams = component =>
  updateWithHistory(addUrlProps({ urlPropsQueryConfig })(component));
export default connectQueryParams;
