import React from 'react';
import PropTypes from 'prop-types';
import CurrentFilter from '../CurrentFilter';

const CurrentFilters = (props) => {
  const { onChange, filters } = props;

  return (
    <div className="current-filters__wrapper">
      {filters.map(data => (<CurrentFilter
        key={data.key}
        label={data.label}
        onChange={onChange}
        className="current-filters"
        values={data.values}
        filter={data}
      />
      ))}
    </div>
  );
}

CurrentFilters.propTypes = {
  filters: PropTypes.arrayOf(Object).isRequired,
  onChange: PropTypes.func.isRequired,
};

export default CurrentFilters;
