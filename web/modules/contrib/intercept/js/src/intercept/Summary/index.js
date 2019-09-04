import React from 'react';
import PropTypes from 'prop-types';

const SummaryImage = (props) => {
  const img = <img src={props.image} alt={props.alt} />;

  return (
    <div className="summary__image">
      {props.url && props.image ? (
        <a href={props.url} className="summary__image-link" aria-hidden="true">
          {img}
        </a>
      ) : (
        img
      )}
    </div>
  );
};

SummaryImage.propTypes = {
  image: PropTypes.string.isRequired,
  alt: PropTypes.string.isRequired,
  url: PropTypes.string,
};

SummaryImage.defaultProps = {
  url: null,
};

const SummaryDate = props => (
  <div className="summary__dateline">
    <p className="summary__dateline-date">{props.date.date}</p>
    <p className="summary__dateline-time">{props.date.time}</p>
  </div>
);

SummaryDate.propTypes = {
  date: PropTypes.object.isRequired,
};

const Summary = (props) => {
  const {
    date,
    body,
    footer,
    image,
    label,
    modifiers,
    supertitle,
    subtitle,
    title,
    titleUrl,
    uuid,
  } = props;
  const classes = `summary ${modifiers.map(mod => `summary--${mod}`).join(' ')}`;

  function createMarkup(value) {
    return { __html: value };
  }

  return (
    <article uuid={uuid} className={classes}>
      {image && <SummaryImage image={image} url={titleUrl} alt={title} />}
      <div className="summary__content">
        <header className="summary__header">
          {supertitle && <p className="summary__supertitle">{supertitle}</p>}
          <h3 className="summary__title">
            {titleUrl ? (
              <a href={titleUrl} className="summary__title-link">
                {title}
              </a>
            ) : (
              title
            )}
          </h3>
          {date && <SummaryDate date={date} />}
          {subtitle && <p className="summary__subtitle">{subtitle}</p>}
        </header>

        {body && <div className="summary__body" dangerouslySetInnerHTML={createMarkup(body)} />}
        {label && <p className="summary__label">{label}</p>}
        {props.children}
      </div>
      {footer && <div className="summary__footer">{footer(props)}</div>}
    </article>
  );
};

Summary.propTypes = {
  uuid: PropTypes.string,
  date: PropTypes.object,
  body: PropTypes.string,
  footer: PropTypes.func,
  image: PropTypes.string,
  label: PropTypes.string,
  modifiers: PropTypes.arrayOf(PropTypes.string),
  supertitle: PropTypes.string,
  subtitle: PropTypes.string,
  title: PropTypes.string.isRequired,
  titleUrl: PropTypes.string,
};

Summary.defaultProps = {
  date: null,
  body: null,
  modifiers: [],
  footer: null,
  image: null,
  label: null,
  subtitle: null,
  supertitle: null,
  titleUrl: null,
  uuid: null,
};

export default Summary;
