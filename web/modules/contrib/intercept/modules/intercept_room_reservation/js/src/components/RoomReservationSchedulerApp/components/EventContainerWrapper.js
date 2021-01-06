import PropTypes from 'prop-types';
import React from 'react';
import * as dates from 'react-big-calendar/lib/utils/dates';
import { notify } from 'react-big-calendar/lib/utils/helpers';
import { findDOMNode } from 'react-dom';

import Selection, {
  getBoundsForNode,
  getEventNodeFromPoint,
  isEvent,
} from 'react-big-calendar/lib/Selection';
import TimeGridEvent from './TimeGridEvent';
import { dragAccessors } from 'react-big-calendar/lib/addons/dragAndDrop/common';
import NoopWrapper from 'react-big-calendar/lib/NoopWrapper';

const pointInColumn = (bounds, { x, y }) => {
  const { left, bottom, top } = bounds;
  return y < bottom && y > top && x > left;
};

// const propTypes = {};

class EventContainerWrapper extends React.Component {
  static propTypes = {
    accessors: PropTypes.object.isRequired,
    components: PropTypes.object.isRequired,
    getters: PropTypes.object.isRequired,
    localizer: PropTypes.object.isRequired,
    selectable: PropTypes.string,
    slotMetrics: PropTypes.object.isRequired,
    resource: PropTypes.any,
  }

  static contextTypes = {
    draggable: PropTypes.shape({
      onStart: PropTypes.func,
      onEnd: PropTypes.func,
      onDropFromOutside: PropTypes.func,
      onBeginAction: PropTypes.func,
      dragAndDropAction: PropTypes.object,
      dragFromOutsideItem: PropTypes.func,
    }),
  }

  constructor(...args) {
    super(...args);
    this.state = {
      selecting: false,
      timeIndicatorPosition: null,
    };
  }

  componentDidMount() {
    this._selectable();
  }

  componentWillUnmount() {
    this._teardownSelectable();
  }

  reset() {
    if (this.state.event) {
      this.setState({ event: null, left: null, width: null });
    }
    if (this.state.selecting) {
      this.setState({ selecting: false });
    }
  }

  update(event, { startDate, endDate, left, width }) {
    const { event: lastEvent } = this.state;
    if (
      lastEvent &&
      startDate === lastEvent.start &&
      endDate === lastEvent.end
    ) {
      return;
    }

    this.setState({
      left,
      width,
      event: { ...event, start: startDate, end: endDate },
    });
  }

  handleMove = (point, boundaryBox) => {
    const { event } = this.context.draggable.dragAndDropAction;
    const { accessors, slotMetrics } = this.props;

    if (!pointInColumn(boundaryBox, point)) {
      this.reset();
      return;
    }

    const currentSlot = slotMetrics.closestSlotFromPoint(
      { y: point.y - this.eventOffsetTop, x: point.x },
      boundaryBox,
    );

    const eventStart = accessors.start(event);
    const eventEnd = accessors.end(event);
    const end = dates.add(
      currentSlot,
      dates.diff(eventStart, eventEnd, 'minutes'),
      'minutes',
    );

    this.update(event, slotMetrics.getRange(currentSlot, end, false, true));
  }

  handleResize(point, boundaryBox) {
    let start;
    let end;
    const { accessors, slotMetrics } = this.props;
    const { event, direction } = this.context.draggable.dragAndDropAction;

    const currentSlot = slotMetrics.closestSlotFromPoint(point, boundaryBox);

    if (direction === 'LEFT') {
      end = accessors.end(event);
      start = dates.min(currentSlot, slotMetrics.closestSlotFromDate(end, -1));
    }
    else if (direction === 'RIGHT') {
      start = accessors.start(event);
      end = dates.max(currentSlot, slotMetrics.closestSlotFromDate(start));
    }

    this.update(event, slotMetrics.getRange(start, end));
  }

