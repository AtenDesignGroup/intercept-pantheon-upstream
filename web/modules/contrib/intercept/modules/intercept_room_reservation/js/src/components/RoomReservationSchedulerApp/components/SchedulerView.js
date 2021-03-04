/* eslint-disable no-underscore-dangle */
import PropTypes from 'prop-types';
import React, { useContext, useRef, useEffect, useState } from 'react';
import moment from 'moment';
// eslint-disable-next-line import/no-extraneous-dependencies
import clsx from 'clsx';
// eslint-disable-next-line import/no-extraneous-dependencies
import * as dates from 'date-arithmetic';

import { Navigate } from 'react-big-calendar';
import { notify } from 'react-big-calendar/lib/utils/helpers';

import CalendarContext, { CalendarProvider } from '../context/CalendarContext';
import schedulerLayoutAlgorithm from '../utils/schedulerLayoutAlgorithm';
import * as TimeSlotUtils from '../utils/TimeSlots';
import TimeGridEvent from './TimeGridEvent';
import GroupsContext from '../context/GroupsContext';
import useScrollbarSize from '../hooks/useScrollbarSize';

import interceptClient from 'interceptClient';
import useEventListener from '../hooks/useEventListener';

const { utils } = interceptClient;

const SLOT_WIDTH = 30;
const EVENT_HEIGHT = 50;
const ROW_MIN_HEIGHT = 50;
const ROW_GAP = 2;

function isSelected(event, selected) {
  if (!event || selected == null) return false;
  return [].concat(selected.id).indexOf(event.id) !== -1;
}

const SchedulerViewScrollArea = ({
  children,
  width,
  timelineWidth,
  height,
  hideScrollbar,
  horizontal,
  overflow,
  vertical,
  scrollRef,
  scrollbarSize,
}) => {
  const frameStyles = {};
  const wrapperStyles = {};

  if (horizontal) {
    frameStyles.overflowX = 'hidden';
    if (overflow) {
      frameStyles.overflowX = 'auto';
    }
  }
  if (vertical) {
    frameStyles.height = `${height}px`;
    frameStyles.overflowY = 'hidden';
    if (overflow) {
      frameStyles.overflowY = 'auto';
    }
    if (hideScrollbar) wrapperStyles.marginRight = `-${scrollbarSize.width}px`;
  }

  const canvasStyles = {};
  if (timelineWidth) canvasStyles.width = `${timelineWidth}px`;

  return (
    <div className="scheduler__scroll-wrapper" style={wrapperStyles}>
      <div className="scheduler__scroll-frame" style={frameStyles} ref={scrollRef}>
        <div className="scheduler__scroll-canvas" style={canvasStyles}>
          {children}
        </div>
      </div>
    </div>
  );
};

const GroupToggle = ({ group }) => {
  const { isGroupCollapsed, collapseGroup, expandGroup } = useContext(GroupsContext);
  const { id } = group;

  const isCollapsed = isGroupCollapsed(id);

  const onClick = isCollapsed
    ? () => expandGroup(id)
    : () => collapseGroup(id);

  const text = isCollapsed
    ? 'Expand Group'
    : 'Collapse Group';

  return (<button
    onClick={onClick}
    className={'scheduler__group-toggle'}
    aria-expanded={!isCollapsed}
  >{text}</button>);
};

GroupToggle.propTypes = {
  group: PropTypes.shape({
    id: PropTypes.oneOfType([PropTypes.string, PropTypes.number]).isRequired,
  }).isRequired,
};

const SchedulerViewTimeline = ({ resourceComponent, timelineComponent }) => (
  <tr>
    <td className="scheduler__section scheduler__section--resource">
      {resourceComponent}
    </td>
    <td className="scheduler__section scheduler__section--divider" />
    <td className="scheduler__section scheduler__section--timeline">
      {timelineComponent}
    </td>
  </tr>
);

