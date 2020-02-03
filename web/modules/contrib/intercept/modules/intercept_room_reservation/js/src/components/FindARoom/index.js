// React
import React from 'react';
import PropTypes from 'prop-types';

// Redux
import { connect } from 'react-redux';

// Lodash
import get from 'lodash/get';

// Drupal
import drupalSettings from 'drupalSettings';

// Intercept
import interceptClient from 'interceptClient';

// Local Components
import RoomFilters from '../RoomFilters';

import RoomList from '../RoomList';
import { Button } from '@material-ui/core';

const { constants, select } = interceptClient;
const c = constants;

function filterByCapacity(items, filters, type, path) {
  let output = items;

  // Filter by location.
  if (type in filters && filters[type]) {
    output = output.filter(item => filters[type] <= get(item, path));
  }

  return output;
}

function filterByRelationship(items, filters, type, path) {
  let output = items;

  // Filter by location.
  if (type in filters && filters[type].length > 0) {
    output = output.filter(item => filters[type].indexOf(get(item, path)) > -1);
  }

  return output;
}

function filterRooms(items, filters) {
  let output = items;

  // Filter by location.
  output = filterByRelationship(
    output,
    filters,
    c.TYPE_LOCATION,
    'data.relationships.field_location.data.id',
  );

  // Filter by Room Type.
  output = filterByRelationship(
    output,
    filters,
    c.TYPE_ROOM_TYPE,
    'data.relationships.field_room_type.data.id',
  );

  // Filter by Capcity.
  output = filterByCapacity(output, filters, 'capacity', 'data.attributes.field_capacity_max');

  return output;
}

class FindARoom extends React.Component {
  constructor(props) {
    super(props);
    this.state = {
      filters: {},
    };
    this.onFilterChange = this.onFilterChange.bind(this);
  }

  onFilterChange(filters) {
    this.setState({ filters });
  }

  render() {
    const { rooms, onSelect } = this.props;
    const { filters } = this.state;
    const teaserProps = {
      footer: roomProps => (<Button variant="contained" size="small" color="primary" className="button button--small button--primary"onClick={() => onSelect(roomProps.uuid)} >Reserve</Button>),
    };

    return (
      <div className="l--offset l--default">
        <div className="l__main">
          <div className="l__primary">
            <div className="l--subsection">
              <RoomFilters onChange={this.onFilterChange} filters={filters} />
            </div>
            <RoomList
              rooms={filterRooms(rooms, filters)}
              onSelect={onSelect}
              teaserProps={teaserProps}
            />
          </div>
        </div>
      </div>
    );
  }
}

const mapStateToProps = state => ({
  rooms: select.roomsAscending(state),
});

FindARoom.propTypes = {
  rooms: PropTypes.arrayOf(Object).isRequired,
  onSelect: PropTypes.func.isRequired,
};

export default connect(mapStateToProps)(FindARoom);
