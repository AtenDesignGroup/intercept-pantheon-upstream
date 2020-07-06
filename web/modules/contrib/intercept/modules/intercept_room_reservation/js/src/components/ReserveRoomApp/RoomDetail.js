import React from 'react';
import PropTypes from 'prop-types';
import { connect } from 'react-redux';
import { withStyles } from '@material-ui/core/styles';
import get from 'lodash/get';
import interceptClient from 'interceptClient';
import FieldInline from 'intercept/FieldInline';
import Summary from 'intercept/Summary';

const { constants, select, utils } = interceptClient;
const c = constants;

const styles = {
  card: {
    maxWidth: 345,
  },
  media: {
    height: 0,
    paddingTop: '56.25%', // 16:9
  },
};

function RoomDetail(props) {
  const { id, resource } = props;

  // Return if bundle has not loaded.
  if (!resource.attributes) {
    return null;
  }

  const capacityMinValue = get(resource, 'attributes.field_capacity_min');
  const capacityMin = capacityMinValue ? (
    <FieldInline
      label="Min Capacity"
      key="capacityMin"
      values={{ id: 'capacityMin', name: capacityMinValue }}
    />
  ) : null;

  const capacityMaxValue = get(resource, 'attributes.field_capacity_max');
  const capacityMax = capacityMaxValue ? (
    <FieldInline
      label="Max Capacity"
      key="capacityMax"
      values={{ id: 'capacityMax', name: capacityMaxValue }}
    />
  ) : null;

  const feesValue = get(resource, 'attributes.field_room_fees.processed');
  const createFeesMarkup = () => ({ __html: feesValue });
  const fees = feesValue ? (
    <div className="field field--inline">
      <strong className="field__label">Fees</strong>
      <div className="field__items" dangerouslySetInnerHTML={createFeesMarkup()} />
    </div>
  ) : null;

  const bodyValue = get(resource, 'attributes.field_text_content.processed');
  const createBodyMarkup = () => ({ __html: bodyValue });
  const body = bodyValue ? (
    <div className="field field--inline">
      <strong className="field__label">More about this room</strong>
      <div className="field__items" dangerouslySetInnerHTML={createBodyMarkup()} />
    </div>
  ) : null;

  const equipmentValue = get(resource, 'attributes.field_room_standard_equipment');
  const equipment = equipmentValue && equipmentValue.length > 0 ? (
    <FieldInline
      label="Equipment in Room"
      key="equipment"
      values={equipmentValue.map((item, i) => ({ id: i, name: item }))}
    />
  ) : null;

  const image = get(resource, 'attributes.room_thumbnail');

  return (
    <div>
      <Summary
        key={id}
        modifiers={[image ? 'with-image' : 'without-image', 'constrained', 'card']}
        image={image}
        supertitle={get(resource, 'relationships.field_location.0.attributes.title')}
        subtitle={get(resource, 'relationships.field_room_type.attributes.name')}
        title={resource.attributes.title}
        label={resource.attributes.field_must_register ? 'Registration Required' : null}
        body={get(resource, 'attributes.field_text_intro.value')}
      >
        {capacityMin}
        {capacityMax}
        {equipment}
        {fees}
        {body}
      </Summary>
    </div>
  );
}

RoomDetail.propTypes = {
  classes: PropTypes.object.isRequired,
  id: PropTypes.string.isRequired,
  resource: PropTypes.object.isRequired,
};

const mapStateToProps = (state, ownProps) => {
  const identifier = select.getIdentifier(c.TYPE_ROOM, ownProps.id);

  return {
    resource: select.bundle(identifier)(state),
  };
};

export default connect(mapStateToProps)(withStyles(styles)(RoomDetail));