const SchedulerViewGroupHours = ({ group, accessors, slotMetrics, height, step, timeslots }) => {
  let styledHours;

  // Create mock events for open hours.
  if (group.hours) {
    const resourceHours = [group.hours];
    const styledResourceHours = schedulerLayoutAlgorithm({
      events: resourceHours,
      accessors,
      slotMetrics,
      minimumStartDifference: Math.ceil((step * timeslots) / 2),
    });

    styledHours = getStyledHours(styledResourceHours[0], height);
  }
  else {
    styledHours = [{
      state: 'closed',
      style: {
        left: 0,
        width: '100%',
        height: `${height}px`,
      },
    }];
  }

  return (
    <div className="scheduler__resource-hours" style={{ height: `${height}px` }}>
      {styledHours.map((timeBlock, idx) => (
        <div key={idx} className={`scheduler__resource-hours__block scheduler__resource-hours__block--${timeBlock.state}`} style={timeBlock.style} />
      )) }
    </div>
  );
};

const setDefaultScroll = (ref, time, slotMetrics, step, timeslots, accessors, timelineWidth) => {
  const styles = schedulerLayoutAlgorithm({
    events: [{ start: time, end: time }],
    accessors,
    slotMetrics,
    minimumStartDifference: Math.ceil((step * timeslots) / 2),
  });

  const slotWidth = timelineWidth / (slotMetrics.groups.length * timeslots);
  const rawPosition = (styles[0].style.left / 100) * timelineWidth;
  // Round down to the nearest slot.
  const scrollPosition = Math.max(0, Math.floor(rawPosition / slotWidth) * slotWidth);

  // eslint-disable-next-line no-param-reassign
  ref.current.scrollLeft = scrollPosition;
};

const SchedulerViewEventsRow = (props) => {
  const _select = (...args) => {
    notify(props.onSelectEvent, args);
  };

  const _doubleClick = (...args) => {
    notify(props.onDoubleClickEvent, args);
  };

  const {
    resourceId,
    slotMetrics,
  } = props;

  const renderEvents = () => {
    const {
      accessors,
      components,
      getters,
      localizer,
      rtl,
      selected,
      styledEvents,
    } = props;

    const { messages } = localizer;

    return styledEvents.map(({ event, style }, idx) => {
      const end = accessors.end(event);
      const start = accessors.start(event);
      let format = 'eventTimeRangeFormat';
      let label;

      const startsBeforeDay = slotMetrics.startsBeforeDay(start);
      const startsAfterDay = slotMetrics.startsAfterDay(end);

      if (startsBeforeDay) format = 'eventTimeRangeEndFormat';
      else if (startsAfterDay) format = 'eventTimeRangeStartFormat';

      if (startsBeforeDay && startsAfterDay) label = messages.allDay;
      else label = localizer.format({ start, end }, format);

      const continuesEarlier = startsBeforeDay || slotMetrics.startsBefore(start);
      const continuesLater = startsAfterDay || slotMetrics.startsAfter(end);

      return (
        <TimeGridEvent
          style={style}
          event={event}
          label={label}
          key={`evt_${idx}`}
          getters={getters}
          rtl={rtl}
          components={components}
          continuesEarlier={continuesEarlier}
          continuesLater={continuesLater}
          accessors={accessors}
          selected={isSelected(event, selected)}
          onClick={e => _select(event, e)}
          onDoubleClick={e => _doubleClick(event, e)}
        />
      );
    });
  };

  const {
    selectable,
    accessors,
    getters,
    localizer,
    components: { eventContainerWrapper: EventContainer, ...components },
    onSelectSlot,
    rtl,
  } = useContext(CalendarContext);

  if (props.dnd) {
    return (<div className="scheduler__events-row">
      <EventContainer
        localizer={localizer}
        resource={resourceId}
        accessors={accessors}
        getters={getters}
        components={components}
        slotMetrics={slotMetrics}
        selectable={selectable}
        onSelectSlot={onSelectSlot}
        eventHeight={EVENT_HEIGHT}
      >
        <div className={clsx('rbc-events-container', rtl && 'rtl')} style={props.style} >
          {renderEvents()}
        </div>
      </EventContainer>
    </div>);
  }
  return (<div className="scheduler__events-row" style={props.style} key={resourceId}>
    {renderEvents()}
  </div>);
};

