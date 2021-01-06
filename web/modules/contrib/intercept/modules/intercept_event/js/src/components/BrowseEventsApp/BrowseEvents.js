// React
import React, { Component } from 'react';
import PropTypes from 'prop-types';

// Redux
import { connect } from 'react-redux';

// Lodash
import debounce from 'lodash/debounce';
import difference from 'lodash/difference';
import last from 'lodash/last';
import xor from 'lodash/xor';
import pick from 'lodash/pick';
import throttle from 'lodash/throttle';
import uniq from 'lodash/uniq';

// Moment
import moment from 'moment';

// in-viewport
import inViewport from 'in-viewport';

/* eslint-disable */
import interceptClient from 'interceptClient';
import ViewSwitcher from 'intercept/ViewSwitcher';
import LoadingIndicator from 'intercept/LoadingIndicator';
import PageSpinner from 'intercept/PageSpinner';
/* eslint-enable */

import EventFilters from './../EventFilters';
import EventList from './../EventList';
import EventCalendar from './../EventCalendar';

const { constants, api, select, utils } = interceptClient;
const c = constants;

const DESIGNATION = 'designation';
const DESIGNATION_FIELD = 'field_event_designation';

const eventIncludes = (view = 'list') =>
  (view === 'list' ? ['field_room'] : null);

const viewOptions = [{ key: 'list', value: 'List' }, { key: 'calendar', value: 'Calendar' }];

const sparseFieldsets = (view = 'list') =>
  (view === 'list'
    ? {
      [c.TYPE_EVENT]: [
        'drupal_internal__nid',
        'status',
        'title',
        'path',
        'field_capacity_max',
        'field_waitlist_max',
        'field_date_time',
        'field_must_register',
        'field_text_teaser',
        'registration',
        'field_event_audience',
        'field_event_register_period',
        'field_event_type',
        'field_event_tags',
        'field_location',
        DESIGNATION_FIELD,
        'field_room',
        'event_thumbnail',
      ],
      [c.TYPE_EVENT_REGISTRATION]: ['field_event', 'field_user', 'status'],
      [c.TYPE_ROOM]: ['drupal_internal__nid', 'title', 'field_location'],
      [c.TYPE_FILE]: ['drupal_internal__fid', 'uri', 'url'],
    }
    : {
      [c.TYPE_EVENT]: [
        'drupal_internal__nid',
        'title',
        'path',
        'field_date_time',
        'field_must_register',
        'field_location',
        DESIGNATION_FIELD,
      ],
    });

function getDate(value, view = 'day', boundary = 'start') {
  const method = boundary === 'start' ? 'startOf' : 'endOf';
  const date = moment.tz(value, utils.getUserTimezone())[method](view);
  // The calendar view may include date from the previous or next month
  // so we make sure to include the beginning of the first week and
  // end of the last week.
  if (view === 'month') {
    date[method]('week');
  }

  return date.format();
}

function getDateSpan(value, view = 'day') {
  const start = getDate(value, view, 'start');
  const end = getDate(value, view, 'end');
  return [start, end];
}

function getPublishedFilters(value = true) {
  return {
    published: {
      path: 'status',
      value: value ? '1' : '0',
    },
  };
}

function getDesignationFilters(value = 'events') {
  return {
    [DESIGNATION]: {
      path: DESIGNATION_FIELD,
      value,
    },
  };
}

function getDateFilters(values, view = 'list', calView = 'day', date = new Date()) {
  const path = 'field_date_time.value';
  let operator = '>=';
  let value = moment(new Date())
    .tz(utils.getUserTimezone())
    .startOf('day')
    .format();
  // Handler Calendar view.
  // The date should be determined by the date and calendar view type
  // rather than the selected date value.
  if (view === 'calendar') {
    value = getDateSpan(date, calView);
    operator = 'BETWEEN';
  }
  else if (values[c.DATE_START] && values[c.DATE_END]) {
    value = [
      getDate(values[c.DATE_START], 'day', 'start'),
      getDate(values[c.DATE_END], 'day', 'end'),
    ];
    operator = 'BETWEEN';
  }
  else if (values[c.DATE_START]) {
    value = getDate(values[c.DATE_START], 'day', 'start');
    operator = '>';
  }
  else if (values[c.DATE_END]) {
    value = getDate(values[c.DATE_END], 'day', 'end');
    operator = '<';
  }

  return {
    data: {
      path,
      value,
      operator,
    },
  };
}