  handleDropFromOutside = (point, boundaryBox) => {
    const { slotMetrics, resource } = this.props;

    const start = slotMetrics.closestSlotFromPoint(
      { y: point.y, x: point.x },
      boundaryBox,
    );

    this.context.draggable.onDropFromOutside({
      start,
      end: slotMetrics.nextSlot(start),
      allDay: false,
      resource,
    });
  }

  _selectable = () => {
    const node = findDOMNode(this);

    const selector = (this._selector = new Selection(() =>
      node.closest('.rbc-time-view'),
    ));

    const selectionState = (point) => {
      let currentSlot = this.props.slotMetrics.closestSlotFromPoint(
        point,
        getBoundsForNode(node),
      );

      if (!this.state.selecting) {
        this._initialSlot = currentSlot;
      }

      let initialSlot = this._initialSlot;
      if (dates.lte(initialSlot, currentSlot)) {
        currentSlot = this.props.slotMetrics.nextSlot(currentSlot);
      }
      else if (dates.gt(initialSlot, currentSlot)) {
        initialSlot = this.props.slotMetrics.nextSlot(initialSlot);
      }

      const selectRange = this.props.slotMetrics.getRange(
        dates.min(initialSlot, currentSlot),
        dates.max(initialSlot, currentSlot),
      );

      return {
        ...selectRange,
        selecting: true,

        left: `${selectRange.left}%`,
        width: `${selectRange.width}%`,
      };
    };

    const maybeSelect = (box) => {
      const onSelecting = this.props.onSelecting;
      const current = this.state || {};
      const state = selectionState(box);
      const { startDate: start, endDate: end } = state;

      if (onSelecting) {
        if (
          (dates.eq(current.startDate, start, 'minutes') &&
            dates.eq(current.endDate, end, 'minutes')) ||
          onSelecting({ start, end, resourceId: this.props.resource }) === false
        ) {
          return;
        }
      }

      if (
        this.state.start !== state.start ||
        this.state.end !== state.end ||
        this.state.selecting !== state.selecting
      ) {
        this.setState(state);
      }
    };

    const selectorClicksHandler = (box, actionType) => {
      if (!isEvent(findDOMNode(this), box)) {
        const { startDate, endDate } = selectionState(box);
        this._selectSlot({
          startDate,
          endDate,
          action: actionType,
          box,
        });
      }
      this.setState({ selecting: false });
    };

    // selector.on('selectStart', maybeSelect);

    selector.on('beforeSelect', (point) => {
      const { dragAndDropAction } = this.context.draggable;

      // Handle selecting empty area
      if (!dragAndDropAction.action) {
        if (this.props.selectable !== 'ignoreEvents') return;

        return !isEvent(findDOMNode(this), point)
          && pointInColumn(getBoundsForNode(node), point);
      }

      if (dragAndDropAction.action === 'resize') {
        return pointInColumn(getBoundsForNode(node), point);
      }

      const eventNode = getEventNodeFromPoint(node, point);
      if (!eventNode) return false;

      this.eventOffsetLeft = point.x - getBoundsForNode(eventNode).left;
    });

    selector.on('selecting', (box) => {
      const { dragAndDropAction } = this.context.draggable;

      // Handle selecting empty area
      if (!dragAndDropAction.action) {
        maybeSelect(box);
        return;
      }

      const bounds = getBoundsForNode(node);

      if (dragAndDropAction.action === 'move') this.handleMove(box, bounds);
      if (dragAndDropAction.action === 'resize') this.handleResize(box, bounds);
    });

    selector.on('dropFromOutside', (point) => {
      if (!this.context.draggable.onDropFromOutside) return;
      const bounds = getBoundsForNode(node);

      if (!pointInColumn(bounds, point)) return;

      this.handleDropFromOutside(point, bounds);
    });

    selector.on('dragOver', (point) => {
      if (!this.context.draggable.dragFromOutsideItem) return;

      const bounds = getBoundsForNode(node);

      this.handleDropFromOutside(point, bounds);
    });

    selector.on('selectStart', (box) => {
      const { dragAndDropAction } = this.context.draggable;

      // Handle selecting empty area
      if (!dragAndDropAction.action) {
        maybeSelect(box);
        return;
      }

      this.context.draggable.onStart();
    });

    selector.on('select', (point) => {
      if (this.state.selecting) {
        this._selectSlot({ ...this.state, action: 'select', point });
        this.setState({ selecting: false });
      }

      const bounds = getBoundsForNode(node);

      if (!this.state.event || !pointInColumn(bounds, point)) return;
      this.handleInteractionEnd();
    });

    selector.on('click', (box) => {
      selectorClicksHandler(box, 'click');
      this.context.draggable.onEnd(null);
    });

    selector.on('doubleClick', box => selectorClicksHandler(box, 'doubleClick'));

    selector.on('reset', () => {
      this.reset();
      this.context.draggable.onEnd(null);
    });
  }

