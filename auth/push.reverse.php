<?php 

	// push reverse 

	require_once ("../db_connection/conn.php");

	if (!admin_is_logged_in()) {
        admn_login_redirect();
    }

	if (!admin_has_permission('supervisor')) {
		$_SESSION['flash_error'] = "What do you want?";
		redirect(goBack());
	}

	include ("../includes/header.inc.php");
    include ("../includes/aside.inc.php");
    include ("../includes/left.nav.inc.php");
    include ("../includes/top.nav.inc.php");

	if (isset($_GET['data']) && !empty($_GET['data'])) {
		$id = sanitize($_GET['data']);

		// find push id
		$find = $conn->query("SELECT * FROM jspence_pushes WHERE push_id = '" . $id . "' LIMIT 1")->fetchAll();
		if (count($find) > 0) {
			if (isset($_POST['admin_pin'])) {
				if (!empty($_POST['admin_pin']) || $_POST['admin_pin'] != '') {

					$reason = ((isset($_POST['reason']) && !empty($_POST['reason'])) ? sanitize($_POST['reason']) : '');
					$pin = ((isset($_POST['admin_pin']) && !empty($_POST['admin_pin'])) ? sanitize($_POST['admin_pin']) : '');
					if ($pin == $admin_data['admin_pin']) {
						$query = "
							UPDATE jspence_pushes 
							SET push_status = ?, push_reverse_reason = ? 
							WHERE push_id = ?
						";
						$statement = $conn->prepare($query);
						$result = $statement->execute([1, $reason, $id]);
						if ($result) {
							if (admin_has_permission('salesperson')) {
								$sql = "
									UPDATE jspence_daily 
									SET daily_balance = daily_balance + '" . $find[0]['push_amount'] . "'
									WHERE daily_id = ? 
								";
								$statement = $conn->prepare($sql);
								$statement->execute([$find[0]['push_daily']]);
							}

							$_SESSION['flash_success'] = 'Push reversed successfully!';
							redirect(PROOT . 'account/pushes');
						}
					} else {
						$_SESSION['flash_error'] = 'Invalid admin pin provided!';
						redirect(PROOT . 'account/pushes');
					}
					
				} else {
					$_SESSION['flash_error'] = 'Provide admin pin to verify this reverse push!';
					redirect(PROOT . 'account/pushes');
				}
			}
		} else {
			$_SESSION['flash_error'] = 'Unknow push id provided!';
			redirect(PROOT . 'account/pushes');
		}
	} else {
		$_SESSION['flash_error'] = "What do you want?";
		redirect(PROOT . 'account/pushes');
	}
?>
	<div class="container-lg">
        <!-- Page header -->
        <div class="row align-items-center mb-7">
            <div class="col-auto">
                <!-- Avatar -->
                <div class="avatar avatar-xl rounded text-warning">
                <i class="fs-2" data-duoicon="menu"></i>
                </div>
            </div>
            <div class="col">
                <!-- Breadcrumb -->
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb mb-2">
                        <li class="breadcrumb-item"><a class="text-body-secondary" href="javascript:;">Market</a></li>
                        <li class="breadcrumb-item active" aria-current="page">Reverse pushes</li>
                    </ol>
                </nav>

                <!-- Heading -->
                <h1 class="fs-4 mb-0">Push</h1>
            </div>
            <div class="col-12 col-sm-auto mt-4 mt-sm-0">
                <!-- Action -->
                <a class="btn btn-light d-block" href="<?= PROOT; ?>account/pushes"> Cancel reverse push</a>
            </div>
        </div>

		<!-- Page content -->
		<div class="row">
        	<div class="col-12 col-lg-3">
				<!-- Nav -->
				<nav class="nav nav-pills position-sticky flex-column mb-8" id="accountNav" style="top: 32px">
					<a class="nav-link active" href="javascript:;">Reverse push</a>
					<a class="nav-link" href="<?= PROOT; ?>account/pushes">Cancel</a>
				</nav>
          	</div>
          	<div class="col-12 col-lg-9" data-bs-spy="scroll" data-bs-target="#accountNav" data-bs-smooth-scroll="true" tabindex="0">
				<!-- General -->
				<section class="card bg-body-tertiary border-transparent card-line mb-5" id="general">
					<div class="card-body">
						<h2 class="fs-5 mb-1">Reverse <?= money($find[0]['push_amount']); ?></h2>
						<p class="text-body-secondary">You are to reverse a push you made, provide pin to complete the reverse.</p>
						<form id="reverseForm" method="POST">
							<div class="mb-3">
								<label class="form-label" for="fullName">Reason</label>
								<textarea class="form-control bg-body" type="reason" id="reson" maxlength="300"></textarea>
							</div>
							<div class="mb-4">
								<label class="form-label" for="fullName">Enter PIN</label>
								<input class="form-control bg-body" type="number" id="admin_pin" name="admin_id" autocomplete="off" inputmode="numeric" data-maxlength="4" oninput="this.value=this.value.slice(0,this.dataset.maxlength)" required />
							</div>
							<button class="btn btn-outline-danger" id="submitReverse">Reverse</button>
						</form>
					</div>
				</section>
			</div>
		</div>

<?php include ("../includes/footer.inc.php"); ?>

<script>

	$('#submitReverse').on('click', function() {

		$('#submitReverse').attr('disabled', true);
		$('#submitReverse').text('Reversing ...');
		setTimeout(function () {
			// $('#reverseForm').submit();

			$('#submitReverse').attr('disabled', false);
			$('#submitReverse').text('Reverse');
		}, 2000)

	});
</script>
