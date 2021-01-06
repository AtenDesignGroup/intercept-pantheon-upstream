import React from 'react';
import PropTypes from 'prop-types';
import { connect } from 'react-redux';

import Toolbar from 'react-big-calendar/lib/Toolbar';
import { Navigate } from 'react-big-calendar';

// Material UI
import ArrowBack from '@material-ui/icons/ArrowBack';
import ArrowForward from '@material-ui/icons/ArrowForward';
import Button from '@material-ui/core/Button';
import IconButton from '@material-ui/core/IconButton';

// Intercept
/* eslint-disable */
import interceptClient from 'interceptClient';
import BigCalendar from 'intercept/BigCalendar';
/* eslint-enable */

import EventSummaryDialog from './EventSummaryDialog';
import PrintableMonth from './PrintableMonth';

const { api, constants, utils, select } = interceptClient;
const c = constants;

const dateAccessor = prop => item =>
  utils.dateFromDrupal(item.data.attributes.field_date_time[prop]);
const startAccessor = dateAccessor('value');
const endAccessor = dateAccessor('end_value');
const titleAccessor = item => (
  <p className="calendar-event-title--tiny">{item.data.attributes.title}</p>
);

const CalendarEvent = (props) => {
  const { event } = props;
  const { data } = event;

  return (
    <div className="calendar-event">
      <p className="calendar-event__title">{data.attributes.title}</p>
    </div>
  );
};

CalendarEvent.propTypes = {
  event: PropTypes.object.isRequired,
};

class CustomToolbar extends Toolbar {
  render() {
    const { localizer: { messages }, label } = this.props;

    return (
      <div className="rbc-toolbar">
        <span className="rbc-btn-group">
          <Button
            variant="contained"
            color="primary"
            size="small"
            className="rbc-btn rbc-btn--today"
            onClick={this.navigate.bind(null, Navigate.TODAY)}
          >
            {messages.today}
          </Button>
          <Button
            variant="contained"
            color="primary"
            size="small"
            className="rbc-btn rbc-btn--print"
            onClick={this.props.onPrintClick}
          >
            Print
          </Button>
        </span>
        <div className="rbc-toolbar__heading">
          <IconButton
            className={'rbc-toolbar__pager-button rbc-toolbar__pager-button--prev'}
            onClick={this.navigate.bind(null, Navigate.PREVIOUS)}
            color="primary"
            aria-label="Previous"
            variant="flat"
          >
            <ArrowBack />
          </IconButton>
          <h2 className="rbc-toolbar__label">{label}</h2>
          <IconButton
            className={'rbc-toolbar__pager-button rbc-toolbar__pager-button--next'}
            onClick={this.navigate.bind(null, Navigate.NEXT)}
            color="primary"
            aria-label="Next"
          >
            <ArrowForward />
          </IconButton>
        </div>

        <span className="rbc-btn-group rbc-btn-group--views">{this.viewNamesGroup(messages)}</span>
      </div>
    );
  }
}

class EventCalendar extends React.Component {
  constructor(props) {
    super(props);
    this.state = {
      showEvent: false,
      selectedEvent: null,
      isPrint: false,
    };

    this.mediaQueryList = window.matchMedia('print');

    this.onSelectEvent = this.onSelectEvent.bind(this);
    this.onHideEvent = this.onHideEvent.bind(this);
    this.printTest = this.printTest.bind(this);
    this.setPrintState = this.setPrintState.bind(this);
    this.onPrintClick = this.onPrintClick.bind(this);
  }

  componentDidMount() {
    // Print handling.
    // We add matchmedia support for Safari and older versions of Chrome.
    this.mediaQueryList.addListener(this.printTest);

    // On beforeprint support for FF and IE and newer versions of Chrome.
    window.onbeforeprint = () => {
      // Using both matchmedia and onbeforeprint will conflict on Chrome.
      // If onbeforeprint is supported and fires, remove the matchMedia listener
      // before it triggers.  This allows it to work for Safari but disables it in
      // browsers that support onbeforeprint.
      this.mediaQueryList.removeListener(this.printTest);
      this.setPrintState(true);
    };
    window.onafterprint = () => {
      this.setPrintState(false);
    };
  }

