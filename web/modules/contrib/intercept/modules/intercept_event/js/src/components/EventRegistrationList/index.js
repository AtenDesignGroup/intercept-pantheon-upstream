// React
import React from 'react';
import PropTypes from 'prop-types';

// Intercept Components
import ContentList from 'intercept/ContentList';

// Local Components
import RegistrationTeaser from './../RegistrationTeaser';

class EventRegistrationList extends React.PureComponent {
  render() {
    const { items } = this.props;

    const teasers = i =>
      i.map(id => ({
        key: id,
        node: (
          <RegistrationTeaser
            id={id}
            className="registrations-teaser"
          />
        ),
      }));

    const list =
      Object.values(items).length > 0 ? (
        <div>
          <ContentList items={teasers(items)} key={0} />
        </div>
      ) : (
        <p key={0}>No registrations have been loaded.</p>
      );

    return <div className="registrations-list">{list}</div>;
  }
}

EventRegistrationList.propTypes = {
  items: PropTypes.arrayOf(String).isRequired,
};

export default EventRegistrationList;