function getKeywordFilters(value) {
  const filters = {};

  if (!value.keyword) {
    return filters;
  }

  const operator = 'CONTAINS';
  const keyword = value.keyword
    // PHP strip_tags
    .replace(/<.*?>/g, '')
    // Remove non alphanumeric characters
    .replace(/[^a-zA-Z 0-9\-]/g, '')
    // Trim whitespace
    .replace(/\s\s+/g, ' ');

  filters['keyword'] = {
    path: 'field_keywords',
    operator,
    value: keyword,
  };

  return filters;
}

function getFilters(values, view = 'list', calView = 'day', date = new Date()) {
  const filter = {
    ...getPublishedFilters(true),
    ...getDesignationFilters(values[DESIGNATION]),
    ...getDateFilters(values, view, calView, date),
    ...getKeywordFilters(values, 'keyword'),
  };

  if (!values) {
    return filter;
  }

  const types = [
    { id: c.TYPE_EVENT_TYPE, path: 'field_event_type.id', conjunction: 'OR' },
    { id: c.TYPE_LOCATION, path: 'field_location.id', conjunction: 'OR' },
    { id: c.TYPE_AUDIENCE, path: 'field_event_audience.id', conjunction: 'OR' },
  ];

  types.forEach((type) => {
    if (values[type.id] && values[type.id].length > 0) {
      if (type.conjunction === 'AND') {
        const group = `${type.id}-group`;
        filter[group] = {
          type: 'group',
          conjunction: type.conjunction,
        };
        values[type.id].forEach((element, key) => {
          const id = `${type.id}-${key}`;
          filter[id] = {
            path: type.path,
            value: element,
            memberOf: group,
          };
        });
      }
      else {
        filter[type.label || type.id] = {
          path: type.path,
          value: values[type.value || type.id],
          operator: 'IN',
        };

        if (type.group) {
          filter[type.group] = {
            type: 'group',
            conjunction: type.conjunction,
          };
          filter[type.label || type.id].memberOf = type.group;
        }
      }
    }
  });

  return filter;
}

function getSortDirection(view, values) {
  let dir = 'ASC';
  if (view === 'list' && values[c.DATE_END] && !values[c.DATE_START]) {
    dir = 'DESC';
  }
  return dir;
}

class BrowseEvents extends Component {
  constructor(props) {
    super(props);
    this.state = {
      calView: props.calView,
      date: props.date,
      filters: props.filters,
      view: props.view,
      scrollSatisfied: false,
      fetcher: null,
    };
    this.handleCalendarNavigate = this.handleCalendarNavigate.bind(this);
    this.handleCalendarView = this.handleCalendarView.bind(this);
    this.handleFilterChange = this.handleFilterChange.bind(this);
    this.handleViewChange = this.handleViewChange.bind(this);
    this.processAudienceFilters = this.processAudienceFilters.bind(this);
    this.setFetchers = this.setFetchers.bind(this);
    this.doFetch = debounce(this.doFetch, 100).bind(this);
    this.doFetchMore = this.doFetchMore.bind(this);
    this.handleScroll = throttle(this.handleScroll, 30, { leading: true }).bind(this);
  }

  componentDidMount() {
    this.setFetchers(this.props.filters, this.props.view, this.props.calView, this.props.date);
    this.props.fetchRegistrations();
    window.addEventListener('scroll', this.handleScroll);
  }

  componentDidUpdate(prevProps) {
    const { scrollSatisfied } = this.state;
    const { scroll, events, view } = this.props;

    if (events.length > 0 && view === 'list') {
      const lastPageLoaded = last(Array.from(document.getElementsByClassName('content-list')));
      if (!scrollSatisfied && scroll !== null && scroll !== 0) {
        const scrollPage = last(Array.from(document.querySelectorAll(`[data-page-num='${scroll}']`)));
        if (scrollPage !== undefined) {
          scrollPage.scrollIntoView(true);
          this.setState({ scrollSatisfied: true });
        }
        else if (last(events).items.length === 0) {
          this.setState({ scrollSatisfied: true });
        }
        else {
          lastPageLoaded.scrollIntoView(true);
        }
      }
    }
  }

  componentWillUnmount() {
    window.removeEventListener('scroll', this.handleScroll);
  }

