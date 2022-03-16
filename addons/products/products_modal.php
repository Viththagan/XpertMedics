<?php
	echo '<div class="modal fade category" tabindex="-1" role="dialog" aria-labelledby="mySmallModalLabel" aria-hidden="true">
		 <div class="modal-dialog modal-dialog-centered">
			<div class="modal-content">
				<div class="modal-body">
					<button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
					<div class="form-group">
						<label>Category</label>
						<div><select class="form-control select" id="category" required>
							<option>Select</option>';
							$category=mysqli_query($con,"SELECT * FROM product_category WHERE level='1' ORDER BY title ASC");
							while($row=mysqli_fetch_assoc($category))echo '<option value="'.$row['id'].'">'.$row['title'].'</option>';
						echo '</select>
						</div>
					</div>
				</div>
			</div>
		</div>
    </div>';
	echo '<div class="modal fade brand" tabindex="-1" role="dialog" aria-labelledby="mySmallModalLabel" aria-hidden="true">
		 <div class="modal-dialog modal-dialog-centered">
			<div class="modal-content">
				<div class="modal-body">
					<button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
					<div class="form-group">
						<label>Brand</label>
						<div><select class="form-control select" id="brand" required>
							<option>Select</option>';
							$result=mysqli_query($con,"SELECT * FROM product_brand ORDER BY title ASC");
							while($row=mysqli_fetch_assoc($result))echo '<option value="'.$row['id'].'">'.$row['title'].'</option>';
						echo '</select>
						</div>
					</div>
				</div>
			</div>
		</div>
    </div>';
	echo '<div class="modal fade company" tabindex="-1" role="dialog" aria-labelledby="mySmallModalLabel" aria-hidden="true">
		 <div class="modal-dialog modal-dialog-centered">
			<div class="modal-content">
				<div class="modal-body">
					<button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
					<div class="form-group">
						<label>Company</label>
						<div><select class="form-control select" id="company" required>
							<option>Select</option>';
							$result=mysqli_query($con,"SELECT * FROM products_company");
							while($row=mysqli_fetch_assoc($result))echo '<option value="'.$row['id'].'">'.$row['title'].'</option>';
						echo '</select>
						</div>
					</div>
				</div>
			</div>
		</div>
    </div>';
	echo '<div class="modal fade weighted" tabindex="-1" role="dialog" aria-labelledby="mySmallModalLabel" aria-hidden="true">
		 <div class="modal-dialog modal-dialog-centered">
			<div class="modal-content">
				<div class="modal-body">
					<button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
					<div class="form-group">
						<label>Type</label>
						<div><select class="form-control select" id="weighted" required>
							<option>Select</option>';
							foreach($product['type'] as $id=>$title)echo '<option value="'.$id.'">'.$title.'</option>';
						echo '</select>
						</div>
					</div>
				</div>
			</div>
		</div>
    </div>';
	echo '<div class="modal fade discount" tabindex="-1" role="dialog" aria-labelledby="mySmallModalLabel" aria-hidden="true">
		 <div class="modal-dialog modal-dialog-centered">
			<div class="modal-content">
				<div class="modal-body">
					<button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
					<div class="form-group">
						<label>Discount</label>
						<div><select class="form-control select" id="discount" required>
							<option>Select</option>';
							foreach($product['discount'] as $id=>$title)echo '<option value="'.$id.'">'.$title.'</option>';
						echo '</select>
						</div>
					</div>
				</div>
			</div>
		</div>
    </div>';
	echo '<div class="modal fade price" tabindex="-1" role="dialog" aria-labelledby="mySmallModalLabel" aria-hidden="true">
		 <div class="modal-dialog modal-dialog-centered">
			<div class="modal-content">
				<div class="modal-body">
					<button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
					<div class="form-group">
						<label>Price</label>
						<div><select class="form-control select" id="price" required>
							<option>Select</option>';
							foreach($product['price'] as $id=>$title)echo '<option value="'.$id.'">'.$title.'</option>';
						echo '</select>
						</div>
					</div>
				</div>
			</div>
		</div>
    </div>';
	echo '<div class="modal fade status" tabindex="-1" role="dialog" aria-labelledby="mySmallModalLabel" aria-hidden="true">
		 <div class="modal-dialog modal-dialog-centered">
			<div class="modal-content">
				<div class="modal-body">
					<button type="button" class="close" data-dismiss="modal" aria-hidden="true">×</button>
					<div class="form-group">
						<label>Status</label>
						<div><select class="form-control select" id="status" required>
							<option>Select</option>';
							foreach($product['status'] as $id=>$title)echo '<option value="'.$id.'">'.$title.'</option>';
						echo '</select>
						</div>
					</div>
				</div>
			</div>
		</div>
    </div>';
?>