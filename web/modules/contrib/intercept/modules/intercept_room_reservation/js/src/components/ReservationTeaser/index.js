import React, { PureComponent } from 'react';
import PropTypes from 'prop-types';
import { connect } from 'react-redux';
import get from 'lodash/get';
import interceptClient from 'interceptClient';
import FieldInline from 'intercept/FieldInline';
import Teaser from 'intercept/Teaser';

const { select, constants } = interceptClient;
const c = constants;

class ReservationTeaser extends PureComponent {
  render() {
    const { id, reservation, actions } = this.props;

    if (reservation === null) {
      return null;
    }

    const attendeeCount = get(reservation, 'attributes.field_attendee_count');
    const attendee = attendeeCount ? (
      <FieldInline
        label="Attendees"
        key="attendee"
        values={{ id: 'attendee', name: attendeeCount }}
      />
    ) : null;
    const statusValue = get(reservation, 'attributes.field_status');
    const statusField = statusValue ? (
      <FieldInline
        label="Status"
        key="status"
        values={{ id: 'status', name: statusValue }}
      />
    ) : null;

    const room = get(reservation, 'relationships.field_room');
    const image = get(room, 'attributes.room_thumbnail');

    return (
      <Teaser
        key={id}
        title={get(reservation, 'attributes.title') || ''}
        modifiers={[image ? 'with-image' : 'without-image']}
        image={image}
        supertitle={get(reservation, 'attributes.location')}
        footer={roomProps => (actions)}
        tags={[attendee, statusField]}
        description={get(reservation, 'attributes.field_group_name')}
      />
    );
  }
}

ReservationTeaser.propTypes = {
  id: PropTypes.string.isRequired,
  reservation: PropTypes.object,
  actions: PropTypes.array,
};

ReservationTeaser.defaultProps = {
  image: null,
  actions: null,
  reservation: null,
};

const mapStateToProps = (state, ownProps) => {
  const identifier = select.getIdentifier(c.TYPE_ROOM_RESERVATION, ownProps.id);
  const reservation = select.bundle(identifier)(state);

  return {
    reservation,
  };
};

export default connect(mapStateToProps)(ReservationTeaser);
