<div class="modal fade" id="update-post-modal" tabindex="-1" role="dialog" aria-labelledby="update-post">
	<div class="modal-dialog modal-lg" role="document">
		<div class="modal-content">
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
				<h4 class="modal-title" id="update-post">Updating {{Post Title}}</h4>
			</div>
			<div class="modal-body">
				<form action="">
					<div class="form-group">
						<label for="postTitle" class="sr-only">Title</label>
						<input type="text" class="form-control" id="postTitle" name="postTitle" placeholder="update title">
					</div>
					<div class="form-group">
						<label for="postContent" class="sr-only">Content</label>
						<textarea class="form-control" name="postContent" id="postContent" cols="30" rows="10" placeholder="update content"></textarea>
					</div>
					<button class="btn btn-danger" type="button">Update!</button>
					<button class="btn btn-default" type="reset" data-dismiss="modal">Cancel</button>
				</form>
			</div>
		</div>
	</div>
</div>