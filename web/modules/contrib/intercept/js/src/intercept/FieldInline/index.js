import React from 'react';
import PropTypes from 'prop-types';

const FieldInline = (props) => {
  const { label, values, onClick } = props;

  if (values === null) {
    return (
      <div className="field field--inline">
        <strong className="field__label">{label}</strong>
      </div>
    );
  }

  const valueList = [].concat(values).map(
    (value) => {
      if (!value.name) {
        return null;
      }

      if (value.html) {
        return (<div
          key={value.id}
          className="field__option"
          dangerouslySetInnerHTML={value.html}
        />);
      }

      return (value.href ? (
        <a key={value.id} href={value.href} className="field__option" onClick={onClick}>
          {value.name}
        </a>
      ) : (
        <span key={value.id} className="field__option" onClick={onClick}>
          {value.name}
        </span>
      ));
    }
  );

  return (
    <div className="field field--inline">
      <strong className="field__label">{label}: </strong>
      <span className="field__items">
        {valueList.reduce((prev, curr) => (!prev ? [curr] : [prev, ', ', curr]))}
      </span>
    </div>
  );
};

FieldInline.propTypes = {
  label: PropTypes.string.isRequired,
  values: PropTypes.oneOfType([PropTypes.arrayOf(Object), PropTypes.object]),
  onClick: PropTypes.func,
};

FieldInline.defaultProps = {
  values: null,
  onClick: null,
};

export default FieldInline;
