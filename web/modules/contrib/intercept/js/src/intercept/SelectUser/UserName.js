import React from 'react';
import PropTypes from 'prop-types';

class UserName extends React.PureComponent {
  render() {
    return (<span className="user-name"><strong>{this.props.label}: </strong>{this.props.name}</span>);
  }
}

UserName.defaultProps = {
  name: null,
  label: 'Reserved for',
};

UserName.propTypes = {
  name: PropTypes.string,
  label: PropTypes.string,
};

export default UserName;
