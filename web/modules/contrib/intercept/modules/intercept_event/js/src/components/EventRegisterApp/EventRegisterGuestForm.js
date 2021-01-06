// React
import React from 'react';
import PropTypes from 'prop-types';

// Redux
import { connect } from 'react-redux';

// UUID
import { v4 as uuidv4 } from 'uuid';

// Lodash
import get from 'lodash/get';
import map from 'lodash/map';

// Intercept
import interceptClient from 'interceptClient';

import RegisterEventStep1 from './Step1';
import RegisterEventStep2 from './Step2';

import EventRegisterConfirmation from './EventRegisterConfirmation';

const { actions, constants, select } = interceptClient;
const c = constants;

const text = {
  active: {
    dialogHeading: 'Are you sure you want to register?',
  },
  waitlist: {
    dialogHeading: 'Are you sure you want to join the waitlist?',
  },
};

const buildEventRegistration = (values) => {
  const uuid = uuidv4();

  const output = {
    id: uuid,
    type: c.TYPE_EVENT_REGISTRATION,
    attributes: {
      status: values.status,
      field_guest_email: values.email,
      field_guest_name_first: values.nameFirst,
      field_guest_name_last: values.nameLast,
      field_guest_phone_number: values.phoneNumber,
      field_guest_zip_code: values.zip,
    },
    relationships: {
      field_event: {
        data: {
          type: c.TYPE_EVENT,
          id: values.event,
        },
      },
      field_registrants: {
        data: map(values.registrants, (value, id) => ({
          type: c.TYPE_POPULATION_SEGMENT,
          id,
          meta: {
            count: value,
          },
        })),
      },
    },
  };
  return output;
};

class EventRegisterGuestForm extends React.Component {
  constructor(props) {
    super(props);

    this.state = {
      openDialog: false,
      step: 0,
      values: {
        nameFirst: '',
        nameLast: '',
        phoneNumber: '',
        email: '',
        zip: '',
        registrants: {},
      },
    };

    this.form = React.createRef();
  }

  onOpenDialog = () => {
    this.setState({ openDialog: true });
  };

  onCloseDialog = () => {
    this.setState({ openDialog: false });
  };

  onChangeStep = (step) => {
    this.setState({ step });
  }

  getValuesTotal() {
    const { values } = this.state;
    return this.props.segments.reduce((total, s) => total + (values.registrants[s.key] || 0), 0);
  }

  handleFormChange = (values) => {
    this.setState({
      values: {
        ...this.state.values,
        ...values,
      },
    });
  };

  saveEntitytoStore = (values) => {
    const { save } = this.props;
    const entity = buildEventRegistration(values);
    this.setState({
      uuid: entity.id,
    });
    save(entity);
    return entity.id;
  };

  render() {
    const {
      eventId,
      status,
    } = this.props;
    const {
      values,
      step,
      uuid,
    } = this.state;
    const currentStatus = status;
    const total = this.getValuesTotal();

    const steps = [
      <RegisterEventStep1
        {...this.props}
        values={values}
        onChange={this.handleFormChange}
        onChangeStep={(s) => {
          this.onChangeStep(s);
        }}
      />,
      <RegisterEventStep2
        {...this.props}
        values={values}
        onOpenDialog={() => this.onOpenDialog()}
        onChange={this.handleFormChange}
        onChangeStep={(s) => {
          this.onChangeStep(s);
        }}
      />,
    ];

    return (
      <div className="l--2-col">
        <div className="l__main">
          <div className="l--section">
            {steps[step]}
            <EventRegisterConfirmation
              open={this.state.openDialog}
              onCancel={this.onCloseDialog}
              uuid={uuid}
              eventId={eventId}
              heading={text[currentStatus].dialogHeading}
              total={total}
              status={currentStatus}
              onConfirm={() =>
                this.saveEntitytoStore({
                  event: eventId,
                  status: currentStatus,
                  registrants: values.registrants,
                  nameFirst: values.nameFirst,
                  nameLast: values.nameLast,
                  phoneNumber: values.phoneNumber,
                  email: values.email,
                  zip: values.zip,
                })
              }
            />
          </div>
        </div>
      </div>
    );
  }
}

EventRegisterGuestForm.propTypes = {
  user: PropTypes.object,
  eventId: PropTypes.string.isRequired,
  save: PropTypes.func.isRequired,
  status: PropTypes.string,
};

EventRegisterGuestForm.defaultProps = {
  segments: [],
  user: {},
  status: 'active',
};

const mapStateToProps = (state, ownProps) => {
  const event = select.record(select.getIdentifier(c.TYPE_EVENT, ownProps.eventId))(state);
  const registrationStatus = get(event, 'data.attributes.registration.status');
  const status = registrationStatus === 'waitlist' ? 'waitlist' : 'active';

  return { status };
};

const mapDispatchToProps = dispatch => ({
  save: (data) => {
    dispatch(actions.add(data, c.TYPE_EVENT_REGISTRATION, data.id));
  },
});

export default connect(
  mapStateToProps,
  mapDispatchToProps,
)(EventRegisterGuestForm);
