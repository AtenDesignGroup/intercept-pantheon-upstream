import React from 'react';
import PropTypes from 'prop-types';
import { connect } from 'react-redux';
import { withStyles } from '@material-ui/core/styles';
import get from 'lodash/get';
import interceptClient from 'interceptClient';
import Summary from 'intercept/Summary';
import RegistrationStatus from './../RegistrationStatus';

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

function EventSummary(props) {
  const { id, event } = props;

  // Return if bundle has not loaded.
  if (!event.attributes) {
    return null;
  }

  const dateStart = utils.dateFromDrupal(event.attributes['field_date_time'].value);
  const dateEnd = utils.dateFromDrupal(event.attributes['field_date_time'].end_value);
  const image = get(event, 'attributes.event_thumbnail');

  return (
    <div>
      <Summary
        key={id}
        modifiers={[image ? 'with-image' : 'without-image', 'constrained', 'card']}
        image={image}
        subtitle={get(event, 'relationships.field_location.0.attributes.title')}
        title={get(event, 'attributes.title')}
        titleUrl={
          event.attributes.path ? event.attributes.path.alias : `/node/${event.attributes.nid}`
        }
        date={{
          date: utils.getDayDisplay(dateStart),
          time: `${utils.getTimeDisplay(dateStart)} - ${utils.getTimeDisplay(dateEnd)}`,
        }}
        label={get(event, 'attributes.field_must_register') ? 'Registration Required' : null}
        body={get(event, 'attributes.field_text_teaser.value')}
      >
        <RegistrationStatus event={event} eventId={id} />
      </Summary>
    </div>
  );
}

EventSummary.propTypes = {
  classes: PropTypes.object.isRequired,
  id: PropTypes.string.isRequired,
  event: PropTypes.object.isRequired,
};

EventSummary.defaultProps = {
  image: null,
};

const mapStateToProps = (state, ownProps) => {
  const identifier = select.getIdentifier(c.TYPE_EVENT, ownProps.id);

  return {
    event: select.bundle(identifier)(state),
  };
};

export default connect(mapStateToProps)(withStyles(styles)(EventSummary));
