import React from 'react';
import PropTypes from 'prop-types';
import map from 'lodash/map';
import moment from 'moment';

// Intercept
/* eslint-disable */
import interceptClient from 'interceptClient';
import EventTeaser from 'intercept/EventTeaser';
import ContentList from 'intercept/ContentList';
/* eslint-enable */

const { utils } = interceptClient;

class EventList extends React.Component {
  state = {};

  render() {
    const { events, loading } = this.props;

    const teasers = items =>
      items.map(id => ({
        key: id,
        node: <EventTeaser id={id} className="event-teaser" />,
      }));

    const list =
      events.length > 0 ? (
        map(events, (group, index) => (
          <ContentList
            heading={utils.getDayDisplay(moment.tz(group.date, utils.getUserTimezone()))}
            items={teasers(group.items)}
            key={group.key}
            page={index}
          />
        ))
      ) : (
        !loading && <div key={0}>
          <p>No events were found.</p>
          <p>Try:</p>
          <ul>
            <li>Confirming the spelling of your search words.</li>
            <li>Using other words for the subject of your searches.</li>
          </ul>
        </div>
      );

    return <div className="events-list">{list}</div>;
  }
}

EventList.propTypes = {
  events: PropTypes.arrayOf(Object).isRequired,
  loading: PropTypes.bool,
};

EventList.defaultProps = {
  loading: false,
};

export default EventList;
