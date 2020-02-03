// React
import React, { Component } from 'react';
import PropTypes from 'prop-types';

// Redux
import { connect } from 'react-redux';

// Moment
import moment from 'moment';

// Drupal
import drupalSettings from 'drupalSettings';

// Lodash
import debounce from 'lodash/debounce';

// Intercept
import interceptClient from 'interceptClient';

// Intercept Components
import ContentList from 'intercept/ContentList';

import EventTeaser from 'intercept/EventTeaser';
import LoadingIndicator from 'intercept/LoadingIndicator';
import ViewSwitcher from 'intercept/ViewSwitcher';
// Local Components
import EventList from '../EventList';

import { CircularProgress } from '@material-ui/core';

const { constants, api, select } = interceptClient;
const c = constants;

const uuid = drupalSettings.intercept.parameters.user.uuid;

const viewOptions = [{ key: 'past', value: 'Past' }, { key: 'upcoming', value: 'Upcoming' }];

function getDateFilters(tense = 'upcoming', path) {
  const operator = tense === 'past' ? '<' : '>';
  const value = moment(new Date()).toISOString();

  return {
    date: {
      path,
      value,
      operator,
    },
  };
}

class AccountEventList extends Component {
  constructor(props) {
    super(props);
    this.state = {
      open: false,
    };
    this.handleViewChange = this.handleViewChange.bind(this);
    this.doFetch = debounce(this.doFetch, 300).bind(this);
    this.doFetchRegistrations = this.doFetchRegistrations.bind(this);
    this.doFetchSavedEvents = this.doFetchSavedEvents.bind(this);
  }

  componentDidMount() {
    this.props.fetchAudiences({
      fields: {
        [c.TYPE_AUDIENCE]: ['name'],
      },
    });
    this.doFetch(this.props.view);
  }

  handleViewChange = (value) => {
    this.props.onChangeView(value);
    this.doFetch(value);
  };

  doFetchRegistrations(view) {
    this.props.fetchRegistrations({
      filters: {
        user: {
          path: 'field_user.id',
          value: uuid,
        },
        ...getDateFilters(view, 'field_event.field_date_time.end_value'),
      },
      include: [
        'field_event',
        'field_event.image_primary',
        'field_event.image_primary.field_media_image',
        'field_event.field_location',
      ],
      // replace: true,
      headers: {
        'X-Consumer-ID': interceptClient.consumer,
      },
    });
  }

  doFetchSavedEvents(view) {
    this.props.fetchSavedEvents({
      filters: {
        user: {
          path: 'uid.uid',
          value: uuid,
        },
        // ...getDateFilters(view, 'flagged_entity.field_date_time.end_value'),
      },
      include: [
        'flagged_entity',
        'flagged_entity.image_primary',
        'flagged_entity.image_primary.field_media_image',
        'flagged_entity.field_location',
      ],
      // replace: true,
      headers: {
        'X-Consumer-ID': interceptClient.consumer,
      },
    });
  }

  doFetch(view) {
    if (this.props.showSaves) {
      this.doFetchSavedEvents(view);
    }
    if (this.props.showRegistrations) {
      this.doFetchRegistrations(view);
    }
  }

  doConfirmAction() {
    this.setState({
      open: true,
      text: 'Confirm cancel',
    });
  }

  render() {
    const { props, handleViewChange } = this;
    const { events, view, isLoading } = props;

    const teasers = items =>
      items.map(item => ({
        key: item.data.id,
        node: <EventTeaser id={item.data.id} className="event-teaser" />,
      }));

    const list =
      events.length > 0 ? (
        <ContentList heading={null} items={teasers(events)} />
      ) : isLoading ? (
        <LoadingIndicator loading={isLoading} />
      ) : (
        <p>No events available.</p>
      );

    return (
      <div className="l--main">
        <div className="l--subsection">
          <ViewSwitcher options={viewOptions} value={view} handleChange={handleViewChange} />
        </div>
        <div className="l--subsection">{list}</div>
      </div>
    );
  }
}

AccountEventList.propTypes = {
  events: PropTypes.array,
  onChangeView: PropTypes.func.isRequired,
  fetchAudiences: PropTypes.func.isRequired,
  fetchRegistrations: PropTypes.func.isRequired,
  fetchSavedEvents: PropTypes.func.isRequired,
  view: PropTypes.string,
  showSaves: PropTypes.bool,
  showRegistrations: PropTypes.bool,
};

AccountEventList.defaultProps = {
  events: [],
  view: 'upcoming',
  showSaves: true,
  showRegistrations: true,
};

const mapStateToProps = (state, ownProps) => {
  let selector = ownProps.view === 'past' ? 'usersPastEvents' : 'usersUpcomingEvents';

  // Only show registrations if we are not showing saves.
  if (ownProps.showSaves === false) {
    selector =
      ownProps.view === 'past' ? 'usersPastRegisteredEvents' : 'usersUpcomingRegisteredEvents';
  }

  // Only show saved events if we are not showing registrations.
  if (ownProps.showRegistrations === false) {
    selector = ownProps.view === 'past' ? 'usersPastSavedEvents' : 'usersUpcomingSavedEvents';
  }

  return {
    events: select[selector](uuid)(state),
    registrations: select.eventRegistrations(state),
    isLoading:
      select.recordsAreLoading(c.TYPE_EVENT_REGISTRATION)(state) ||
      select.recordsAreLoading(c.TYPE_SAVED_EVENT)(state),
  };
};

const mapDispatchToProps = (dispatch, ownProps) => ({
  fetchAudiences: (options) => {
    dispatch(api[c.TYPE_AUDIENCE].fetchAll(options));
  },
  fetchRegistrations: (options) => {
    dispatch(api[c.TYPE_EVENT_REGISTRATION].fetchAll(options));
  },
  fetchSavedEvents: (options) => {
    dispatch(api[c.TYPE_SAVED_EVENT].fetchAll(options));
  },
});

export default connect(
  mapStateToProps,
  mapDispatchToProps,
)(AccountEventList);
