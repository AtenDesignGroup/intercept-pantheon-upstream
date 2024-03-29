import React from 'react';
import connectQueryParams from './connectQueryParams';
import RoomReservationScheduler from './components/RoomReservationScheduler';
import { RoomsProvider } from './context/RoomsContext';
import { GroupsProvider } from './context/GroupsContext';

const App = props => (
  <RoomsProvider view={props.viewsId} display={props.displayId}>
    <GroupsProvider>
      <RoomReservationScheduler {...props} />
    </GroupsProvider>
  </RoomsProvider>
);

export default connectQueryParams(App);