  setFetchers(
    values = this.props.filters,
    view = this.props.view,
    calView = this.props.calView,
    date = this.props.date,
  ) {
    const options = {
      filters: getFilters(values, view, calView, date),
      include: eventIncludes(view),
      replace: true,
      fields: pick(sparseFieldsets(view), [
        c.TYPE_EVENT,
        c.TYPE_FILE,
        c.TYPE_ROOM,
      ]),
      sort: {
        date: {
          path: 'field_date_time.value',
          direction: getSortDirection(view, values),
        },
        title: {
          path: 'title',
        },
        id: {
          path: 'drupal_internal__nid',
        },
      },
      count: view === 'list' ? 10 : 0,
      headers: {
        'X-Consumer-ID': interceptClient.consumer,
      },
      limit: view === 'list' ? 10 : 50,
    };

    const fetcher = {
      [c.TYPE_EVENT]: api[c.TYPE_EVENT].fetcher(options),
    };

    this.setState({
      fetcher,
    });

    this.doFetch(fetcher);
  }

  handlePageChange = (value) => {
    this.props.onChangePage(value);
  };

  handleViewChange = (value) => {
    this.props.onChangeView(value);
    this.setFetchers(this.props.filters, value, this.props.calView, this.props.date);
  };

  handleCalendarNavigate = (date, calView) => {
    this.props.onChangeDate(date);
    this.setFetchers(this.props.filters, 'calendar', calView, date);
  };

  handleCalendarView = (calView) => {
    this.props.onChangeCalView(calView);
    this.setFetchers(this.props.filters, 'calendar', calView, this.props.date);
  };

  // Handle audience filter parent child selections.
  // This ensures parent terms are selected when all children
  // are selected. As well as selects and deselects the children
  // in sync with the parents.
  processAudienceFilters(values) {
    const oldTerms = this.props.filters[c.TYPE_AUDIENCE] || [];
    const newTerms = values[c.TYPE_AUDIENCE] || [];
    const tree = this.props.audienceOptions;

    let selected = newTerms;

    // Return if audiences are the same.
    if (oldTerms.length === newTerms.length) {
      return values;
    }

    // Which audience changed?
    const altered = xor(oldTerms, newTerms).pop();
    // Was audience added or removed?
    const op = oldTerms.length < newTerms.length ? 'add' : 'remove';
    // Does audience have children?
    const top = tree.filter(t => t.key === altered).pop();
    // If this is a parent apply same operation to children
    if (top) {
      const related = top.children.map(child => child.key);
      selected = op === 'add'
        ? uniq([].concat(newTerms, related))
        : difference(newTerms, related);
    }
    // If this is a child apply same operation to the parent based on all children
    else {
      // Find parent
      const related = tree.filter(
        node => node.children.filter(child => child.key === altered).length > 0,
      ).pop();

      // If removing a child
      if (op === 'remove') {
        // Remove the parent
        selected = difference(newTerms, [related.key]);
      }
      // If adding a child and all children are selected
      else if (difference(related.children.map(child => child.key), newTerms).length === 0) {
        // Add the parent
        selected = uniq([].concat(newTerms, related.key));
      }
    }

    return {
      ...values,
      [c.TYPE_AUDIENCE]: selected,
    };
  }

  handleFilterChange(values) {
    const newValues = this.processAudienceFilters(values);
    this.props.onChangeFilters(newValues);
    this.setFetchers(newValues);
  }

  handleScroll() {
    // The calendar view should fetch all visible events so no need to load on scroll.
    if (this.props.view === 'calendar') {
      return;
    }

    const windowHeight =
      'innerHeight' in window ? window.innerHeight : document.documentElement.offsetHeight;
    const body = document.body;
    const html = document.documentElement;
    const docHeight = Math.max(
      body.scrollHeight,
      body.offsetHeight,
      html.clientHeight,
      html.scrollHeight,
      html.offsetHeight,
    );
    const windowBottom = windowHeight + window.pageYOffset;

    if (windowBottom >= docHeight - 1500) {
      this.doFetchMore(c.TYPE_EVENT);
    }

    const currentPage = last(Array.from(document.getElementsByClassName('content-list')).filter(element => inViewport(element, { offset: -300 })));
    if (currentPage !== undefined) {
      const { scroll } = this.props;
      if (scroll !== currentPage.getAttribute('data-page-num')) {
        this.handlePageChange(currentPage.getAttribute('data-page-num'));
      }
    }
  }

  nextPage = () => {
    this.doFetchMore(c.TYPE_EVENT);
  }

