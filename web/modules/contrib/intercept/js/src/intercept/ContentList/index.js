import React from 'react';
import PropTypes from 'prop-types';

class ContentList extends React.PureComponent {
  constructor(props) {
    super(props);
    this.listRef = React.createRef();
  }

  render() {
    const { items, heading, modifiers, page } = this.props;

    const classes = ['content-list'].concat(modifiers.map(modifier => `content-list--${modifier}`)).join(' ');

    const title = heading && <h3 className="content-list__heading">{heading}</h3>;

    const list = items.map(item => <li key={item.key} className="content-list__item">{item.node}</li>);

    return (
      <div className={classes} ref={'list'} data-page-num={page}>
        {title}
        <ul className="content-list__list">
          {list}
        </ul>
      </div>
    );
  }
}

ContentList.propTypes = {
  items: PropTypes.arrayOf(PropTypes.object).isRequired,
  modifiers: PropTypes.arrayOf(PropTypes.string),
  heading: PropTypes.node,
  page: PropTypes.number,
};

ContentList.defaultProps = {
  heading: null,
  modifiers: [],
  page: null,
};

export default ContentList;
