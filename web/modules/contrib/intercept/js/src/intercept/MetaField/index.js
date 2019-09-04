import React from 'react';
import PropTypes from 'prop-types';

const MetaField = (props) => {
  const { label, values, onClick } = props;
  const valueList = [].concat(values).map(
    value =>
      (value.href ? (
        <a key={value.id} href={value.href} className="meta-field__option">
          {value.name}
        </a>
      ) : (
        <span key={value.id} className="meta-field__option">
          {value.name}
        </span>
      )),
  );
  return (
    <div className="field">
      <strong className="field__label">{label}: </strong>
      <span className="field__items">
        {valueList.reduce((prev, curr) => (!prev ? [curr] : [prev, ', ', curr]))}
      </span>
    </div>
  );
};

MetaField.propTypes = {
  label: PropTypes.string.isRequired,
  values: PropTypes.oneOfType([PropTypes.arrayOf(Object), PropTypes.object]).isRequired,
  onClick: PropTypes.func,
};

export default MetaField;
