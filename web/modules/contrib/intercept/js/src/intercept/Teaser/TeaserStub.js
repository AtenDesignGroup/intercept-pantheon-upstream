import React from 'react';
import PropTypes from 'prop-types';

const TeaserStub = (props) => {
  const { modifiers } = props;

  const classes = `clearfix teaser ${[]
    .concat(modifiers, 'stub')
    .map(mod => `teaser--${mod}`)
    .join(' ')}`;

  const date = {
    month: '',
    date: '',
    time: '',
  };

  return (
    <article className={classes} aria-hidden>
      <div className="teaser__image">
        <div />
        {date && (
          <div className="teaser__date-wrapper">
            <p className="teaser__date">
              <span className="teaser__date-month">{date.month}</span>
              <span className="teaser__date-date">{date.date}</span>
              <span className="teaser__date-time">{date.time}</span>
            </p>
          </div>
        )}
      </div>
      <div className="teaser__main clearfix">
        <div className="teaser__content">
          <span className="teaser__type">{''}</span>
          <h3 className="teaser__title">{''}</h3>
          <div className="teaser__description" />
        </div>
        {<div className="teaser__footer" />}
      </div>
    </article>
  );
};

TeaserStub.propTypes = {
  // uuid: PropTypes.string,
  // date: PropTypes.object,
  // description: PropTypes.string,
  // footer: PropTypes.func,
  // image: PropTypes.string,
  modifiers: PropTypes.arrayOf(PropTypes.string),
  // supertitle: PropTypes.string,
  // subtitle: PropTypes.string,
  // tags: PropTypes.arrayOf(PropTypes.element),
  // title: PropTypes.string.isRequired,
  // titleUrl: PropTypes.string,
  // type: PropTypes.string,
};

TeaserStub.defaultProps = {
  // date: null,
  // description: null,
  modifiers: [],
  // footer: null,
  // image: null,
  // subtitle: null,
  // supertitle: null,
  // titleUrl: null,
  // tags: null,
  // type: null,
  // uuid: null,
};

export default TeaserStub;