const getFrameWidth = ref => (ref ? (ref.offsetWidth - 300) : 800);

const getTimelineWidth = slots => [].concat(...slots).length * SLOT_WIDTH;

/**
 * Set a dynamic timeline height based on the available window size.
 */
const getTimelineHeight = () => {
  // @todo: Calculate these values dynamically.
  const ADMIN_TOOLBAR_HEIGHT = 40;
  const TOOLBAR_HEIGHT = 56;
  const LEGEND_HEIGHT = 80;

  return window
    ? (window.innerHeight - TOOLBAR_HEIGHT - LEGEND_HEIGHT - ADMIN_TOOLBAR_HEIGHT)
    : 500;
};

/**
 * Syncs the scroll position of the timeline, resource and schedule dom nodes.
 *
 * @param {Object} refs
 */
const scrollSyncEffect = refs => () => {
  const { resourcesRef, scheduleRef, timelineRef } = refs;

  let isSyncingScheduleScroll = false;

  const onScheduleScroll = (e) => {
    window.requestAnimationFrame(() => {
      if (!isSyncingScheduleScroll) {
        isSyncingScheduleScroll = true;
        timelineRef.current.scrollLeft = e.target.scrollLeft;
        resourcesRef.current.scrollTop = e.target.scrollTop;
      }
      isSyncingScheduleScroll = false;
    });
  };

  scheduleRef.current.addEventListener('scroll', onScheduleScroll);

  return () => {
    scheduleRef.current.removeEventListener('scroll', onScheduleScroll);
  };
};

/**
 * Determines whether or not the scheduler has scrolled.
 *
 * @param {Object} refs
 */
const hasScrolledEffect = (refs, setHasScrolled) => () => {
  const { scheduleRef, timelineRef } = refs;

  const removeListeners = (handler) => {
    scheduleRef.current.removeEventListener('scroll', handler);
    timelineRef.current.removeEventListener('scroll', handler);
  };

  const onScroll = () => {
    setHasScrolled(true);
    removeListeners(onScroll);
  };

  scheduleRef.current.addEventListener('scroll', onScroll);
  timelineRef.current.addEventListener('scroll', onScroll);

  return () => {
    removeListeners(onScroll);
  };
};

function getStyledHours(styledResourceHours, height) {
  const { style } = styledResourceHours;

  return [
    {
      state: 'closed',
      style: {
        left: 0,
        width: `${style.left}%`,
        height: `${height}px`,
        top: 0,
      },
    },
    {
      state: 'open',
      style: {
        left: `${style.left}%`,
        width: `${style.width}%`,
        height: `${height}px`,
        top: 0,
      },
    },
    {
      state: 'closed',
      style: {
        left: `${style.left + style.width}%`,
        width: `${100 - style.left - style.width}%`,
        height: `${height}px`,
        top: 0,
      },
    },
  ];
}

function getStyledResources(resources, events, slotMetrics, step, timeslots, accessors) {
  return resources.reduce((styledResources, resource) => {
    const resourceEvents = events.filter(event => event.resourceId === resource.id);
    const styledEvents = schedulerLayoutAlgorithm({
      events: resourceEvents,
      accessors,
      slotMetrics,
      minimumStartDifference: Math.ceil((step * timeslots) / 2),
    });

    const height = styledEvents.reduce(
      (minHeight, event) => (event.style.yOffset > minHeight ? event.style.yOffset : minHeight), EVENT_HEIGHT,
    ) + ROW_GAP;

    return {
      ...styledResources,
      [resource.id]: {
        ...resource,
        styledEvents,
        height,
      },
    };
  }, {});
}