  doFetch(fetcher) {
    const { fetchEntities } = this.props;
    fetchEntities(fetcher[c.TYPE_EVENT]);
  }

  doFetchMore(type) {
    const { fetchEntities, loading } = this.props;
    if (!loading[type] && !this.state.fetcher[type].isDone()) {
      fetchEntities(this.state.fetcher[type]);
    }
  }

  render() {
    const {
      props,
      handleCalendarNavigate,
      handleViewChange,
      handleCalendarView,
      handleFilterChange,
    } = this;
    const { calendarEvents, events, loading, filters, view, date, calView } = props;
    const eventsLoading = loading[c.TYPE_EVENT];
    const eventComponent =
      view === 'list' ? (
        <React.Fragment>
          <EventList events={events} loading={eventsLoading} />
          <LoadingIndicator loading={eventsLoading} />
        </React.Fragment>
      ) : (
        <EventCalendar
          events={calendarEvents}
          filters={filters}
          onNavigate={handleCalendarNavigate}
          onView={handleCalendarView}
          defaultView={calView}
          date={date}
          defaultDate={date}
        />
      );

    return (
      <div className="l--offset">
        <div className="clearfix">
          <div className="l__main">
            <div className="l--subsection">
              <ViewSwitcher value={view} handleChange={handleViewChange} options={viewOptions} />
              <PageSpinner loading={eventsLoading} />
              <EventFilters
                onChange={handleFilterChange}
                showDate={view === 'list'}
                filters={filters}
                view={view}
              />
            </div>
            <div className="l__primary">{eventComponent}</div>
          </div>
        </div>
      </div>
    );
  }
}

const mapStateToProps = (state, ownProps) => {
  const dir = getSortDirection(ownProps.view, ownProps.filters);
  return {
    events: select[dir === 'DESC' ? 'eventsByDateAscending' : 'eventsByDateDescending'](state),
    loading: {
      [c.TYPE_EVENT]: select.recordsAreLoading(c.TYPE_EVENT)(state),
      [c.TYPE_EVENT_REGISTRATION]: select.recordsAreLoading(c.TYPE_EVENT_REGISTRATION)(state),
    },
    calendarEvents: select.calendarEvents(state),
    audienceOptions: select.recordOptions(c.TYPE_AUDIENCE)(state),
  };
};

const mapDispatchToProps = dispatch => ({
  fetchEntities: (fetcher) => {
    dispatch(fetcher.next());
  },
  fetchRegistrations: utils.isUserLoggedIn()
    ? () => {
      dispatch(
        api[c.TYPE_EVENT_REGISTRATION].fetchAll({
          filters: {
            user: {
              value: utils.getUserUuid(),
              path: 'field_user.id',
            },
            status: {
              path: 'status',
              value: ['active', 'waitlist'],
              operator: 'IN',
            },
          },
          sort: {
            date: {
              path: 'created',
              direction: 'DESC',
            },
          },
        }),
      );
    }
    : () => {}, // Don't fetch if the user is loggedOut
});

BrowseEvents.propTypes = {
  calendarEvents: PropTypes.arrayOf(Object).isRequired,
  events: PropTypes.arrayOf(Object).isRequired,
  audienceOptions: PropTypes.arrayOf(Object).isRequired,
  loading: PropTypes.object.isRequired,
  fetchEntities: PropTypes.func.isRequired,
  fetchRegistrations: PropTypes.func.isRequired,
  calView: PropTypes.string,
  date: PropTypes.instanceOf(Date),
  view: PropTypes.string,
  filters: PropTypes.object,
  page: PropTypes.number,
  scroll: PropTypes.number,
  onChangeCalView: PropTypes.func.isRequired,
  onChangeView: PropTypes.func.isRequired,
  onChangeFilters: PropTypes.func.isRequired,
  onChangeDate: PropTypes.func.isRequired,
  onChangePage: PropTypes.func.isRequired,
};

BrowseEvents.defaultProps = {
  view: 'list',
  calView: 'month',
  page: 0,
  scroll: 0,
  date: utils.getUserTimeNow(),
  filters: {
    [c.KEYWORD]: '',
    location: [],
    type: [],
    audience: [],
    [c.DATE]: null,
    [c.DATE_START]: null,
    [c.DATE_END]: null,
    [DESIGNATION]: 'events', // TODO: Get this from drupalSettings
  },
};

export default connect(
  mapStateToProps,
  mapDispatchToProps,
)(BrowseEvents);
