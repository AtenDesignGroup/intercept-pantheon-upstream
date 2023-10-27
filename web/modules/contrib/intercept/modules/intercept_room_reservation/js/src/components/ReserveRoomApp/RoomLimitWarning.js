import React from 'react';
import PropTypes from 'prop-types';

class RoomLimitWarning extends React.PureComponent {
  render() {
    const { userStatus, query } = this.props;
    let message = '';

    if ((!userStatus.exceededLimit || !userStatus.initialized) && window.drupalSettings.intercept.user.barred === false && (!window.drupalSettings.intercept.room_reservations.eligibility || window.drupalSettings.intercept.room_reservations.eligibility === 'all' || window.drupalSettings.intercept.room_reservations.eligibility === 'study')) {
      return null;
    }

    const destination = encodeURIComponent(window.location.pathname + query);

    if (window.drupalSettings.user.uid === 0) {
      message = (
        <p className="value-summary__footer-text">
          You must be logged in to reserve rooms.{' '}
          <a
            className="value-summary__footer-link"
            href={`/user/login?destination=${destination}`}
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
    else if (window.drupalSettings.intercept.room_reservations.eligibility === 'none') {
      const msg = window.drupalSettings.intercept.room_reservations.eligibility_text;
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
  query: PropTypes.string,
};

RoomLimitWarning.defaultProps = {
  level: 'error',
  query: '',
};

export default RoomLimitWarning;
