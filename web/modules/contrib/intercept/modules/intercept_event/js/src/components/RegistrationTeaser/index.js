// React
import React from 'react';
import PropTypes from 'prop-types';

// Redux
import { connect } from 'react-redux';

// Lodash
import get from 'lodash/get';

// Moment
import moment from 'moment';

/* eslint-disable */
// Intercept
import interceptClient from 'interceptClient';
import drupalSettings from 'drupalSettings';

// Intercept Components
import Teaser from 'intercept/Teaser';
import TeaserStub from 'intercept/Teaser/TeaserStub';
import RegistrationStatus from 'intercept/RegistrationStatus';
import ButtonRegister from 'intercept/ButtonRegister';
/* eslint-enable */

const { select, constants, utils } = interceptClient;
const c = constants;

const userId = get(drupalSettings, 'intercept.user.uuid');

class RegistrationTeaser extends React.PureComponent {
  render() {
    const { id, registration, event, image } = this.props;

    // Render a stub teaser until the entity has fully loaded.
    if (!event.attributes) {
      return <TeaserStub />;
    }
    const status = get(registration, 'attributes.status');
    const date = moment(utils.dateFromDrupal(event.attributes['field_date_time'].value));

    return (
      <Teaser
        key={id}
        modifiers={[image ? 'with-image' : 'without-image', status]}
        title={event.attributes.title}
        titleUrl={
          event.attributes.path ? event.attributes.path.alias : `/node/${event.attributes.nid}`
        }
        image={image}
        supertitle={get(event, 'relationships.field_location.attributes.title')}
        date={{
          month: date.utcOffset(utils.getUserUtcOffset()).format('MMM'),
          date: date.utcOffset(utils.getUserUtcOffset()).format('D'),
          time: utils.getTimeDisplay(date),
        }}
        footer={() => (
          <React.Fragment>
            {/* <ButtonRegister event={event} registrations={[registration]} /> */}
            <ButtonRegister eventId={event.id} userId={userId} />
            <RegistrationStatus event={event} />
          </React.Fragment>
        )}
        description={event.attributes['field_text_teaser'].value}
      />
    );
  }
}

RegistrationTeaser.propTypes = {
  id: PropTypes.string.isRequired,
  registration: PropTypes.object.isRequired,
  event: PropTypes.object,
  image: PropTypes.string,
};

RegistrationTeaser.defaultProps = {
  image: null,
  event: null,
};

const mapStateToProps = (state, ownProps) => {
  const identifier = select.getIdentifier(c.TYPE_EVENT_REGISTRATION, ownProps.id);
  const registration = select.bundle(identifier)(state);
  const event = get(registration, 'relationships.field_event');
  const eventIdentifier = select.getIdentifier(c.TYPE_EVENT, event.id);

  return {
    registration,
    image: select.resourceImageStyle(eventIdentifier, '4to3_740x556')(state),
    event,
  };
};

export default connect(mapStateToProps)(RegistrationTeaser);
