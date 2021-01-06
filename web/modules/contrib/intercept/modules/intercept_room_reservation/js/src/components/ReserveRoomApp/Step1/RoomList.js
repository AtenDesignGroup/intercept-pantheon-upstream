import React from 'react';
import PropTypes from 'prop-types';
import RoomTeaser from 'intercept/RoomTeaser';
import ContentList from 'intercept/ContentList';

class RoomList extends React.PureComponent {
  render() {
    const { rooms, teaserProps, TeaserComponent, loading } = this.props;

    const teasers = items =>
      items.map(item => ({
        key: item.data.id,
        node: <TeaserComponent uuid={item.data.id} id={item.data.id} className="room-teaser" {...teaserProps} />,
      }));

    const list =
      rooms.length > 0 ? (
        <ContentList items={teasers(rooms)} key={0} />
      ) : (
        !loading && <div key={0}>
          <p>No rooms were found.</p>
          <p>Try:
            <ul>
              <li>Confirming the spelling of your search words.</li>
              <li>Using other words for the subject of your searches.</li>
            </ul>
          </p>
        </div>
      );

    return <div className="rooms-list">{list}</div>;
  }
}

RoomList.propTypes = {
  rooms: PropTypes.arrayOf(Object).isRequired,
  TeaserComponent: PropTypes.func,
  teaserProps: PropTypes.object,
  loading: PropTypes.bool,
};

RoomList.defaultProps = {
  TeaserComponent: RoomTeaser,
  teaserProps: {},
  loading: false,
};

export default RoomList;
