import React from 'react';
import PropTypes from 'prop-types';

class RoomLimitWarning extends React.PureComponent {
  render() {
    const { userStatus } = this.props;
    let message = '';

    const getDestination = () => encodeURIComponent(window.location.pathname + window.location.search);

    if ((!userStatus.exceededLimit || !userStatus.initialized) && window.drupalSettings.intercept.user.barred === false) {
      return null;
    }

    if (window.drupalSettings.user.uid === 0) {
      message = (
        <p className="value-summary__footer-text">
          You must be logged in to reserve rooms.{' '}
          <a
            className="value-summary__footer-link"
            href={`/user/login?destination=${getDestination()}`}
          >
            Log in now
          </a>
        </p>
      );
    }
    else if (window.drupalSettings.intercept.user.barred === true) {
      const msg = window.drupalSettings.intercept.room_reservations.reservation_barred_text;
      message = (
        <p
          className="value-summary__footer-text"
          dangerouslySetInnerHTML={{ __html: msg }}
        />
      );
    }
    else {
      message = (
        <p className="value-summary__footer-text">
          You are only allowed to reserve a maximum of {userStatus.limit} room
          {parseInt(userStatus.limit, 10) === 1 ? '' : 's'} at a time.{' '}
          <a className="value-summary__footer-link" href="/account/room-reservations">
            View your current reservations.
          </a>
        </p>
      );
    }

    return (
      <div className={`value-summary__footer value-summary__footer--${this.props.level}`}>
        {message}
      </div>
    );
  }
}

RoomLimitWarning.propTypes = {
  userStatus: PropTypes.object.isRequired,
  level: PropTypes.string,
};

RoomLimitWarning.defaultProps = {
  level: 'error',
};

export default RoomLimitWarning;
