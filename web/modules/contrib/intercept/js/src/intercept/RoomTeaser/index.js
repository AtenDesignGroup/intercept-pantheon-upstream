import React, { PureComponent } from 'react';
import PropTypes from 'prop-types';
import { connect } from 'react-redux';
import get from 'lodash/get';
import interceptClient from 'interceptClient';
import FieldInline from './../FieldInline';
import Teaser from './../Teaser';

const { select, constants } = interceptClient;
const c = constants;

class RoomTeaser extends PureComponent {
  render() {
    const { id, room, image, footer } = this.props;

    const termMap = item => ({
      id: item.id,
      name: get(item, 'attributes.name'),
    });

    const roomTypeValues = get(room, 'relationships.field_room_type');
    const roomTypes = roomTypeValues.id ? (
      <FieldInline label="Room Type" key="roomType" values={termMap(roomTypeValues)} />
    ) : null;

    const capacityValue = get(room, 'attributes.field_capacity_max');
    const capicity = capacityValue ? (
      <FieldInline
        label="Capacity"
        key="capacity"
        values={{ id: 'capacity', name: capacityValue }}
      />
    ) : null;

    const staffUseValue = get(room, 'attributes.field_staff_use_only');
    const staffUse = staffUseValue ? (
      <FieldInline
        label="Staff Use Only"
        key="staffUse"
      />
    ) : null;

    return (
      <Teaser
        key={id}
        uuid={id}
        modifiers={['narrow', image ? 'with-image' : 'without-image']}
        footer={footer}
        image={image}
        supertitle={get(room, 'relationships.field_location.attributes.title')}
        title={room.attributes.title}
        description={get(room, 'attributes.field_text_teaser.value')}
        tags={[roomTypes, capicity, staffUse]}
        room={room}
      />
    );
  }
}

RoomTeaser.propTypes = {
  id: PropTypes.string.isRequired,
  room: PropTypes.object.isRequired,
  image: PropTypes.string,
  footer: PropTypes.func,
};

RoomTeaser.defaultProps = {
  image: null,
  footer: null,
};

const mapStateToProps = (state, ownProps) => {
  const identifier = select.getIdentifier(c.TYPE_ROOM, ownProps.id);

  return {
    room: select.bundle(identifier)(state),
    image: select.resourceImageStyle(identifier, '4to3_740x556')(state),
  };
};

export default connect(mapStateToProps)(RoomTeaser);