const SchedulerView = (props) => {
  const {
    groups,
    isGroupExpanded,
  } = useContext(GroupsContext);

  const {
    accessors,
    date,
    resources,
    events,
    min,
    max,
    timeslots,
    step,
    components,
  } = useContext(CalendarContext);

  const [
    hasScrolled,
    setHasScrolled,
  ] = useState(false);

  const [
    timelineHeight,
    setTimelineHeight,
  ] = useState(500);

  // Setup scroll refs
  const schedulerRef = useRef(null);
  const timelineRef = useRef(null);
  const resourcesRef = useRef(null);
  const scheduleRef = useRef(null);

  const slotMetrics = TimeSlotUtils.getSlotMetrics({
    min,
    max,
    timeslots,
    step,
  });

  const slots = slotMetrics.groups;
  const frameWidth = getFrameWidth(schedulerRef.current);
  const timelineWidth = getTimelineWidth(slots);

  useEventListener(
    'resize',
    () => {
      setTimelineHeight(getTimelineHeight());
    },
    window,
  );

  // Bind scrolling events
  useEffect(() => {
    scrollSyncEffect({ resourcesRef, scheduleRef, timelineRef })();
    hasScrolledEffect({ scheduleRef, timelineRef }, setHasScrolled)();
    setTimelineHeight(getTimelineHeight());
  }, []);

  useEffect(() => {
    if (scheduleRef.current && !hasScrolled) {
      // We want the current local time.
      // const time = new Date();
      const time = utils.convertDateFromLocal(date, utils.getUserTimezone());
      setDefaultScroll(scheduleRef, time, slotMetrics, step, timeslots, accessors, timelineWidth);
    }
  }, [timelineRef.current, scheduleRef.current, events]);

  const scrollbarSize = useScrollbarSize();
  const styledResources = getStyledResources(resources, events, slotMetrics, step, timeslots, accessors);

  return (
    <table className="scheduler" ref={schedulerRef}>
      <thead className="scheduler__head">
        <SchedulerViewTimeline
          resourceComponent={<span>Rooms</span>}
          timelineComponent={
            <SchedulerViewScrollArea
              horizontal
              hideScrollbar
              width={frameWidth}
              timelineWidth={timelineWidth}
              scrollRef={timelineRef}
              scrollbarSize={scrollbarSize}
            >
              <table>
                <colgroup>
                  {slots.map(slot =>
                    slot.map((minorSlot, index) => (
                      <col key={index} style={{ width: `${SLOT_WIDTH}px` }} />
                    )),
                  )}
                </colgroup>
                <tbody>
                  <tr>
                    {slots.map((slot, index) => (
                      <td key={index} colSpan={slot.length}>
                        {moment(slot[0]).format('ha')}
                      </td>
                    ))}
                  </tr>
                </tbody>
              </table>
            </SchedulerViewScrollArea>
          }
        />
      </thead>
      <tbody className="scheduler__body">
        <SchedulerViewTimeline
          resourceComponent={
            <SchedulerViewScrollArea
              vertical
              hideScrollbar
              height={timelineHeight}
              scrollRef={resourcesRef}
              scrollbarSize={scrollbarSize}
            >
              <table>
                <tbody>
                  {groups.map((group) => {
                    const isExpanded = isGroupExpanded(group.id);
                    const groupResources = resources.filter(
                      resource => resource.groupId === group.id,
                    );

                    return groupResources.length <= 0 ? null : (
                      <React.Fragment key={group.id}>
                        <tr className="scheduler__row scheduler__row--group">
                          <th>
                            <div className="scheduler__group-heading">
                              <span className="scheduler__group-text">{group.title}</span>
                              <GroupToggle group={group} />
                            </div>
                          </th>
                        </tr>
                        {isExpanded &&
                          groupResources.map(resource => (
                            <tr
                              key={resource.id}
                              className="scheduler__row scheduler__row--resource"
                              style={{ height: styledResources[resource.id].height }}
                            >
                              <td>{resource.title}</td>
                            </tr>
                          ))}
                      </React.Fragment>
                    );
                  })}
                </tbody>
              </table>
            </SchedulerViewScrollArea>
          }
          timelineComponent={
            <SchedulerViewScrollArea
              vertical
              horizontal
              overflow
              width={frameWidth}
              timelineWidth={timelineWidth}
              height={timelineHeight}
              scrollRef={scheduleRef}
              scrollbarSize={scrollbarSize}
            >
              <div className="rbc-scheduler-view">
                <div
                  className="scheduler__timeline-container rbc-time-view"
                  style={{
                    width: `${timelineWidth}px`,
                    minHeight: `${timelineHeight}px`,
                  }}
                >
                  <table className="scheduler__timeline">
                    <tbody>
                      {groups.map((group) => {
                        const isExpanded = isGroupExpanded(group.id);
                        const groupResources = resources.filter(
                          resource => resource.groupId === group.id,
                        );

                        return groupResources.length <= 0 ? null : (
                          <React.Fragment key={group.id}>
                            <tr
                              className="scheduler__row scheduler__row--group"
                              style={{ height: `${ROW_MIN_HEIGHT}px` }}
                            >
                              <td>
                                <div className="scheduler__row-inner">
                                  <SchedulerViewGroupHours
                                    {...props}
                                    group={group}
                                    slotMetrics={slotMetrics}
                                    height={ROW_MIN_HEIGHT}
                                  />
                                </div>
                              </td>
                            </tr>
                            {isExpanded &&
                              groupResources.map(resource => (
                                <tr
                                  key={resource.id}
                                  className="scheduler__row scheduler__row--event"
                                  style={{ height: styledResources[resource.id].height }}
                                >
                                  <td>
                                    <div className="scheduler__row-inner">
                                      <SchedulerViewEventsRow
                                        {...props}
                                        components={components}
                                        accessors={accessors}
                                        slotMetrics={slotMetrics}
                                        resourceId={resource.id}
                                        style={{ height: styledResources[resource.id].height }}
                                        styledEvents={styledResources[resource.id].styledEvents}
                                      />
                                      <SchedulerViewGroupHours
                                        {...props}
                                        group={group}
                                        slotMetrics={slotMetrics}
                                      />
                                    </div>
                                  </td>
                                </tr>
                              ))}
                          </React.Fragment>
                        );
                      })}
                    </tbody>
                  </table>
                  <div className="scheduler__bg">
                    <table>
                      <colgroup>
                        {slots.map(slot =>
                          slot.map((minorSlot, index) => (
                            <col key={index} style={{ width: `${SLOT_WIDTH}px` }} />
                          )),
                        )}
                      </colgroup>
                      <tbody>
                        <tr>
                          {slots.map(slot =>
                            slot.map((minorSlot, index) => (
                              <td
                                key={index}
                                className={`scheduler__bg--${index === 0 ? 'major' : 'minor'}`}
                                style={{ width: `${SLOT_WIDTH}px` }}
                              />
                            )),
                          )}
                        </tr>
                      </tbody>
                    </table>
                  </div>
                </div>
              </div>
            </SchedulerViewScrollArea>
          }
          width={timelineWidth}
        />
      </tbody>
    </table>
  );
};

SchedulerView.propTypes = {
  date: PropTypes.instanceOf(Date).isRequired,
  events: PropTypes.array.isRequired,
  resources: PropTypes.array.isRequired,
};

const Scheduler = props => (
  <CalendarProvider {...props}>
    <SchedulerView {...props} />
  </CalendarProvider>
);

Scheduler.title = (date, { localizer }) => localizer.format(date, 'dayHeaderFormat');

Scheduler.navigate = (date, action) => {
  switch (action) {
    case Navigate.PREVIOUS:
      return dates.add(date, -1, 'day');

    case Navigate.NEXT:
      return dates.add(date, 1, 'day');

    default:
      return date;
  }
};

export default Scheduler;
