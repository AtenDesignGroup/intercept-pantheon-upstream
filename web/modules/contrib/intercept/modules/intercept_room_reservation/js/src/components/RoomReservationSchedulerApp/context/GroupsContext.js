/**
 *  Context: Groups
 */

import React, { useState } from 'react';
import PropTypes from 'prop-types';

// Create a Context
const GroupsContext = React.createContext();

export const GroupsProvider = ({ children }) => {
  const [groups, setGroups] = useState([]);
  const [collapsedGroups, setCollapsedGroups] = useState([]);

  const collapseAllGroups = () => {
    setCollapsedGroups([...groups.map(group => group.id)]);
  };

  const collapseGroup = (id) => {
    setCollapsedGroups([id, ...collapsedGroups]);
  };

  const expandGroup = (id) => {
    setCollapsedGroups(collapsedGroups.filter(group => group !== id));
  };

  const expandAllGroups = () => {
    setCollapsedGroups([]);
  };

  const isGroupCollapsed = id => collapsedGroups.indexOf(id) >= 0;

  const isGroupExpanded = id => collapsedGroups.indexOf(id) < 0;

  const value = {
    groups,
    collapseAllGroups,
    collapseGroup,
    expandAllGroups,
    expandGroup,
    isGroupCollapsed,
    isGroupExpanded,
    setGroups,
  };

  return (
    <GroupsContext.Provider value={value}>
      {children}
    </GroupsContext.Provider>
  );
};

GroupsProvider.propTypes = {
  children: PropTypes.element.isRequired,
};

export default GroupsContext;
