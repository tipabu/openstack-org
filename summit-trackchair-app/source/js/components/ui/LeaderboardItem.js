import React from 'react';

class LeaderboardItem extends React.Component {
	
	constructor(props) {
		super(props);
		this.handleUp = this.handleUp.bind(this);
		this.handleDown = this.handleDown.bind(this);
	}

	handleUp(e) {
		e.preventDefault();
		this.props.onUp && this.props.onUp(this.props.eventKey);
	}

	handleDown(e) {
		e.preventDefault();
		this.props.onDown && this.props.onDown(this.props.eventKey);
	}

	render() {
		const {title, notes, rank} = this.props;
		return (
		<div className="vote-item">
			<div className="row">
				<div className="col-md-10">
					<div className="vote-actions">
					{this.props.canUp &&
						<a href="#" onClick={this.handleUp}>
							<i className="fa fa-chevron-up"> </i>
						</a>
					}					
						<div>&nbsp;</div>
					{this.props.canDown &&
						<a href="#" onClick={this.handleDown}>
							<i className="fa fa-chevron-down"> </i>
						</a>
					}
					</div>
					<a href="#" className="vote-title">
						{title}
					</a>
					<div className="vote-info">
						<i className="fa fa-comments-o"></i> {notes}
					</div>
				</div>
				<div className="col-md-2 ">
					<div className="vote-icon">
						#{rank}
					</div>
				</div>

			</div>
		</div>
		);
	}	
}

LeaderboardItem.propTypes = {
	title: React.PropTypes.any,
	notes: React.PropTypes.any,
	rank: React.PropTypes.any,
	onUp: React.PropTypes.func,
	onDown: React.PropTypes.func,
	eventKey: React.PropTypes.any,
	canUp: React.PropTypes.bool,
	canDown: React.PropTypes.bool
};

LeaderboardItem.defaultProps = {
	canUp: true,
	canDown: true
};

export default LeaderboardItem;