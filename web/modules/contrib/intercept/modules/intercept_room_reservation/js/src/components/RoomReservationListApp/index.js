// React
import React from 'react';
import PropTypes from 'prop-types';

// Redux
import { connect } from 'react-redux';

// Lodash
import debounce from 'lodash/debounce';

// Intercept
import interceptClient from 'interceptClient';

// Intercept Components
import ContentList from 'intercept/ContentList';
import ButtonActions from 'intercept/ButtonActions';
import DialogConfirm from 'intercept/Dialog/DialogConfirm';

// Local Components
import ReservationTeaser from './../ReservationTeaser';

const { constants, api, select, utils } = interceptClient;
const c = constants;

const userId = utils.getUserUuid();

class AccountRoomReservations extends React.Component {
  constructor(props) {
    super(props);
    this.state = {
      open: false,
    };
    this.doFetch = debounce(this.doFetch, 500).bind(this);
  }

  componentDidMount() {
    this.doFetch();
  }

  doFetch() {
    this.props.fetchReservations({
      filters: {
        user: {
          path: 'field_user.id',
          value: userId,
        },
      },
      include: [
        'field_room',
        'field_room.image_primary',
        'field_room.image_primary.field_media_image',
      ],
      replace: true,
      headers: {
        'X-Consumer-ID': interceptClient.consumer,
      },
    });
  }

  doConfirmAction(Param) {
    this.setState({
      open: true,
      text: 'Confirm cancel',
    });
  }

  render() {
    const { reservations } = this.props;

    const teasers = items =>
      items.map(item => ({
        key: item.data.id,
        node: (
          <ReservationTeaser
            id={item.data.id}
            actions={
              <ButtonActions
                id={item.data.id}
                // actions={["cancel", "approve", "deny"]}
                actions={['cancel']}
              />
            }
            className="room-teaser"
          />
        ),
      }));

    const list =
      Object.values(reservations).length > 0 ? (
        <div>
          <ContentList items={teasers(Object.values(reservations))} key={0} />
          <DialogConfirm
            open={this.state.open}
            onClose={this.onDialogClose}
            onConfirm={this.onDialogConfirm}
            onCancel={this.onDialogCancel}
            text={this.state.text}
          />
        </div>
      ) : (
        <p key={0}>No rooms have been loaded.</p>
      );

    return <div className="rooms-list">{list}</div>;
  }
}

AccountRoomReservations.propTypes = {
  fetchReservations: PropTypes.func.isRequired,
  reservations: PropTypes.object,
};

AccountRoomReservations.defaultProps = {
  reservations: {},
};

const mapStateToProps = state => ({
  reservations: select.roomReservations(state),
  reservationsLoading: select.recordsAreLoading(c.TYPE_ROOM_RESERVATION)(state),
});

const mapDispatchToProps = (dispatch, ownProps) => ({
  fetchReservations: (options) => {
    dispatch(api[c.TYPE_ROOM_RESERVATION].fetchAll(options));
  },
});

export default connect(mapStateToProps, mapDispatchToProps)(AccountRoomReservations);