  componentWillUnmount() {
    this.mediaQueryList.removeListener(this.printTest);
    window.onbeforeprint = null;
    window.onafterprint = null;
  }

  onSelectEvent(event) {
    this.props.fetchEvent(event.data.id);

    this.setState({
      showEvent: true,
      selectedEvent: event.data.id,
    });
  }

  onHideEvent() {
    this.setState({
      showEvent: false,
    });
  }

  onPrintClick() {
    this.setState(() => ({
      isPrint: true,
    }),
    () => {
      window.print();
    });
  }

  setPrintState(isPrint) {
    this.setState({ isPrint });
  }

  printTest(mql) {
    this.setPrintState(mql.matches);
  }

  render() {
    const components = {
      event: CalendarEvent,
      toolbar: props => (<CustomToolbar {...props} onPrintClick={this.onPrintClick} />),
    };

    return (
      <React.Fragment>
        <BigCalendar
          timeZoneName={utils.getUserTimezone()}
          components={components}
          filters={this.props.filters}
          events={this.props.events}
          onSelectEvent={this.onSelectEvent}
          titleAccessor={titleAccessor}
          startAccessor={startAccessor}
          endAccessor={endAccessor}
          onNavigate={this.props.onNavigate}
          onView={this.props.onView}
          defaultView={this.props.defaultView}
          defaultDate={this.props.defaultDate}
          popup
          formats={{
            dayRangeHeaderFormat: ({ start, end }, culture, localizer) => {
              const s = localizer.format(start, 'MMM D', culture);
              let e = '';
              if (localizer.format(start, 'MMM', culture) === localizer.format(end, 'MMM', culture)) {
                // week spans one month, don't display ending month (eg Feb 9 - 15 )
                e = localizer.format(end, 'D', culture);
              }
              else {
                // week spans two months, display ending month (eg Feb 24 - Mar 2)
                e = localizer.format(end, 'MMM D', culture);
              }
              return `${s} - ${e}`;
            },
            dayFormat: 'ddd M/D',
            dayHeaderFormat: 'dddd MMM D',
          }}
          views={{
            month: this.state.isPrint ? PrintableMonth : true,
            week: true,
            day: true,
          }}
          elementProps={{
            style: {
              height: 'calc(100vh - 26rem)',
            },
          }}
          min={new Date('Jan 1, 2000 07:00:00')}
          max={new Date('Jan 1, 2000 22:00:00')}
        />
        <EventSummaryDialog
          id={this.state.selectedEvent}
          open={this.state.showEvent}
          onClose={this.onHideEvent}
          loading={this.props.isEventLoading}
        />
      </React.Fragment>
    );
  }
}

EventCalendar.propTypes = {
  events: PropTypes.arrayOf(Object).isRequired,
  filters: PropTypes.object.isRequired,
  onNavigate: PropTypes.func,
  onView: PropTypes.func,
  defaultDate: PropTypes.instanceOf(Date),
  defaultView: PropTypes.string,
};

EventCalendar.defaultProps = {
  onNavigate: null,
  onView: null,
  defaultDate: new Date(),
  defaultView: 'month',
};

const mapStateToProps = state => ({
  isEventLoading:
    select.recordsAreLoading(c.TYPE_EVENT)(state) ||
    select.recordsAreLoading(c.TYPE_EVENT_REGISTRATION)(state),
});

const mapDispatchToProps = dispatch => ({
  fetchEvent: (id) => {
    dispatch(
      api[c.TYPE_EVENT].fetchAll({
        filters: {
          uuid: {
            value: id,
            path: 'id',
          },
        },
        include: [
          'image_primary',
          'image_primary.field_media_image',
          'field_room',
        ],
        headers: {
          'X-Consumer-ID': interceptClient.consumer,
        },
      }),
    );
    dispatch(
      api[c.TYPE_EVENT_REGISTRATION].fetchAll({
        filters: {
          event: {
            value: id,
            path: 'field_event.id',
          },
          user: {
            value: utils.getUserUuid(),
            path: 'field_user.id',
          },
        },
      }),
    );
  },
});

export default connect(
  mapStateToProps,
  mapDispatchToProps,
)(EventCalendar);