  handleInteractionEnd = () => {
    const { resource } = this.props;
    const { event } = this.state;

    this.reset();

    this.context.draggable.onEnd({
      start: event.start,
      end: event.end,
      resourceId: resource,
    });
  }

  _teardownSelectable = () => {
    if (!this._selector) return;
    this._selector.teardown();
    this._selector = null;
  }

  _selectSlot = ({ startDate, endDate, action, bounds, box }) => {
    let current = startDate;
    const slots = [];

    while (dates.lte(current, endDate)) {
      slots.push(current);
      current = dates.add(current, this.props.step, 'minutes');
    }

    notify(this.props.onSelectSlot, {
      slots,
      start: startDate,
      end: endDate,
      resourceId: this.props.resource,
      action,
      bounds,
      box,
    });
  }

  /**
   * Gets the formatted event height CSS property.
   *
   * @returns {string}
   *   The height property value in px.
   */
  eventHeight = () => `${this.props.eventHeight}px`;

  /**
   * Renders a placeholder selection event.
   * Used when selecting an empty time slot.
   */
  renderPlaceholderEvent = () => {
    const { left, width, startDate, endDate } = this.state;
    const selectDates = { start: startDate, end: endDate };

    const {
      localizer,
    } = this.props;

    return (<div className="rbc-slot-selection" style={{ left, width, height: this.eventHeight() }}>
      <span>{localizer.format(selectDates, 'selectRangeFormat')}</span>
    </div>);
  }

  /**
   * Renders the currently selected event.
   * Used when moving or resizing an existing event.
   */
  renderSelectedEvent = () => {
    const {
      accessors,
      components,
      getters,
      slotMetrics,
      localizer,
    } = this.props;

    const { event, left, width } = this.state;
    const { start, end } = event;

    let label;
    let format = 'eventTimeRangeFormat';

    const startsBeforeDay = slotMetrics.startsBeforeDay(start);
    const startsAfterDay = slotMetrics.startsAfterDay(end);

    if (startsBeforeDay) format = 'eventTimeRangeEndFormat';
    else if (startsAfterDay) format = 'eventTimeRangeStartFormat';

    if (startsBeforeDay && startsAfterDay) label = localizer.messages.allDay;
    else label = localizer.format({ start, end }, format);

    return (<TimeGridEvent
      event={event}
      label={label}
      className="rbc-addons-dnd-drag-preview"
      style={{ left, width, height: this.eventHeight() }}
      getters={getters}
      components={{ ...components, eventWrapper: NoopWrapper }}
      accessors={{ ...accessors, ...dragAccessors }}
      continuesEarlier={startsBeforeDay}
      continuesLater={startsAfterDay}
    />);
  }

  render() {
    const {
      children,
    } = this.props;

    const { event, selecting } = this.state;

    if (!event && !selecting) return children;

    const events = children.props.children;

    return React.cloneElement(children, {
      children: (
        <React.Fragment>
          {events}
          {event && this.renderSelectedEvent()}
          {selecting && this.renderPlaceholderEvent()}
        </React.Fragment>
      ),
    });
  }
}

// EventContainerWrapper.propTypes = propTypes;

export default EventContainerWrapper;
