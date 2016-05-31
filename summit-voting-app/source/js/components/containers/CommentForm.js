import React from 'react';
import {connect} from 'react-redux';
import {postComment} from '../../action-creators';

class CommentForm extends React.Component {

	constructor (props) {
		super(props);
		const {user_comment} = this.props.presentation;
		this.state = {
			comment: user_comment ? user_comment.comment : null
		};
		this.updateComment = this.updateComment.bind(this);
		this.handleSubmit = this.handleSubmit.bind(this);
	}

	updateComment(e) {
		this.setState({
			comment: e.target.value
		})
	}

	handleSubmit(e) {
		e.preventDefault();
		this.props.onCreateComment(
			this.props.presentation,
			this.state.comment
		);
	}

	render () {		
		return (
			<form onSubmit={this.handleSubmit}>
				<textarea className="form-control" value={this.state.comment} onChange={this.updateComment}></textarea>
				<button className="btn block-btn btn-primary" type="submit">Add comment</button>
			</form>
		);
	}

}

export default connect(
	state => ({
		presentation: state.presentations.selectedPresentation	
	}),
	dispatch => ({
		onCreateComment(presentation, comment) {
			dispatch(postComment(presentation.id, comment));
		}
	})
)(CommentForm);