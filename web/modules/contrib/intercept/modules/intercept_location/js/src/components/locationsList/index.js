import React from 'react';
import PropTypes from 'prop-types';
import { withStyles } from '@material-ui/core/styles';
import map from 'lodash/map';

const styles = theme => ({});

class LocationList extends React.Component {
  state = {};

  render() {
    const { locations } = this.props;

    const list =
      Object.keys(locations).length > 0 ? (
        map(locations, (location, id) => <p key={id}>{location.data.title}</p>)
      ) : (
        <p>No locations have been loaded.</p>
      );

    return <div className="locationList">{list}</div>;
  }
}

LocationList.propTypes = {
  locations: PropTypes.object.isRequired,
};

export default withStyles(styles, { withTheme: true })(LocationList);
