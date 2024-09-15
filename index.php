<?php 
    require_once ("db_connection/conn.php");

    include ("includes/header.inc.php");
    include ("includes/nav.inc.php");

    if (admin_is_logged_in()) {
    	
		// insert daily capital given
		if (isset($_POST['today_given'])) {
			if (!empty($_POST['today_given']) || $_POST['today_given'] != '') {

				$given = sanitize($_POST['today_given']);
				$today_date = sanitize($_POST['today_date']);

				$today = date("Y-m-d");
				$daily_id = guidv4();
				$daily_by = $admin_data[0]['admin_id'];

				if ($today_date == $today) {
					$data = [$daily_id, $given, $today, $daily_by];
					$sql = "
						INSERT INTO jspence_daily (daily_id, daily_capital, daily_date, daily_by) 
						VALUES (?, ?, ?, ?)
					";
					$message = "today " . $today . " capital entered of an amount of " . money($given);

					if (is_capital_given()) {
						$g = (float)($given - _capital()['today_capital']);
						$b = ((admin_has_permission('salesperson') && _capital()['today_balance'] == '0.00') ? '0.00' : (float)($g + _capital()['today_balance']));

						if (admin_has_permission('supervisor')) {
							$b = _capital()['today_balance'];
						}

						$sql = "
							UPDATE jspence_daily 
							SET daily_capital = ?, 
							daily_balance = " . $b . "
							WHERE daily_date = ? 
							AND daily_by = ?
						";
						// remove the first element and only remove one element
						$data = array_splice($data, 1, 3);
						$message = "today " . $today . " capital updated of an amount of " . money($given) . ', added amount ' . money($g);
					}
					$statement = $conn->prepare($sql);
					$result = $statement->execute($data);
					if ($result) {
						
						add_to_log($message, $admin_id);
		
						$_SESSION['flash_success'] = 'Today capital saved successfully!';
						redirect(PROOT);
					} else {
						echo js_alert('Something went wrong, please refresh and try agin!');
						redirect(PROOT);
					}
				}
			}
		}
    
		// statisticall calculations
	    $thisYr = date("Y");
	    $lastYr = $thisYr - 1;

	    $where = '';
		if (!admin_has_permission()) {
			$where = ' AND sale_by = "'.$admin_data[0]['admin_id'].'"';
		}

	    $thisYrQ = "
	        SELECT sale_total_amount, createdAt 
	        FROM jspence_sales 
	        WHERE YEAR(createdAt) = '{$thisYr}' 
	        AND sale_status = 0 
	        $where
	    ";
	    $statement = $conn->prepare($thisYrQ);
	    $statement->execute();
	    $thisYr_result = $statement->fetchAll();
	    

	    $lastYrQ = "
	        SELECT sale_total_amount, createdAt 
	        FROM jspence_sales 
	        WHERE YEAR(createdAt) = '{$lastYr}' 
	        AND sale_status = 0 
	        $where
	    ";
	    $statement = $conn->prepare($lastYrQ);
	    $statement->execute();
	    $lastYr_result = $statement->fetchAll();

	    $current = array();
	    $last = array();

	    $currentTotal = 0;
	    $lastTotal = 0;

	    foreach ($thisYr_result as $thisYr_row) {
	        $month = date("m", strtotime($thisYr_row['createdAt']));
	        if (!array_key_exists((int)$month, $current)) {
	            $current[(int)$month] = $thisYr_row['sale_total_amount'];
	        } else {
	            $current[(int)$month] += $thisYr_row['sale_total_amount'];
	        }
	        $currentTotal += $thisYr_row['sale_total_amount'];
	    }

	    foreach ($lastYr_result as $lastYr_row) {
	        $month = date("m", strtotime($lastYr_row['createdAt']));
	        if (!array_key_exists((int)$month, $last)) {
	            $last[(int)$month] = $lastYr_row['sale_total_amount'];
	        } else {
	            $last[(int)$month] += $lastYr_row['sale_total_amount'];
	        }
	        $currentTotal += $lastYr_row['sale_total_amount'];
	    }
    }

?>


					<?php if (admin_is_logged_in()): ?>
					<!-- <div class="row align-items-center g-10">
						<div class="col-lg-8">
							<h1 class="ls-tight fw-bolder display-3 text-white mb-5">Build Professional Dashboards, Faster than Ever.</h1>
							<p class="w-xl-75 lead text-white">With our intuitive tools and expertly designed components, you'll have the power to create professional dashboards quicker than ever.</p>
						</div>
						<div class="col-lg-4 align-self-end">
							<div class="hstack gap-3 justify-content-lg-end"><a href="https://themes.getbootstrap.com/product/satoshi-defi-and-crypto-exchange-theme/" class="btn btn-lg btn-white rounded-pill bg-dark-hover border-0 shadow-none px-lg-8" target="_blank">Purchase now </a><a href="/pages/dashboard.html" class="btn btn-lg btn-dark rounded-pill border-0 shadow-none px-lg-8">Explore more</a>
							</div>
						</div>
					</div> -->

					<?php if (!admin_has_permission()): ?>
					<div class="mb-6 mb-xl-10">
						<div class="row g-3 align-items-center">
							<div class="col">
								<h1 class="ls-tight">
									<?= ((admin_has_permission('supervisor')) ? 'Sold' : 'Balance'); ?>: 
									<span style="font-family: Roboto Mono, monospace;"><?= money(_capital()['today_balance']); ?></span>
								</h1>
								<p class="text-sm text-muted">
									<?php if (admin_has_permission('supervisor')) :?>
									Gained / Balance: <span class="text-success" style="font-family: Roboto Mono, monospace;"><?= _gained_calculation(_capital()['today_balance'], _capital()['today_capital']); ?></span>
									<br>
									<?php endif; ?>
									Amount given today to trade: <span style="font-family: Roboto Mono, monospace;"><?= money(_capital()['today_capital']); ?></span> 
									<br>Today date: <?= date("Y-m-d"); ?>
								</p>
							</div>
							<div class="col">
								<div class="hstack gap-2 justify-content-end">
									<button type="button" class="btn btn-sm btn-square btn-neutral rounded-circle d-xxl-none" data-bs-toggle="offcanvas" data-bs-target="#responsiveOffcanvas" aria-controls="responsiveOffcanvas"><i class="bi bi-three-dots"></i></button> <button type="button" class="btn btn-sm btn-neutral d-none d-sm-inline-flex" data-bs-target="#buyModal" data-bs-toggle="modal"><span class="pe-2"><i class="bi bi-plus-circle"></i> </span><span>Trade</span></button> 
									<button data-bs-toggle="modal" data-bs-target="#modalCapital" type="button" class="btn d-inline-flex btn-sm btn-dark"><span>Today Capital</span></button>
								</div>
							</div>
						</div>
					</div>
					<?php endif; ?>
					<div class="row g-3 g-xxl-6">
						<div class="col-xxl-8">
							<div class="vstack gap-3 gap-md-6">
								<div class="row g-3">
									<div class="col-md col-sm-6">
										<div class="card border-primary-hover">
											<div class="card-body p-4" style="font-family: Roboto Mono, monospace;">
												<div class="d-flex align-items-center gap-2">
													<img src="<?= PROOT; ?>dist/media/today.png" class="w-rem-5 flex-none" alt="..."> <a href="javascript:;" class="h6 stretched-link">Today</a>
												</div>
												<?php $t = total_amount_today($admin_data[0]['admin_id']); ?>
												<div class="text-sm fw-semibold mt-3"><?= $t['amount']; ?></div>
												<div class="d-flex align-items-center gap-2 mt-1 text-xs">
													<span class="badge badge-xs bg-<?= $t['percentage_color']; ?>"><i class="bi bi-arrow-<?= $t['percentage_icon']; ?>"></i> </span><span><?= $t['percentage']; ?>%</span>
												</div>
											</div>
										</div>
									</div>
									<div class="col-md col-sm-6">
										<div class="card border-primary-hover">
											<div class="card-body p-4" style="font-family: Roboto Mono, monospace;">
												<div class="d-flex align-items-center gap-2">
													<img src="<?= PROOT; ?>dist/media/thismonth.png" class="w-rem-5 flex-none" alt="..."> 
													<a href="javascript:;" class="h6 stretched-link">This Month</a>
												</div>
												<?php $m = total_amount_thismonth($admin_data[0]['admin_id']); ?>
												<div class="text-sm fw-semibold mt-3"><?= $m['amount']; ?></div>
												<div class="d-flex align-items-center gap-2 mt-1 text-xs"><span class="badge badge-xs bg-<?= $t['percentage_color']; ?>"><i class="bi bi-arrow-<?= $t['percentage_icon']; ?>"></i> </span><span><?= $t['percentage']; ?>%</span></div>
											</div>
										</div>
									</div>
									<div class="col-md col-sm-6">
										<div class="card border-primary-hover">
											<div class="card-body p-4" style="font-family: Roboto Mono, monospace;">
												<div class="d-flex align-items-center gap-2">
													<img src="<?= PROOT; ?>dist/media/orders.jpg" class="w-rem-5 flex-none" alt="..."> 
													<a href="<?= PROOT; ?>acc/trades" class="h6 stretched-link">Orders</a></div>
													<div class="text-sm fw-semibold mt-3"><?= count_total_orders($admin_data[0]['admin_id']); ?></div>
													<div class="d-flex align-items-center gap-2 mt-1 text-xs">
														<span class="badge badge-xs bg-info"><i class="bi bi-123"></i> </span><span><?= date("l jS \of F " . ' . ' . " A"); ?></span>
													</div>
												</div>
											</div>
										</div>
									</div>
									<?php if (admin_has_permission()): ?>
									<div class="card">
										<div class="card-body pb-0">
											<div class="d-flex justify-content-between align-items-center"><div>
												<h5>Trades</h5>
											</div>
											<div class="hstack align-items-center">
												<a href="<?= PROOT; ?>acc/analytics" class="text-muted"><i class="bi bi-arrow-repeat"></i></a>
											</div>
										</div>
										<div class="mx-n4">
											<canvas class="my-4 w-100" id="myChart" width="900" height="380"></canvas>
										</div>
									</div>
								</div>
								
								<div class="card">
									<div class="card-body">
										<div class="d-flex justify-content-between align-items-center mb-5">
											<div>
												<h5>Accumulated trades by months and years</h5>
											</div>
											<div class="hstack align-items-center">
												<a href="<?= PROOT; ?>acc/trades" class="text-muted">
													<i class="bi bi-arrow-repeat"></i>
												</a>
											</div>
										</div>
										<div class="vstack gap-6">
											<table class="table table-bordered table-lg">
												<thead>
													<tr>
														<th scope="col"></th>
						                                <th scope="col" style="font-family: Roboto Mono, monospace;"><?= $lastYr; ?></th>
						                                <th scope="col" style="font-family: Roboto Mono, monospace;"><?= $thisYr; ?></th>
													</tr>
												</thead>
												 <tbody>
						                            <?php for ($i = 1; $i <= 12; $i++):
						                                $dt = dateTime::createFromFormat('!m',$i);
						                            ?>
						                                <tr>
						                                    <td <?= (date('m') == $i) ? ' class="bg-danger"' : ''; ?>><?= $dt->format("F"); ?></td>
						                                    <td <?= (date('m') == $i) ? ' class="bg-danger"' : ''; ?> style="font-family: Roboto Mono, monospace;"><?= ((array_key_exists($i, $last)) ? money($last[$i]) : money(0)); ?></td>
						                                    <td <?= (date('m') == $i) ? ' class="bg-danger"' : ''; ?> style="font-family: Roboto Mono, monospace;"><?=  ((array_key_exists($i, $current)) ? money($current[$i]) : money(0)); ?></td>
						                                </tr>
						                            <?php endfor; ?>
						                            <tr>
						                                <td>Total</td>
						                                <td style="font-family: Roboto Mono, monospace;"><?= money($lastTotal); ?></td>
						                                <td style="font-family: Roboto Mono, monospace;"><?= money($currentTotal); ?></td>
						                            </tr>
						                        </tbody>
											</table>
										</div>
									</div>
								</div>
								<?php endif; ?>

								<div class="card">
									<div class="card-body">
										<div class="d-flex justify-content-between align-items-center mb-5">
											<div>
												<h5>Recent trades</h5>
											</div>
											<div class="hstack align-items-center">
												<a href="<?= PROOT; ?>acc/trades" class="text-muted">
													<i class="bi bi-arrow-repeat"></i>
												</a>
											</div>
										</div>
										<div class="vstack gap-6">
											<?= get_recent_trades($admin_data[0]['admin_id']); ?>
										</div>
									</div>
								</div>

							</div>
						</div>
						<div class="col-xxl-4">
							<div class="offcanvas-xxl m-xxl-0 rounded-sm-4 rounded-xxl-0 offcanvas-end overflow-hidden m-sm-4" tabindex="-1" id="responsiveOffcanvas" aria-labelledby="responsiveOffcanvasLabel">
								<div class="offcanvas-header rounded-top-4 bg-light">
									<h5 class="offcanvas-title" id="responsiveOffcanvasLabel">Quick Stats</h5>
									<button type="button" class="btn-close" data-bs-dismiss="offcanvas" data-bs-target="#responsiveOffcanvas" aria-label="Close"></button>
								</div>
								<div class="offcanvas-body d-flex flex-column p-3 p-sm-6 p-xxl-0 gap-3 gap-xxl-6">
									<div class="vstack gap-6 gap-xxl-6">
										<div class="card border-0 border-xxl">
											<div class="card-body d-flex flex-column p-0 p-xxl-6" style="font-family: Roboto Mono, monospace;">
												<div class="d-flex justify-content-between align-items-center mb-3">

													<?php $g = grand_total_amount($admin_data[0]['admin_id']); ?>
													<div>
														<h5>Grand total</h5>
													</div>
													<div>
														<span class="text-heading fw-bold">
															<i class="bi bi-arrow-<?= $g['percentage_icon'] ?> me-2"></i><?= $g['percentage'] ?>%</span>
														</div>
													</div>
													<div class="text-2xl fw-bolder text-heading ls-tight"><?= $g['grand_total']; ?></div>
													<div class="d-flex align-items-center justify-content-between mt-8">
														<div class="">
															<div class="d-flex gap-3 align-items-center">
																<div class="icon icon-sm icon-shape text-sm rounded-circle bg-dark text-info">
																	<i class="bi bi-currency-exchange"></i>
																</div>
																<span class="h6 fw-semibold text-muted">Last year</span>
															</div>
															<div class="fw-bold text-heading mt-3"><?= $g['last_year']; ?></div>
														</div>
														<span class="vr bg-dark bg-opacity-10"></span>
														<div class="">
															<div class="d-flex gap-3 align-items-center">
																<div class="icon icon-sm icon-shape text-sm rounded-circle bg-dark text-success">
																	<i class="bi bi-currency-exchange"></i>
																</div>
																<span class="h6 fw-semibold text-muted">This year</span>
															</div>
															<div class="fw-bold text-heading mt-3"><?= $g['this_year']; ?></div>
														</div>
													</div>
												</div>
											</div>
											<hr class="my-0 d-xxl-none">
											<div class="card border-0 border-xxl">
												<div class="card-body p-0 p-xxl-6">
													<div class="d-flex justify-content-between align-items-center mb-5"><div>
														<h5>Logs</h5>
													</div>
													<div class="hstack align-items-center">
														<a href="<?= PROOT; ?>acc/logs" title="view more" class="text-muted">
															<i class="bi bi-three-dots-vertical"></i>
														</a>
													</div>
												</div>
												<div class="vstack gap-1">
													<ul class="list-group">
													  	<?= get_logs($admin_data[0]['admin_id']); ?>
													</ul>
												</div>
											</div>
										</div>
										<hr class="my-0 d-xxl-none">
									</div>
								</div>
							</div>
						</div>
					</div>
					
					<!-- Seeting for todays capital -->
					 <?php if (!admin_has_permission()): ?>
					<div class="modal fade" id="modalCapital" tabindex="-1" aria-labelledby="modalCapital" aria-hidden="true" style="backdrop-filter: blur(5px);">
						<div class="modal-dialog modal-dialog-centered">
							<div class="modal-content shadow-3">
								<div class="modal-header justify-content-start">
									<div class="icon icon-shape rounded-3 bg-primary-subtle text-primary text-lg me-4">
										<i class="bi bi-currency-exchange"></i>
									</div>
									<div>
										<h5 class="mb-1">Today's Capital</h5>
										<small class="d-block text-xs text-muted">You are to give todays capital before you can start trade.</small>
									</div>
								</div>
								<form action="" method="POST" id="capitalForm">
									<div class="modal-body">
										<div class="mb-3">
											<label class="form-label">Today's Date</label> 
											<input class="form-control" name="today_date" id="today_date" type="date" value="<?php echo date('Y-m-d'); ?>">
										</div>
										<div class="">
											<label class="form-label">Amount given</label> 
											<input class="form-control" placeholder="0.00" name="today_given" id="today_given" type="number" min="0.00" step="0.01" value="<?= (is_capital_given() ? _capital()['today_capital'] : '' ); ?>">
										</div>
									</div>
									<div class="modal-footer">
										<button type="button" class="btn btn-sm btn-neutral" data-bs-dismiss="modal">Close</button> 
										<button type="submit" id="submitCapital" class="btn btn-sm btn-primary">Save</button>
									</div>
								</form>
							</div>
						</div>
					</div>
					<?php endif; ?>


					<?php else: ?>

					<div class="d-flex justify-content-center">
						<div class="col-md-6 p-12 p-xl-7">
							<div class="d-lg-flex flex-column w-full h-full p-16 bg-surface-secondary rounded-5">
								<!-- <a class="d-block" href="<?= PROOT; ?>">
									<div class="w-md-auto  text-dark">
										<img class="img-fluid rounded" style="widht: 64px; height: 64px;" src="<?= PROOT; ?>dist/media/logo.jpeg">
									</div>
								</a> -->

								<!-- Title -->
								<div class="mt-10 mt-xl-16">
									<h1 class="lh-tight ls-tighter font-bolder display-5">
										admin portal, login to start making trades.
									</h1>
								</div>

								<div class="svg-fluid mt-auto mb-xl-20 mx-auto transform scale-150">
									<svg width="400" height="400" viewBox="0 0 1600 1200" fill="none" xmlns="http://www.w3.org/2000/svg">
										<path d="M932.9 129.8C908.4 144.3 901.4 169.3 898.5 179.6C897.8 182.1 889.6 212.8 903.8 244.8C908 254.3 912.5 264.1 922.7 268.8C928.6 271.6 933.9 271.4 954.7 269.6C964.4 268.8 974.9 267.8 986.1 266.6C994 256.5 1007.6 236.3 1011.6 207.9C1012.3 202.6 1020.5 139.4 986.1 122.6C963.6 111.5 936.5 127.6 932.9 129.8Z" fill="#EC615B"></path>
										<path d="M480.5 278.9C492.982 278.9 503.1 267.707 503.1 253.9C503.1 240.093 492.982 228.9 480.5 228.9C468.018 228.9 457.9 240.093 457.9 253.9C457.9 267.707 468.018 278.9 480.5 278.9Z" fill="#EC615B"></path>
										<path d="M993 679.5C1002.28 679.5 1009.8 672.247 1009.8 663.3C1009.8 654.353 1002.28 647.1 993 647.1C983.722 647.1 976.2 654.353 976.2 663.3C976.2 672.247 983.722 679.5 993 679.5Z" fill="#EC615B"></path>
										<path d="M480.7 581.7C487.604 581.7 493.2 576.104 493.2 569.2C493.2 562.297 487.604 556.7 480.7 556.7C473.796 556.7 468.2 562.297 468.2 569.2C468.2 576.104 473.796 581.7 480.7 581.7Z" fill="#EC615B"></path>
										<path d="M457.4 270.6C468.39 270.6 477.3 261.243 477.3 249.7C477.3 238.157 468.39 228.8 457.4 228.8C446.41 228.8 437.5 238.157 437.5 249.7C437.5 261.243 446.41 270.6 457.4 270.6Z" fill="#4A4DC4"></path>
										<path d="M988.4 653.9C995.193 653.9 1000.7 648.169 1000.7 641.1C1000.7 634.031 995.193 628.3 988.4 628.3C981.607 628.3 976.1 634.031 976.1 641.1C976.1 648.169 981.607 653.9 988.4 653.9Z" fill="#4A4DC4"></path>
										<path d="M1152.8 300.9C1160.26 300.9 1166.3 294.453 1166.3 286.5C1166.3 278.547 1160.26 272.1 1152.8 272.1C1145.34 272.1 1139.3 278.547 1139.3 286.5C1139.3 294.453 1145.34 300.9 1152.8 300.9Z" fill="#4A4DC4"></path>
										<path d="M859.6 389.9C857.4 386.4 853.8 389.7 852.3 392.1C849.2 397.1 847.2 403.3 849.5 409C851.2 413.3 855.4 416.9 860.3 416.2C864.8 415.6 868.2 411.9 868.1 407.4C868.1 406.2 866.4 405.3 865.5 406.3C860.2 413 853.7 422.5 859 431.1C862.7 437 871.8 437.8 875.4 431.4C874.5 431.3 873.6 431.2 872.7 431C873.2 433.8 873.7 436.6 874.1 439.4C874.4 441.3 877.3 440.5 877 438.6C876.5 435.8 876 433 875.6 430.2C875.4 428.9 873.4 428.8 872.9 429.8C871.3 432.6 867.7 433.6 864.8 432.2C861.6 430.6 860.2 427 860.2 423.5C860.2 417.7 864.3 412.6 867.7 408.3C866.8 407.9 866 407.6 865.1 407.2C865.2 411 861.5 413.8 857.8 413C855 412.4 853.2 410 852.2 407.4C851.2 404.7 851.3 402 852.1 399.3C852.2 399.1 856.3 390.2 856.9 391.2C858 393 860.6 391.5 859.6 389.9Z" fill="#EC615B"></path>
										<path d="M451.3 398.9C453.5 392.9 460.8 395.5 463.1 399.7C463.9 401.1 464.3 402.7 464.5 404.3C464.9 407.3 464.6 408.5 462 410.3C459.2 413.1 458.5 412 460 407C460.7 406.7 461.5 406.4 462.2 406.1C464.1 405.6 466.3 405.7 468.2 406.3C473 407.9 475.4 413 474.7 417.7C474.5 418.9 475.7 420.1 476.9 419.4C483.4 415.7 492 419.8 493.5 427.1C493.9 429 496.8 428.2 496.4 426.3C494.5 417.1 483.6 412.3 475.4 416.9C476.1 417.5 476.9 418 477.6 418.6C478.9 409.8 472.2 401.6 462.9 403C459.7 403.5 454.9 405.5 454.5 409.3C454.2 412.4 457.1 414.7 460 415C468.6 415.8 468.4 403.3 465.6 398.3C461.8 391.5 451.3 389.8 448.3 398.2C447.7 399.9 450.6 400.7 451.3 398.9Z" fill="#EC615B"></path>
										<path d="M977.8 775.3C985.5 780.4 992 786.9 997.2 794.5C998.3 796.1 1000.9 794.6 999.8 793C994.3 785 987.5 778.2 979.4 772.8C977.7 771.6 976.2 774.2 977.8 775.3Z" fill="#4A4DC4"></path>
										<path d="M997.3 780.8C998.9 782.7 1000.4 784.7 1002 786.6C1003.2 788.1 1005.3 786 1004.1 784.5C1002.5 782.6 1001 780.6 999.4 778.7C998.2 777.2 996.1 779.4 997.3 780.8Z" fill="#4A4DC4"></path>
										<path d="M988.1 752.8C995 759.2 1000.7 766.5 1005.1 774.8C1006 776.5 1008.6 775 1007.7 773.3C1003.2 764.8 997.3 757.2 990.2 750.7C988.8 749.3 986.7 751.5 988.1 752.8Z" fill="#4A4DC4"></path>
										<path d="M296.7 865.4C298.9 887.1 302.7 908.5 308 929.7C308.5 931.6 311.4 930.8 310.9 928.9C305.7 908 301.9 886.8 299.7 865.4C299.5 863.5 296.5 863.5 296.7 865.4Z" fill="#E8E8E8"></path>
										<path d="M308.6 930.7C328.3 935.8 348.8 935.8 368.5 930.7C370.4 930.2 369.6 927.3 367.7 927.8C348.5 932.8 328.6 932.8 309.4 927.8C307.6 927.3 306.8 930.2 308.6 930.7Z" fill="#E8E8E8"></path>
										<path d="M370.8 927.7C372.1 906.4 373.8 885.1 375.8 863.8C376 861.9 373 861.9 372.8 863.8C370.8 885.1 369.1 906.4 367.8 927.7C367.7 929.6 370.7 929.6 370.8 927.7Z" fill="#E8E8E8"></path>
										<path d="M298.4 867C323.3 872.1 348.9 871.9 373.8 866.3C375.7 865.9 374.9 863 373 863.4C348.7 868.8 323.6 869.1 299.2 864.1C297.3 863.7 296.5 866.6 298.4 867Z" fill="#E8E8E8"></path>
										<path d="M298.1 874.2C298.9 878.3 304.1 878.1 307.3 878.4C315.4 879.1 323.6 879.3 331.7 879.1C339.5 878.9 347.3 878.3 355.1 877.4C361.3 876.6 367.3 875.8 372.8 872.7C374.5 871.8 373 869.2 371.3 870.1C361.4 875.6 347.7 875.4 336.6 875.9C331.1 876.1 325.5 876.2 320 876.1C317.1 876 301.6 876.7 301 873.4C300.6 871.5 297.8 872.3 298.1 874.2Z" fill="#E8E8E8"></path>
										<path d="M303.8 908.9C308.6 917.4 314.7 924.9 322 931.4C323.4 932.7 325.6 930.6 324.1 929.3C316.9 923 311 915.7 306.4 907.4C305.5 905.7 302.9 907.2 303.8 908.9Z" fill="#E8E8E8"></path>
										<path d="M299.9 885.8C310.1 903 322.5 918.8 336.7 933C338.1 934.4 340.2 932.2 338.8 930.9C324.8 916.9 312.6 901.3 302.5 884.3C301.5 882.7 298.9 884.2 299.9 885.8Z" fill="#E8E8E8"></path>
										<path d="M316.6 878.6C327.2 897.5 339.5 915.4 353.4 932.1C354.6 933.6 356.7 931.5 355.5 930C341.8 913.5 329.6 895.8 319.2 877.1C318.3 875.4 315.7 876.9 316.6 878.6Z" fill="#E8E8E8"></path>
										<path d="M335.7 879.3C346 894.7 356.4 910 366.9 925.3C368 926.9 370.6 925.4 369.5 923.8C359 908.5 348.6 893.2 338.3 877.8C337.2 876.2 334.6 877.7 335.7 879.3Z" fill="#E8E8E8"></path>
										<path d="M351.7 878.4C356.9 886.8 362.5 895 368.3 903C369.4 904.5 372 903 370.9 901.5C365.1 893.5 359.5 885.3 354.3 876.9C353.3 875.3 350.7 876.8 351.7 878.4Z" fill="#E8E8E8"></path>
										<path d="M265.2 777.4C266.4 784.3 267.6 791.3 268.9 798.2C269.3 800.1 269.6 802.1 271.2 803.3C272.3 804.2 275.9 804.1 276.6 805.1C276.7 804.5 276.8 803.9 276.8 803.3C275.3 804.8 273.8 806.3 272.2 807.7C271.8 808.1 271.7 808.7 271.8 809.2C272.7 811.9 273.3 815.9 275.1 818.2C276 819.4 277.6 819.2 278.3 819.9C278.9 820.5 278.7 820.1 278.4 821.9C278.2 823.4 278.2 825 278.4 826.6C278.7 829 279.4 831.3 280.3 833.5C285.5 846.1 295.6 856.3 305.3 865.7C306.7 867 308.8 864.9 307.4 863.6C301.2 857.6 295 851.4 289.9 844.4C284.9 837.5 279 828.2 282.1 819.4C282.3 818.9 282.1 818.2 281.7 817.9C279.8 816.4 277.9 817 276.6 814.6C274.1 810.1 275.5 808.6 278.7 805.5C279.2 805.1 279.3 804.2 278.9 803.7C278.1 802.5 277.5 802.1 276.2 801.7C275.4 801.4 274.2 801.7 273.4 801.3C271.7 800.4 271.7 798.2 271.4 796.6C270.2 790 269 783.3 267.9 776.7C267.7 774.7 264.8 775.5 265.2 777.4Z" fill="#E8E8E8"></path>
										<path d="M267.4 777C306.4 790 333.1 826.4 333.9 867.6C333.9 869.5 336.9 869.5 336.9 867.6C336.1 825.1 308.5 787.6 268.2 774.1C266.4 773.5 265.6 776.4 267.4 777Z" fill="#E8E8E8"></path>
										<path d="M327.3 826.6C337 799.7 351.3 773.7 374.2 756C375.7 754.8 374.2 752.2 372.7 753.4C349.1 771.7 334.4 798.1 324.4 825.8C323.8 827.6 326.7 828.4 327.3 826.6Z" fill="#E8E8E8"></path>
										<path d="M371.6 755.4C372.3 764.6 373.1 773.8 373.8 783.1C374 785 377 785 376.8 783.1C376.1 773.9 375.3 764.7 374.6 755.4C374.5 753.5 371.5 753.5 371.6 755.4Z" fill="#E8E8E8"></path>
										<path d="M375.1 782.1C373.8 782.4 372.5 782.7 371.3 783.1C369.4 783.6 370.2 786.5 372.1 786C373.4 785.7 374.7 785.4 375.9 785C377.8 784.5 377 781.7 375.1 782.1Z" fill="#E8E8E8"></path>
										<path d="M369.8 785.8C370.2 787.7 371.8 788.9 373.6 789.1C374.4 789.2 375.1 788.4 375.1 787.6C375.1 786.7 374.4 786.2 373.6 786.1C373.9 786.1 373.6 786.1 373.5 786.1C373.4 786.1 373.2 786 373.4 786.1C373.3 786 373.2 786 373.1 785.9C372.9 785.8 373.3 786.1 373 785.8C372.9 785.7 372.9 785.7 372.8 785.6C373 785.8 372.8 785.6 372.8 785.5C372.7 785.4 372.7 785.2 372.7 785.4C372.7 785.3 372.6 785.1 372.6 785C372.4 784.2 371.5 783.7 370.8 784C370 784.2 369.6 785 369.8 785.8Z" fill="#E8E8E8"></path>
										<path d="M372.1 788C372.2 793.7 372 799.3 371.4 804.9C371.2 806.7 371.5 810.9 370.3 812.3C370.2 812.4 368.4 812.7 368 813C366.5 814.2 368.6 816.3 370.1 815.1C370.6 814.7 371.8 815.1 372.6 814.2C373.8 813 373.6 810.2 373.8 808.8C374.7 801.9 375.1 794.9 375 787.9C375.1 786.1 372.1 786.1 372.1 788Z" fill="#E8E8E8"></path>
										<path d="M366.8 815.6C370 819.4 369.1 825.7 368.8 830.3C368.4 835.6 367.5 840.5 364.9 845.1C361.2 851.9 356 857.6 353.3 865C352.6 866.8 355.5 867.6 356.2 865.8C359.8 856.1 368.1 848.9 370.6 838.7C372.4 831.4 374.1 819.7 368.9 813.5C367.7 812.1 365.6 814.2 366.8 815.6Z" fill="#E8E8E8"></path>
										<path d="M343.2 866.4C346 838 353.5 810.3 365.5 784.5C366.3 782.8 363.7 781.2 362.9 783C350.7 809.4 343 837.5 340.2 866.5C340 868.4 343 868.3 343.2 866.4Z" fill="#E8E8E8"></path>
										<path d="M284.2 793.7C301.7 815.7 314.5 841 322 868C322.5 869.9 325.4 869.1 324.9 867.2C317.2 839.6 304.1 814 286.3 791.6C285.1 790.1 283 792.2 284.2 793.7Z" fill="#E8E8E8"></path>
										<path d="M286.1 830.6C294.5 830.1 302.3 832.1 309.4 836.5C311 837.5 312.6 834.9 310.9 833.9C303.4 829.3 294.9 827.1 286.1 827.6C284.1 827.7 284.1 830.7 286.1 830.6Z" fill="#E8E8E8"></path>
										<path d="M304.4 817.2C303.8 811.7 304.5 806.4 306.6 801.2C307.3 799.4 304.4 798.6 303.7 800.4C301.5 805.8 300.8 811.4 301.4 817.2C301.6 819.1 304.6 819.1 304.4 817.2Z" fill="#E8E8E8"></path>
										<path d="M336.8 800.6C340.1 808.7 344.9 817.3 346.1 825.9C346.4 827.8 349.3 827 349 825.1C347.7 816.4 342.9 807.8 339.7 799.8C339 798 336.1 798.8 336.8 800.6Z" fill="#E8E8E8"></path>
										<path d="M355.2 810.1C358.1 806.8 360.9 802.9 364.6 800.4C367.7 798.3 371.1 796.9 373.5 794C374.7 792.5 372.6 790.4 371.4 791.9C368.8 795 365 796.3 361.9 798.7C358.5 801.3 355.9 804.8 353.1 808C351.8 809.5 353.9 811.6 355.2 810.1Z" fill="#E8E8E8"></path>
										<path d="M356.2 865.4C361 864.1 365.9 863.6 370.9 863.9C372.8 864 372.8 861 370.9 860.9C365.6 860.6 360.5 861.1 355.4 862.5C353.6 863 354.3 865.9 356.2 865.4Z" fill="#E8E8E8"></path>
										<path d="M355.8 866.6C361.4 867.1 366.8 866.4 372.2 864.7C374 864.1 373.2 861.2 371.4 861.8C366.3 863.4 361.1 864 355.8 863.6C353.9 863.5 353.9 866.5 355.8 866.6Z" fill="#E8E8E8"></path>
										<path d="M299.7 866.1C301 866.1 302.2 866.1 303.5 866.1C305.4 866.1 305.4 863.1 303.5 863.1C302.2 863.1 301 863.1 299.7 863.1C297.8 863.1 297.8 866.1 299.7 866.1Z" fill="#E8E8E8"></path>
										<path d="M373 890.3C404.7 890.3 436.5 890.3 468.2 890.3C470.1 890.3 470.1 887.3 468.2 887.3C436.5 887.3 404.7 887.3 373 887.3C371.1 887.3 371.1 890.3 373 890.3Z" fill="#E8E8E8"></path>
										<path d="M909.6 870.5C928.5 870.5 947.3 870.5 966.2 870.5C968.1 870.5 968.1 867.5 966.2 867.5C947.3 867.5 928.5 867.5 909.6 867.5C907.7 867.5 907.7 870.5 909.6 870.5Z" fill="#E8E8E8"></path>
										<path d="M1081.2 872.3C1162.7 872.3 1244.2 872.3 1325.7 872.3C1348.9 872.3 1372.1 872.3 1395.3 872.3C1397.2 872.3 1397.2 869.3 1395.3 869.3C1313.8 869.3 1232.3 869.3 1150.8 869.3C1127.6 869.3 1104.4 869.3 1081.2 869.3C1079.3 869.3 1079.3 872.3 1081.2 872.3Z" fill="#E8E8E8"></path>
										<path d="M960.5 544C952.9 559.7 944.7 575.1 936 590.2C935.6 591 935.9 592.3 936.9 592.4C979.8 598.9 1018.2 620.2 1055.2 641.7C1056.9 642.7 1058.4 640.1 1056.7 639.1C1019.4 617.5 980.8 596.1 937.6 589.5C937.9 590.2 938.2 591 938.5 591.7C947.3 576.6 955.4 561.2 963 545.5C963.9 543.8 961.4 542.2 960.5 544Z" fill="#E8E8E8"></path>
										<path d="M967.1 598.5C970.1 589.9 973.1 581.2 976.1 572.6C977.4 569 978.3 565 980 561.5C980.4 559.4 981.7 557.8 983.7 556.6C984.9 556.9 986.1 557.2 987.3 557.5C989.1 558 990.9 558.4 992.6 558.9C996.7 559.9 1003.3 559.9 1002.6 565.4C1002.1 569.3 999.8 573.5 998.5 577.3C995.5 585.8 992.6 594.3 989.6 602.8C989 604.6 991.9 605.4 992.5 603.6C996.4 592.4 1000.3 581.1 1004.2 569.9C1005.1 567.3 1007.6 562.9 1005.6 560.1C1003.4 557.1 996.8 556.9 993.4 556C990.1 555.2 984.3 552.2 980.9 553.8C978 555.1 977.4 560.1 976.5 562.6C972.4 574.3 968.3 586.1 964.2 597.8C963.6 599.5 966.5 600.3 967.1 598.5Z" fill="#E8E8E8"></path>
										<path d="M1005.7 609.2C1012.1 595.7 1018.6 582.1 1025 568.6C1028.2 561.8 1031.4 555.1 1034.7 548.3C1036.2 545.2 1037.4 541.5 1039.3 538.6C1040.9 536.1 1041.3 535.5 1044.1 536.6C1045.6 537.2 1047.4 538.7 1048.7 539.6C1050.4 540.8 1053.1 542.1 1054.3 543.7C1055.8 545.6 1055.2 547.6 1054.3 550C1053.1 553.2 1051.3 556.3 1049.8 559.4C1046.5 566.2 1043.2 573 1039.7 579.8C1033.1 592.7 1026.2 605.5 1019.1 618.2C1018.1 619.9 1020.7 621.4 1021.7 619.7C1030.4 604.3 1038.7 588.7 1046.5 572.9C1050.4 565.1 1054.7 557.1 1058 549C1060.3 543.1 1055.3 540.7 1050.9 537.6C1047.4 535.2 1041.9 529.7 1038.1 534.2C1035.7 537 1034.4 541.7 1032.8 545.1C1030.8 549.2 1028.9 553.3 1026.9 557.5C1018.9 574.3 1010.9 591.1 1003 607.9C1002.3 609.4 1004.9 611 1005.7 609.2Z" fill="#E8E8E8"></path>
										<path d="M1038.9 630C1047.5 616.5 1057.2 603.7 1067.9 591.9C1067.3 592 1066.7 592.1 1066.1 592.1C1069.9 594.7 1073.6 597.2 1077.4 599.8C1077.2 599.1 1077 598.4 1076.9 597.7C1068.4 611 1059.9 624.2 1051.4 637.5C1050.4 639.1 1052.9 640.6 1054 639C1062.5 625.7 1071 612.5 1079.5 599.2C1080 598.5 1079.6 597.6 1079 597.1C1075.2 594.5 1071.5 592 1067.7 589.4C1067.2 589 1066.3 589.2 1065.9 589.6C1055 601.7 1045.1 614.6 1036.4 628.3C1035.3 630.2 1037.9 631.7 1038.9 630Z" fill="#E8E8E8"></path>
										<path d="M1158.2 534.9C1148.2 531.1 1141.9 521.1 1143.7 510.4C1145.5 499.5 1156.2 489.8 1167.2 496.3C1174.8 500.8 1178.7 511 1175.8 519.3C1172.8 528.2 1163 533.5 1153.9 532.1C1152 531.8 1151.2 534.7 1153.1 535C1163.8 536.6 1175.1 530.7 1178.7 520.1C1182.3 509.5 1176.5 496.7 1166.1 492.4C1153.9 487.4 1142.8 497.9 1140.7 509.6C1138.5 522 1145.8 533.4 1157.3 537.8C1159.2 538.5 1160 535.6 1158.2 534.9Z" fill="#E8E8E8"></path>
										<path d="M1178.7 533C1158.8 532.4 1166.3 500.5 1183.9 501.7C1201.5 503 1194.3 538.9 1175 532.3C1173.2 531.7 1172.4 534.6 1174.2 535.2C1196.5 542.8 1206.5 502.2 1185.2 498.8C1174.8 497.1 1165.4 506.9 1163.8 516.6C1162.2 526.2 1168.7 535.6 1178.7 535.9C1180.6 536.1 1180.6 533.1 1178.7 533Z" fill="#E8E8E8"></path>
										<path d="M1163.8 442.5C1162.1 440.7 1161.3 437.8 1162.6 435.6C1163.9 433.5 1167.1 432.5 1169.1 434.1C1170.9 435.5 1171.3 438.5 1169.6 440C1167.6 441.8 1164.4 440.9 1162.5 439.4C1161 438.2 1158.9 440.3 1160.4 441.5C1163.5 443.9 1168.3 445.1 1171.6 442.2C1174.6 439.5 1174.3 434.5 1171.3 431.9C1168.2 429.2 1163.3 430 1160.7 433C1157.8 436.4 1158.6 441.5 1161.6 444.6C1163 446 1165.1 443.9 1163.8 442.5Z" fill="#E8E8E8"></path>
										<path d="M873.6 517.8C870.8 500.8 880.7 480.3 899.9 480.5C909.7 480.6 919.2 484.6 929.1 483.2C936.1 482.2 942.1 478.2 946.3 472.7C950.5 467.2 952.9 460.3 953.3 453.4C953.8 443.2 949.9 432.8 952.7 422.7C954.1 417.6 957.1 412.9 962.2 411C967.1 409.3 972.4 410.3 977.5 410.1C984.9 409.8 990.7 406.6 994.4 400.1C999.6 391 998.4 380.1 1002 370.5C1006.3 358.9 1018.4 349 1031.2 354.7C1032.9 355.5 1034.5 352.9 1032.7 352.1C1015.3 344.3 1000.8 360 997.4 375.8C995.2 386.1 995.6 402.1 983.3 406.2C974.7 409.1 965 404.6 957.1 410.5C950.6 415.3 948.7 424.1 948.8 431.8C949.1 443.1 952.6 454.1 947.6 464.9C943 475.1 933.9 481.2 922.6 480.5C912.6 479.9 902.7 475.8 892.6 478.4C875.5 482.7 868.2 502.9 870.9 518.7C871.1 520.4 874 519.6 873.6 517.8Z" fill="#E8E8E8"></path>
										<path d="M1000 446.5C996.3 438.1 1001.4 430.1 1009.5 427.4C1015.4 425.4 1021.9 425.3 1027 421.3C1031 418.2 1032.3 414.3 1031.8 409.3C1031.3 404.6 1031.3 400.9 1035 397.7C1038.1 395 1042.2 394 1046.2 393.8C1057.2 393.4 1072.3 399 1080.3 388.3C1084 383.3 1082.9 377.1 1084.6 371.5C1086.9 363.7 1094.4 358.9 1102.4 359.3C1104.3 359.4 1104.3 356.4 1102.4 356.3C1096.3 356 1090.4 358.3 1086.2 362.7C1081 368.2 1081.7 374.4 1080.1 381.3C1075.8 400.2 1049.5 386.9 1037.1 392.8C1032.5 395 1028.8 398.9 1028.4 404.1C1027.9 410.2 1030.7 415.2 1024.5 419.4C1021.2 421.6 1017.4 422.3 1013.6 423.2C1007.3 424.7 1001.1 426.6 997.9 432.7C995.3 437.5 995.3 443 997.5 447.9C998.2 449.8 1000.7 448.2 1000 446.5Z" fill="#E8E8E8"></path>
										<path d="M1178.2 313.3C1177.8 312.4 1177.4 311.4 1177.1 310.4C1178.4 308.9 1179.6 307.4 1180.9 305.9C1181.3 305.8 1183.1 307 1183.7 307C1185.2 307.2 1186.5 306.8 1187.7 305.9C1191.4 303 1190.1 298.4 1191.2 294.5C1193 287.8 1200.9 292.4 1204.5 287.4C1208.1 282.5 1206.8 272.1 1216.4 274.9C1218.3 275.4 1219.1 272.5 1217.2 272C1211.7 270.4 1206.7 273 1204.7 278.3C1204.1 279.8 1203.9 281.5 1203.3 283C1202 286.5 1200.6 287 1196.8 287.2C1194 287.4 1191.8 287.2 1189.9 289.6C1188.3 291.7 1188.3 294.4 1188 296.9C1187.7 300.6 1188 303.7 1182.5 303.3C1181.4 303.2 1180.5 302.2 1179.3 302.2C1172.7 302.1 1173.8 310.2 1175.4 314.1C1176 315.9 1179 315.1 1178.2 313.3Z" fill="#E8E8E8"></path>
										<path d="M1197 321.6C1195.4 319.5 1192.7 313.4 1197.4 311.8C1199.1 311.2 1201.3 311.9 1203.1 311.9C1204.7 311.9 1206.1 311.9 1207.4 310.8C1211.4 307.6 1208.5 301.9 1210.6 297.9C1214 291.4 1220.8 296.5 1225.6 293.1C1229 290.7 1229.9 286.1 1230.5 282.3C1230.8 280.4 1227.9 279.6 1227.6 281.5C1226.9 286 1226.1 291 1220.5 291.5C1217.6 291.8 1215.4 290.8 1212.7 292.1C1208.3 294.2 1206.9 298.1 1206.8 302.6C1206.7 306.4 1207.6 308.3 1201.2 308.6C1200.4 308.6 1199.6 308.4 1198.8 308.4C1196.5 308.6 1194.5 309.8 1193.2 311.7C1190.4 315.7 1192 320.3 1194.8 323.8C1196.1 325.3 1198.2 323.1 1197 321.6Z" fill="#E8E8E8"></path>
										<path d="M346.8 375.8C355.1 365.8 366.9 357.6 380.5 359C395.3 360.5 402.6 372.3 404 386.1C406.4 409 400.8 436.4 416.3 455.9C422.4 463.6 431.5 467.7 441.3 465.3C453.1 462.5 462.3 453.2 472.8 447.7C485.2 441.2 502.3 437.7 513.5 448.6C521.7 456.6 523.5 468.8 526.2 479.4C528.7 489 533 499.6 541.6 505.3C546.8 508.7 552.5 509 558.5 508.6C565.2 508.1 578.9 507.7 577.5 518.2C577.3 520.1 580.3 520.1 580.5 518.2C581 514.1 579.1 510.8 575.8 508.4C570.6 504.6 564.6 505.2 558.5 505.6C551.6 506.1 545.7 505.5 540.4 500.6C535.6 496.2 532.7 490 530.6 483.9C527.1 473.4 526.1 461.9 520.1 452.3C514.2 442.8 504 438.2 493 438.6C479.1 439.1 467.4 447.1 456.3 454.5C451 458 445.3 461.7 438.8 462.8C431 464.1 424.1 460.7 419.1 454.9C409.7 443.9 408.2 428.2 407.7 414.4C407.2 401.5 408.9 387.5 404.6 375C400.7 363.6 390.8 356.5 378.9 355.9C365.1 355.2 353 363.5 344.5 373.7C343.4 375.2 345.5 377.3 346.8 375.8Z" fill="#E8E8E8"></path>
										<path d="M296.3 530.1C311.9 556.1 325.8 583 338.1 610.7C338.9 612.5 341.5 610.9 340.7 609.2C328.4 581.5 314.4 554.6 298.9 528.6C297.9 526.9 295.3 528.4 296.3 530.1Z" fill="#E8E8E8"></path>
										<path d="M298.2 528.9C322.5 513.8 347.5 499.7 373.2 486.9C374.9 486 373.4 483.4 371.7 484.3C346.1 497.1 321.1 511.2 296.7 526.3C295 527.4 296.5 530 298.2 528.9Z" fill="#E8E8E8"></path>
										<path d="M371.4 485.6C384.1 512.3 397.5 538.6 411.7 564.5C412.6 566.2 415.2 564.7 414.3 563C400.1 537.1 386.6 510.8 374 484.1C373.1 482.3 370.6 483.8 371.4 485.6Z" fill="#E8E8E8"></path>
										<path d="M341.5 611.3C365.4 596.9 389 582.1 412.4 566.9C414 565.9 412.5 563.3 410.9 564.3C387.5 579.5 363.9 594.3 340 608.7C338.4 609.7 339.9 612.3 341.5 611.3Z" fill="#E8E8E8"></path>
										<path d="M339.6 520.1C339 539.3 340.1 558.4 342.9 577.4C343.2 579.3 346.1 578.5 345.8 576.6C343 557.9 342 539 342.6 520.1C342.7 518.2 339.7 518.2 339.6 520.1Z" fill="#E8E8E8"></path>
										<path d="M339.7 519.1C353.6 530 367.5 540.7 381.6 551.3C383.1 552.5 384.6 549.9 383.1 548.7C369.2 538.3 355.4 527.7 341.8 517C340.3 515.8 338.2 517.9 339.7 519.1Z" fill="#E8E8E8"></path>
										<path d="M346 578C357.5 567.7 372.5 562.2 383.4 551.1C384.8 549.7 382.6 547.6 381.3 549C370.4 560 355.5 565.6 343.9 575.9C342.4 577.1 344.6 579.2 346 578Z" fill="#E8E8E8"></path>
										<path d="M300.3 545.8C303.5 544.2 306.7 542.7 309.9 541.1C311.6 540.3 310.1 537.7 308.4 538.5C305.2 540.1 302 541.6 298.8 543.2C297.1 544.1 298.6 546.6 300.3 545.8Z" fill="#E8E8E8"></path>
										<path d="M310.6 562.8C313.3 561 316.2 559.5 319.2 558.2C321 557.5 320.2 554.6 318.4 555.3C315.1 556.7 312 558.3 309 560.2C307.5 561.3 309 563.9 310.6 562.8Z" fill="#E8E8E8"></path>
										<path d="M321.9 582.4C324.7 581 327.5 579.6 330.3 578.3C332 577.4 330.5 574.9 328.8 575.7C326 577.1 323.2 578.5 320.4 579.8C318.7 580.6 320.2 583.2 321.9 582.4Z" fill="#E8E8E8"></path>
										<path d="M327.7 602C330.8 599.3 334 597 337.6 595C339.3 594 337.8 591.5 336.1 592.4C332.4 594.5 328.9 597 325.6 599.9C324.1 601.2 326.2 603.3 327.7 602Z" fill="#E8E8E8"></path>
										<path d="M377.1 505.3C379.4 503.7 381.8 502.6 384.5 501.9C386.4 501.4 385.6 498.6 383.7 499C380.8 499.7 378 500.9 375.5 502.7C374 503.8 375.5 506.4 377.1 505.3Z" fill="#E8E8E8"></path>
										<path d="M386 522.4C389.2 520.4 392.4 518.4 395.5 516.3C397.1 515.3 395.6 512.7 394 513.7C390.8 515.7 387.6 517.7 384.5 519.8C382.8 520.9 384.3 523.5 386 522.4Z" fill="#E8E8E8"></path>
										<path d="M396.2 542.9C399.6 541.4 403.1 539.9 406.5 538.4C408.3 537.6 406.7 535 405 535.8C401.6 537.3 398.1 538.8 394.7 540.3C392.9 541.1 394.5 543.7 396.2 542.9Z" fill="#E8E8E8"></path>
										<path d="M403.5 556.3C406.2 553.7 409.2 551.5 412.5 549.6C414.2 548.7 412.7 546.1 411 547C407.5 549 404.3 551.3 401.4 554.1C400 555.5 402.1 557.6 403.5 556.3Z" fill="#E8E8E8"></path>
										<path d="M350 598.1C352.5 601.8 354.6 605.7 356.2 609.8C356.9 611.6 359.8 610.8 359.1 609C357.4 604.6 355.3 600.4 352.6 596.5C351.5 595 348.9 596.5 350 598.1Z" fill="#E8E8E8"></path>
										<path d="M365.3 589.8C367.2 593.2 369 596.5 370.9 599.9C371.8 601.6 374.4 600.1 373.5 598.4C371.6 595 369.8 591.7 367.9 588.3C367 586.6 364.4 588.1 365.3 589.8Z" fill="#E8E8E8"></path>
										<path d="M383 577.1C385 580.7 387 584.3 389 587.9C389.9 589.6 392.5 588.1 391.6 586.4C389.6 582.8 387.6 579.2 385.6 575.6C384.7 573.9 382.1 575.4 383 577.1Z" fill="#E8E8E8"></path>
										<path d="M398.9 567.6C400.5 571.4 402.1 575.2 403.8 578.9C404.6 580.7 407.1 579.1 406.4 577.4C404.8 573.6 403.2 569.8 401.5 566.1C400.7 564.4 398.1 565.9 398.9 567.6Z" fill="#E8E8E8"></path>
										<path d="M308.2 513.5C310.1 516.6 311.9 519.7 313.8 522.7C314.8 524.4 317.4 522.8 316.4 521.2C314.5 518.1 312.7 515 310.8 512C309.8 510.4 307.2 511.9 308.2 513.5Z" fill="#E8E8E8"></path>
										<path d="M325.4 503.5C327.2 507.5 329 511.5 330.8 515.5C331.6 517.3 334.2 515.7 333.4 514C331.6 510 329.8 506 328 502C327.1 500.2 324.6 501.7 325.4 503.5Z" fill="#E8E8E8"></path>
										<path d="M340.9 493.9C343.6 497.5 345.7 501.4 347.1 505.6C347.7 507.4 350.6 506.6 350 504.8C348.5 500.3 346.4 496.1 343.5 492.4C342.4 490.9 339.8 492.3 340.9 493.9Z" fill="#E8E8E8"></path>
										<path d="M357.6 486.2C359.7 489.4 361.7 492.5 363.8 495.7C364.8 497.3 367.4 495.8 366.4 494.2C364.3 491 362.3 487.9 360.2 484.7C359.1 483.1 356.5 484.6 357.6 486.2Z" fill="#E8E8E8"></path>
										<path d="M513.1 287.9C503.4 284.9 502.9 274.6 507.5 266.9C512 259.4 520.7 257.4 527.5 263.6C533.1 268.8 533.6 277.7 528.4 283.4C523.2 289.2 514.7 289.1 508.7 284.7C507.2 283.5 505.7 286.1 507.2 287.3C514.8 292.9 525.9 292 531.6 284.1C537.5 276.1 535.4 264.9 527.1 259.5C507.1 246.6 489.9 283.8 512.3 290.8C514.2 291.4 515 288.5 513.1 287.9Z" fill="#E8E8E8"></path>
										<path d="M529.2 290.5C521.7 292.8 519.9 283.8 523.2 279C525.9 275.1 531.2 272.8 536 274.1C540.6 275.3 543.9 280 542.7 284.7C541.2 290.6 535 292.7 529.5 291.8C527.6 291.5 526.8 294.4 528.7 294.7C536.1 296 544.1 292.8 545.7 284.8C547.1 277.9 541.7 271.6 535 270.8C527.6 269.9 519.5 275.3 518.5 282.9C517.6 289.5 523.6 295.4 530.2 293.3C531.9 292.8 531.1 289.9 529.2 290.5Z" fill="#E8E8E8"></path>
										<path d="M465.4 355.6C463 360.5 466.4 365.4 471.4 366.7C475.2 367.7 479.2 366.8 483.1 367C487.3 367.2 491.5 368.2 495.3 369.9C503.7 373.7 509.1 380.6 509.4 389.9C509.7 397.7 507.4 405.8 510.8 413.2C518 428.8 537.8 416.9 548 427C556.5 435.5 553 451 564.4 457.5C566.1 458.5 567.6 455.9 565.9 454.9C555.7 449.1 558.1 434 550.9 425.6C547.1 421.2 541.6 420 536 419.6C529.2 419.2 520.4 420.6 515.4 414.8C509.6 408 512.7 397.8 512.4 389.8C512.2 383.5 510.1 377.8 505.7 373.3C501.8 369.4 496.7 366.7 491.4 365.3C488.7 364.6 485.9 364.1 483.1 364C479.5 363.8 463.9 365.6 468.1 357.1C468.9 355.4 466.3 353.9 465.4 355.6Z" fill="#E8E8E8"></path>
										<path d="M231.6 895.4C249.7 895.4 267.7 895.4 285.8 895.4C287.7 895.4 287.7 892.4 285.8 892.4C267.7 892.4 249.7 892.4 231.6 892.4C229.6 892.4 229.6 895.4 231.6 895.4Z" fill="#E8E8E8"></path>
										<path d="M260.8 963.2C295.6 963.2 330.3 963.2 365.1 963.2C367 963.2 367 960.2 365.1 960.2C330.3 960.2 295.6 960.2 260.8 960.2C258.8 960.2 258.8 963.2 260.8 963.2Z" fill="#E8E8E8"></path>
										<path d="M360.9 944.4C393.2 944.4 425.6 944.4 457.9 944.4C459.8 944.4 459.8 941.4 457.9 941.4C425.6 941.4 393.2 941.4 360.9 941.4C358.9 941.4 358.9 944.4 360.9 944.4Z" fill="#E8E8E8"></path>
										<path d="M898.8 939.2C915.1 935.2 931.5 934.8 948.1 937.4C955.1 938.5 962.2 939.6 969.3 939C976.2 938.4 982.9 936.3 989.6 934.5C997.2 932.4 1004.8 930.8 1012.8 931.4C1021 932 1028.9 934.3 1036.9 936.1C1043.8 937.7 1050.8 938.8 1057.9 938.7C1065.4 938.5 1072.8 937.2 1080.1 936.1C1100.1 932.9 1118.9 934.9 1138.7 938.2C1140.6 938.5 1141.4 935.6 1139.5 935.3C1123.5 932.7 1107.4 930 1091.1 931.6C1074.1 933.3 1058.3 937.5 1041.1 934C1026.7 931.1 1013 926.6 998.2 929.4C990.3 930.9 982.8 933.7 974.9 935.2C966.1 936.9 957.6 935.9 948.8 934.5C931.7 931.9 914.8 932.1 897.9 936.3C896.2 936.8 897 939.7 898.8 939.2Z" fill="#E8E8E8"></path>
										<path d="M1052.4 908.8C1083.8 907.1 1115.2 905.4 1146.6 903.8C1162.1 903 1177.1 903.3 1192.5 905.4C1199.4 906.3 1206 906.5 1212.9 905.7C1220.6 904.8 1228.6 903.6 1235.8 907.3C1237.5 908.2 1239 905.6 1237.3 904.7C1225.1 898.5 1211 904.3 1197.9 903.1C1190.1 902.4 1182.5 900.9 1174.7 900.5C1166.6 900.1 1158.5 900.3 1150.4 900.7C1117.7 902.2 1085 904.2 1052.3 905.9C1050.5 905.9 1050.5 908.9 1052.4 908.8Z" fill="#E8E8E8"></path>
										<path d="M437.5 994.7C452.3 996.1 467.2 997.5 482 995.8C488.9 995 495.5 993.5 502.2 991.5C510.9 988.9 518.7 988.2 527.7 989.8C534.6 991 541.4 992.7 548.3 993.3C555.6 993.9 562.9 993.5 570.2 992.8C585.5 991.3 600.8 988.7 616 992.1C617.9 992.5 618.7 989.6 616.8 989.2C602.2 986 587.6 988.1 572.9 989.6C565.6 990.3 558.3 991 551 990.6C542.7 990.2 534.7 988 526.6 986.7C519.8 985.6 513.3 985.6 506.6 987.2C499.4 989 492.6 991.4 485.2 992.5C469.4 994.8 453.3 993.2 437.5 991.8C435.5 991.5 435.6 994.5 437.5 994.7Z" fill="#E8E8E8"></path>
										<path d="M595.7 187.8C591.8 185.1 586.5 180.8 588.1 175.4C589.8 169.8 594 172.5 597.9 171.2C603.1 169.5 602.8 163.2 601.3 159.1C599.8 155.1 596.6 145.4 604.9 148.7C606.8 149.5 608.2 150.8 610.4 150.1C612.4 149.5 613.4 147.7 613.9 145.8C614.3 144.1 614 142.3 613.9 140.5C613.7 139.3 613.6 138 613.6 136.8C613.2 133.9 615 132.7 618.8 133.2C618.9 135.1 621.9 135.1 621.8 133.2C621.6 130.4 619.5 128.4 616.5 128.5C613 128.6 611.1 132 610.7 135.2C610.4 137.4 612.2 142.5 611.2 144.3C610 145.1 608.8 146 607.5 146.8C607 146.5 606.5 146.2 606 145.9C605.1 145.6 604.4 145 603.5 144.8C600.4 144.1 597.9 146 596.8 148.8C594.9 153.6 598.3 157.6 599.3 162.1C600.6 168.3 595.9 167.6 591.8 168.3C590 168.6 588.5 169.5 587.3 170.8C581 178.1 588.3 186.2 594.3 190.4C595.8 191.5 597.3 188.9 595.7 187.8Z" fill="#E8E8E8"></path>
										<path d="M615.4 185C612.1 181.2 609.6 176 616 174C617.6 173.5 619.4 173.7 620.9 172.7C624.2 170.4 622.8 167.3 622.2 164.1C621.9 162.3 621.2 159.9 622.3 158.1C623.1 156.8 626 156.3 627.5 156C629.6 155.5 631.8 155.2 633.6 154C636.3 152.1 637.3 148.7 635.5 145.8C634.5 144.2 631.9 145.7 632.9 147.3C635.9 152.1 621.9 153.4 619.5 156.9C616.7 161 622.6 168.2 617.7 170.8C615.8 171.8 613.4 171.1 611.6 172.4C610.3 173.3 609.4 174.7 609 176.2C607.9 180.5 610.8 184.2 613.4 187.2C614.6 188.6 616.7 186.5 615.4 185Z" fill="#E8E8E8"></path>
										<path d="M497.3 161.3C494.7 159.5 494.4 156.3 495.2 153.5C495.7 151.6 498.1 146.4 500.4 146.2C504.2 145.8 506.1 150.6 507.1 153.4C508 156.1 508.3 158.8 508.4 161.6C508.4 163.5 511.4 163.5 511.4 161.6C511.3 156 507.6 138.8 498.4 143.8C494.9 145.7 492.3 151.2 491.9 155C491.5 158.6 492.9 161.8 495.8 163.9C497.3 165 498.8 162.4 497.3 161.3Z" fill="#E8E8E8"></path>
										<path d="M496.1 162.9C492.5 163.8 488.9 164.7 485.2 165.6C483.1 166.1 479.5 166.3 477.8 167.9C474.9 170.6 475.7 178.9 475.7 182.4C475.7 183.4 476.7 184.1 477.6 183.8C478.1 183.7 478.6 183.5 479.2 183.4C481.8 182 483.1 182.9 483.2 185.9C485.2 187.9 485.2 189.1 483.2 189.3C482 189.8 480.8 190 479.5 190C478.7 190.1 478 190.6 478 191.5C478.2 197.9 478.7 204.2 479.4 210.6C479.6 212.5 482.6 212.5 482.4 210.6C481.7 204.2 481.2 197.9 481 191.5C480.5 192 480 192.5 479.5 193C483.7 192.5 490.4 190.8 489.5 185.1C488.5 179 481.1 179.7 476.9 180.9C477.5 181.4 478.2 181.9 478.8 182.3C478.8 180 478.8 177.8 479 175.5C479.3 171.9 479.1 170.6 482.4 169.3C486.9 167.5 492.2 166.9 496.9 165.7C498.8 165.4 498 162.5 496.1 162.9Z" fill="#E8E8E8"></path>
										<path d="M510.5 163.8C514.7 163.6 519 163.6 523.2 164.1C527.2 164.6 527 165.2 527.6 168.7C528 170.9 528.1 173.2 528.3 175.4C529.1 184 529.7 192.6 530.2 201.2C530.3 203.1 533.3 203.1 533.2 201.2C532.6 189.5 532.2 177.5 530.3 165.9C529.8 163 529.3 162.1 526.3 161.6C521.1 160.6 515.7 160.6 510.5 160.9C508.6 160.9 508.6 163.9 510.5 163.8Z" fill="#E8E8E8"></path>
										<path d="M481.6 212.7C487.5 211.7 493.4 210.7 499.2 209.7C505.9 208.6 512.3 209.1 519 208.6C522.1 208.3 536.8 206.8 531.4 200.7C530.1 199.3 528 201.4 529.3 202.8C530.1 203.7 516.5 205.7 516.2 205.7C512 205.8 507.8 205.6 503.6 206.1C496 207 488.4 208.6 480.8 209.9C478.9 210.1 479.7 213 481.6 212.7Z" fill="#E8E8E8"></path>
										<path d="M1012.8 788.6C1012.5 799.1 1012.2 809.6 1011.9 820.2C1025.2 821.1 1038.4 821.9 1051.7 822.8C1051.7 812 1051.7 801.2 1051.7 790.4C1038.7 789.8 1025.8 789.2 1012.8 788.6Z" fill="white" stroke="#161616" stroke-width="2" stroke-miterlimit="10"></path>
										<path d="M1030.1 905.3C1031.9 916.9 1032.7 926.5 1033.1 933.4C1034.4 956.3 1033.4 961.6 1031.4 965.8C1026.5 976.1 1017.6 980.4 1018.4 981.8C1019 982.7 1023.4 981.7 1043 971.4C1041.8 948.6 1040.7 925.9 1039.5 903.1C1036.4 903.9 1033.3 904.6 1030.1 905.3Z" fill="white" stroke="#161616" stroke-width="2" stroke-miterlimit="10"></path>
										<path d="M1004.2 831.8C1021.2 831.9 1038.2 832.1 1055.2 832.2C1053.3 855 1051.5 877.7 1049.6 900.5C1034.8 899.3 1019.9 898.2 1005.1 897C1004.7 875.3 1004.4 853.6 1004.2 831.8Z" fill="white" stroke="#161616" stroke-width="2" stroke-miterlimit="10"></path>
										<path d="M1011.1 904.5C1016.5 917.6 1018.3 928.9 1018.9 936.5C1019.8 947.7 1020.4 956.3 1015 962C1013 964.2 1009.7 966.3 1003.3 970.6C997.6 974.4 994.9 975.8 995.1 976.2C995.4 976.8 1000.9 975.6 1024.9 967.1C1026.5 959 1027.8 948.6 1027.5 936.4C1027.1 923.4 1025 912.3 1022.7 904C1018.9 904.2 1015 904.3 1011.1 904.5Z" fill="white" stroke="#161616" stroke-width="2" stroke-miterlimit="10"></path>
										<path d="M1019.8 982.7C1022.8 980.2 1025.6 977.5 1028.3 974.6C1031.4 971.2 1036 966 1040.8 970.5C1040.9 969.7 1041 968.9 1041.1 968.1C1034 971.6 1027 975.1 1019.9 978.5C1018.5 979.2 1019.2 981.3 1020.7 981.3C1027.4 981.4 1031.6 969.7 1038.5 971.9C1040.3 972.5 1041.1 969.6 1039.3 969C1036.3 968.1 1034 968.9 1031.5 970.7C1028.6 972.8 1024.5 978.3 1020.8 978.3C1021.1 979.2 1021.3 980.2 1021.6 981.1C1028.7 977.6 1035.7 974.1 1042.8 970.7C1043.7 970.3 1043.8 969 1043.1 968.3C1040.4 965.8 1035.6 964.9 1032.2 966.5C1029.7 967.6 1028.1 970.5 1026.3 972.4C1023.6 975.3 1020.8 977.9 1017.8 980.5C1016.2 981.8 1018.3 984 1019.8 982.7Z" fill="#161616"></path>
										<path d="M1032.1 967.1C1035.1 967.2 1038 967.8 1040.8 969C1042.5 969.8 1044.1 967.2 1042.3 966.4C1039 964.9 1035.7 964.1 1032.1 964C1030.2 964.1 1030.2 967.1 1032.1 967.1Z" fill="#161616"></path>
										<path d="M1015.7 963.6C1018 964.2 1020 965.4 1021.6 967.2C1022.9 968.6 1025 966.5 1023.7 965.1C1021.7 963 1019.2 961.5 1016.4 960.8C1014.7 960.2 1013.9 963.1 1015.7 963.6Z" fill="#161616"></path>
										<path d="M999.5 975.7C1002.6 973.4 1005.8 971.3 1009.1 969.3C1010.6 968.4 1012.2 967.2 1013.8 966.7C1016.3 965.8 1017.8 966.4 1020.1 967.4C1020.1 966.5 1020.1 965.7 1020.1 964.8C1016.9 966.2 1013.3 966.1 1010 967.5C1006.8 968.8 1003.8 972 1000.5 972.6C1000.9 973.5 1001.3 974.4 1001.7 975.3C1007.6 972.4 1014.2 971.9 1020.1 969C1021.8 968.1 1020.3 965.6 1018.6 966.4C1012.7 969.3 1006.1 969.8 1000.2 972.7C998.6 973.5 999.8 975.7 1001.4 975.4C1005 974.8 1007.9 971.5 1011.3 970.2C1014.7 968.9 1018.4 968.8 1021.7 967.4C1022.7 967 1022.7 965.2 1021.7 964.8C1017.9 963.1 1016.2 962.1 1012.4 964.1C1007.4 966.7 1002.7 969.8 998.2 973.1C996.4 974.2 997.9 976.8 999.5 975.7Z" fill="#161616"></path>
										<path d="M1016.6 919.8C1019.1 918.9 1021.6 919 1023.9 920.2C1025.6 921.1 1027.1 918.5 1025.4 917.6C1022.4 916 1019 915.7 1015.8 916.9C1014 917.6 1014.8 920.5 1016.6 919.8Z" fill="#161616"></path>
										<path d="M1019.1 929.8C1021.2 929.6 1023.2 930.1 1024.8 931.5C1026.3 932.7 1028.4 930.6 1026.9 929.4C1024.7 927.6 1022 926.6 1019.1 926.8C1017.2 926.9 1017.2 929.9 1019.1 929.8Z" fill="#161616"></path>
										<path d="M1020.2 942.3C1022.1 942.1 1023.9 942.8 1025 944.4C1026 946 1028.6 944.5 1027.6 942.9C1026.1 940.4 1023.2 938.9 1020.3 939.3C1019.5 939.4 1018.8 939.9 1018.8 940.8C1018.7 941.5 1019.4 942.4 1020.2 942.3Z" fill="#161616"></path>
										<path d="M1033.1 919.1C1034.9 918.1 1037.2 918.2 1038.9 919.6C1040.4 920.8 1042.5 918.7 1041 917.5C1038.3 915.3 1034.7 914.8 1031.6 916.5C1029.9 917.5 1031.4 920.1 1033.1 919.1Z" fill="#161616"></path>
										<path d="M1034.8 938C1036.4 937.3 1038.2 937.6 1039.5 938.8C1040.9 940.1 1043.1 938 1041.6 936.7C1039.3 934.6 1036.1 934.1 1033.2 935.5C1031.5 936.2 1033.1 938.8 1034.8 938Z" fill="#161616"></path>
										<path d="M1035.2 954C1036.8 953 1038.8 953 1040.3 954.2C1041.8 955.4 1043.9 953.3 1042.4 952.1C1039.9 950 1036.4 949.7 1033.7 951.5C1032.1 952.5 1033.6 955.1 1035.2 954Z" fill="#161616"></path>
										<path d="M1057.6 832.1C1055.6 855.1 1053.6 878.1 1051.6 901.1" stroke="#161616" stroke-width="6" stroke-miterlimit="10"></path>
										<path d="M1004.8 897.9C1021.5 899.1 1038.2 900.2 1054.9 901.4" stroke="#161616" stroke-width="6" stroke-miterlimit="10"></path>
										<path d="M1055 789.5C1054.6 800.9 1054.1 812.2 1053.7 823.6" stroke="#161616" stroke-width="6" stroke-miterlimit="10"></path>
										<path d="M1057.1 845.6C1066.4 843.9 1076.3 851.2 1077 860.8C1078 872.8 1064.3 874.9 1055.3 873.6C1053.4 873.3 1052.6 876.2 1054.5 876.5C1065.5 878.1 1081 875 1080 860.8C1079.2 849.1 1067.5 840.6 1056.3 842.7C1054.4 843 1055.2 845.9 1057.1 845.6Z" fill="#161616"></path>
										<path d="M1054.9 878.9C1058.8 879.7 1061.9 882.5 1063 886.3C1063.5 888.2 1066.4 887.4 1065.9 885.5C1064.6 880.7 1060.7 876.9 1055.8 876C1053.8 875.7 1053 878.5 1054.9 878.9Z" fill="#161616"></path>
										<path d="M1053.4 877.9C1055.8 881.4 1056.9 885.3 1056.5 889.6C1056.4 891.5 1059.4 891.5 1059.5 889.6C1059.8 884.9 1058.6 880.3 1055.9 876.4C1054.9 874.8 1052.3 876.3 1053.4 877.9Z" fill="#161616"></path>
										<path d="M1004.1 844.2C997.5 840 985.4 852.1 983.9 858C980.9 870 996.3 868.6 1002.7 872.6C1004.3 873.6 1005.8 871 1004.2 870C999.6 867.1 992.8 868.2 988.8 864.6C983.9 860.2 988.4 854.9 991.8 851.8C993.6 850.2 999.7 844.9 1002.6 846.7C1004.2 847.9 1005.8 845.3 1004.1 844.2Z" fill="#161616"></path>
										<path d="M1002.5 871.2C999.9 872.2 997.5 873.7 995.5 875.8C994 877.4 992.1 879.9 993.6 882.1C994.7 883.7 997.3 882.2 996.2 880.6C995.5 879.6 998.1 877.4 998.8 876.8C1000.1 875.6 1001.6 874.7 1003.3 874.1C1005.1 873.4 1004.4 870.5 1002.5 871.2Z" fill="#161616"></path>
										<path d="M1001.3 873.3C999.4 876.3 998.8 879.7 999.5 883.1C999.9 885 1002.8 884.2 1002.4 882.3C1001.8 879.6 1002.4 877.1 1003.9 874.8C1005 873.2 1002.4 871.7 1001.3 873.3Z" fill="#161616"></path>
										<path d="M1010.8 842.3C1010.3 849.6 1010.4 857 1010.9 864.3C1011.1 866.2 1014.1 866.2 1013.9 864.3C1013.3 857 1013.3 849.6 1013.8 842.3C1014 840.4 1011 840.4 1010.8 842.3Z" fill="#161616"></path>
										<path d="M1013 843.1C1023.6 843.7 1034.1 844.1 1044.7 844.3C1044.2 843.8 1043.7 843.3 1043.2 842.8C1042.9 850.3 1042.6 857.9 1042.2 865.4C1042.1 867.3 1045.1 867.3 1045.2 865.4C1045.5 857.9 1045.8 850.3 1046.2 842.8C1046.2 842 1045.5 841.3 1044.7 841.3C1034.1 841.1 1023.6 840.7 1013 840.1C1011.1 840 1011.1 843 1013 843.1Z" fill="#161616"></path>
										<path d="M1011.8 865.6C1016.2 866.8 1021.1 866.5 1025.5 866.8C1030.9 867.1 1036.4 867.5 1041.8 867.8C1043.7 867.9 1043.7 864.9 1041.8 864.8C1037 864.5 1032.2 864.2 1027.4 863.9C1022.6 863.6 1017.2 863.9 1012.5 862.7C1010.8 862.2 1010 865.1 1011.8 865.6Z" fill="#161616"></path>
										<path d="M1015.2 881.1C1017 885 1020.9 881.6 1023.9 883C1025.3 883.6 1025 884.5 1026.9 884.6C1029.1 884.7 1034.5 880.3 1035.6 884.4C1035.9 885.5 1037.3 885.8 1038.1 885.1C1039.1 884.8 1040.2 884.5 1041.2 884.3C1042.4 885.8 1044.6 883.6 1043.3 882.2C1041 879.5 1038.1 881 1035.9 883C1036.7 883.2 1037.6 883.4 1038.4 883.7C1037.5 880.2 1033.8 877.9 1030.3 879.5C1028.6 880.3 1028.4 881.8 1025.8 880.8C1025.3 880.6 1025.1 879.8 1024.6 879.6C1023.5 879.2 1017.7 879.8 1017.7 879.7C1017 877.8 1014.4 879.3 1015.2 881.1Z" fill="#161616"></path>
										<path d="M1020.5 802.4C1020.7 801.3 1022 801 1022.9 801.4C1024.2 801.9 1024.9 803.4 1025.5 804.5C1026.3 806.2 1028.9 804.7 1028.1 803C1027 800.8 1025.3 798.5 1022.6 798.2C1020.4 798 1018.1 799.3 1017.7 801.5C1017.2 803.5 1020.1 804.3 1020.5 802.4Z" fill="#161616"></path>
										<path d="M1038.9 803.8C1039.1 802.8 1040 802.2 1041 802.6C1042.2 803 1042.7 804.5 1042.9 805.6C1043.2 807.5 1046.1 806.7 1045.8 804.8C1045.4 802.4 1043.8 799.9 1041.2 799.5C1038.9 799.2 1036.4 800.6 1036 803C1035.7 804.9 1038.6 805.7 1038.9 803.8Z" fill="#161616"></path>
										<path d="M1022 808.7C1025.4 814.7 1033.5 815.6 1037.9 810.2C1039.1 808.7 1037 806.6 1035.8 808.1C1032.6 812 1027.1 811.7 1024.6 807.2C1023.7 805.5 1021.1 807 1022 808.7Z" fill="#161616"></path>
										<path d="M917.1 265.3C904.5 260.2 896.9 248 892.3 235.8C887.4 223.1 885.3 209.3 886 195.7C887.2 170.6 897.9 145.2 917.7 129.2C936.4 114 964.1 109.7 984.9 123.5C1006.6 137.9 1010.5 167.6 1010.7 191.5C1010.9 218.1 1004.7 246 985 265.1C983.6 266.4 985.7 268.6 987.1 267.2C1005.8 249 1012.9 223 1013.6 197.6C1014.3 172.7 1011.3 141.2 990.8 124.1C971.6 108.1 943.1 108.9 922.6 121.9C899.9 136.3 886.5 162.4 883.6 188.7C881.8 205 883.8 221.9 889.8 237.3C894.9 250.3 903 262.7 916.4 268.1C918.1 268.9 918.8 266 917.1 265.3Z" fill="#161616"></path>
										<path d="M915.7 267.2C912.3 272 913 279.1 918 282.7C921.8 285.4 927.4 285.8 931.9 286.4C937.3 287.2 942.8 287.5 948.3 287.5C958.1 287.4 969.3 286.7 978.4 282.9C985 280.1 990 273.5 986.5 266.4C985.7 264.7 983.1 266.2 983.9 267.9C991.4 283.2 955.9 284.5 948.2 284.5C941.1 284.5 934 283.9 927.1 282.6C923.8 282 920.1 281.4 918 278.5C915.9 275.5 916.3 271.6 918.3 268.8C919.4 267.1 916.8 265.6 915.7 267.2Z" fill="#161616"></path>
										<path d="M918.9 283.4C918.9 288.4 919 293.5 919.6 298.5C919.9 300.8 920.4 302.8 922.3 304.2C925 306.2 929.5 306.1 932.7 306.4C938.1 306.8 943.6 306.8 949 306.6C954.1 306.4 959.2 306 964.3 305.5C967.3 305.2 970.7 305.3 973 303C978.1 298 978.2 288.5 976.9 282.2C976.5 280.3 973.6 281.1 974 283C974.7 286.4 974.9 289.8 974.2 293.3C973.2 298.2 971.8 301.4 966.3 302.4C959.9 303.6 952.7 303.6 946.2 303.7C939.8 303.8 932.6 304.1 926.3 302.6C920.5 301.2 922 288.2 922 283.4C921.9 281.5 918.9 281.5 918.9 283.4Z" fill="#161616"></path>
										<path d="M919.9 175.7C931.9 183.2 941 194.2 946.1 207.4C946.8 209.2 949.7 208.4 949 206.6C943.6 192.6 934.1 181 921.5 173.1C919.8 172 918.3 174.6 919.9 175.7Z" fill="#161616"></path>
										<path d="M948.5 207.4C954.4 195 963 184.4 974 176.2C975.5 175.1 974 172.4 972.5 173.6C961.1 182.1 952.1 193.1 945.9 205.9C945.1 207.6 947.7 209.1 948.5 207.4Z" fill="#161616"></path>
										<path d="M946.1 203.9C947.6 224.5 948.3 245.1 948 265.7C948 267.6 951 267.6 951 265.7C951.3 245.1 950.6 224.4 949.1 203.9C948.9 201.9 945.9 201.9 946.1 203.9Z" fill="#161616"></path>
										<path d="M829 254.3C842.3 249.8 855.5 245.3 868.8 240.8C870.6 240.2 869.8 237.3 868 237.9C854.7 242.4 841.5 246.9 828.2 251.4C826.3 252 827.1 254.9 829 254.3Z" fill="#161616"></path>
										<path d="M844.2 234.3C848.8 233 853.4 231.6 858 230.3C859.8 229.8 859.1 226.9 857.2 227.4C852.6 228.7 848 230.1 843.4 231.4C841.6 232 842.3 234.9 844.2 234.3Z" fill="#161616"></path>
										<path d="M810.9 201C828.5 200.8 846 201.3 863.5 202.4C865.4 202.5 865.4 199.5 863.5 199.4C846 198.3 828.4 197.9 810.9 198C809 198 809 201 810.9 201Z" fill="#161616"></path>
										<path d="M848.1 187.4C852.3 188.5 856.6 189 860.9 189.1C862.8 189.1 862.8 186.1 860.9 186.1C856.8 186 852.8 185.5 848.9 184.5C847 184 846.2 186.9 848.1 187.4Z" fill="#161616"></path>
										<path d="M841.6 94.3001C857.3 107.8 871.6 122.6 884.5 138.8C885.7 140.3 887.8 138.2 886.6 136.7C873.7 120.5 859.4 105.6 843.7 92.2001C842.3 90.9001 840.1 93.0001 841.6 94.3001Z" fill="#161616"></path>
										<path d="M885.5 122.7C888.2 125.5 890.8 128.3 893.5 131.2C894.8 132.6 896.9 130.5 895.6 129.1C892.9 126.3 890.3 123.5 887.6 120.6C886.3 119.2 884.2 121.3 885.5 122.7Z" fill="#161616"></path>
										<path d="M824.6 128.3C829.5 131.2 834.5 133.9 839.6 136.4C841.3 137.3 842.8 134.7 841.1 133.8C836 131.2 831 128.5 826.1 125.7C824.4 124.7 822.9 127.3 824.6 128.3Z" fill="#161616"></path>
										<path d="M998.6 105.5C1013.4 90.7 1028.2 75.9 1043 61.1C1044.4 59.7 1042.2 57.6 1040.9 59C1026.1 73.8 1011.3 88.6 996.5 103.4C995.1 104.8 997.2 106.9 998.6 105.5Z" fill="#161616"></path>
										<path d="M1013.5 112.4C1017.3 109.6 1021.1 106.9 1024.9 104.1C1026.4 103 1025 100.4 1023.4 101.5C1019.6 104.3 1015.8 107 1012 109.8C1010.4 110.9 1011.9 113.5 1013.5 112.4Z" fill="#161616"></path>
										<path d="M952.4 85.9001C955.4 71.4001 957.7 56.8 959.1 42.1C959.3 40.2 956.3 40.2 956.1 42.1C954.7 56.5 952.5 70.9 949.5 85.1C949.1 87 952 87.8001 952.4 85.9001Z" fill="#161616"></path>
										<path d="M1036.5 177.9C1063.7 175.4 1090.8 172.9 1118 170.4C1119.9 170.2 1119.9 167.2 1118 167.4C1090.8 169.9 1063.7 172.4 1036.5 174.9C1034.6 175 1034.5 178 1036.5 177.9Z" fill="#161616"></path>
										<path d="M922.2 270.8C939.1 274.5 956.3 274.4 973.1 270.6C975 270.2 974.2 267.3 972.3 267.7C956 271.4 939.3 271.5 923 267.9C921.1 267.5 920.3 270.4 922.2 270.8Z" fill="#161616"></path>
										<path d="M924.7 296.2C937.6 297.9 950.5 298.3 963.5 297.4C965.4 297.3 965.4 294.3 963.5 294.4C950.5 295.3 937.6 294.9 924.7 293.2C922.8 293 922.8 296 924.7 296.2Z" fill="#161616"></path>
										<path d="M1017.6 260.7C1037.9 275 1058.6 288.8 1079.6 302.1C1081.2 303.1 1082.7 300.5 1081.1 299.5C1060.1 286.2 1039.4 272.4 1019.1 258.1C1017.5 257 1016 259.6 1017.6 260.7Z" fill="#161616"></path>
										<path d="M762.1 964.1C743.1 987.4 724.2 1010.6 705.2 1033.9C727.5 1112.3 740.1 1132.9 747.6 1131.8C753 1131 755.6 1119.3 758.5 1106.2C765.7 1073.5 761.4 1045.4 757 1027.9C769 1014.5 780.9 1001 792.9 987.6C782.6 979.8 772.4 972 762.1 964.1Z" fill="#161616" stroke="#161616" stroke-width="2" stroke-miterlimit="10"></path>
										<path d="M692 844.1C703.9 853.3 715.3 850 738.9 854C759 857.4 781.6 864.9 805.6 879.9C780 909.9 754.4 941.6 729 975C722.9 983.1 716.8 991.2 710.9 999.3C720.7 1001.6 732.5 1005.2 745.3 1011C757.8 1016.6 768 1022.9 776.1 1028.6C814.2 995.4 848.2 963.3 878.5 933C892.5 919 912.3 897.6 915.4 867.3C915.6 865.6 915.6 864.1 915.6 862.6C914.9 812.3 851.6 775.9 813.1 762.5C771 747.9 698.6 740 678.4 774.9C666.5 795.5 673.5 829.8 692 844.1Z" fill="#EC615B"></path>
										<path opacity="0.26" d="M765.9 793.8C765.2 803.3 763.5 814.6 760 827.1C757.5 836 754.5 843.9 751.5 850.6C748.2 851.9 745 853.2 741.7 854.5C763.2 863 784.8 871.5 806.3 880C790.3 898.3 774.2 917.5 758 937.5C741.3 958.1 725.4 978.3 710.3 998.2C721 1001.7 731.6 1005.2 742.3 1008.6C780.6 966.4 818.9 924.1 857.2 881.9C861.2 870.4 861 861.4 859.8 855.1C854.3 824.3 816.6 799.2 765.9 793.8Z" fill="#6D6C6C"></path>
										<path opacity="0.23" d="M806.1 879.8C809.6 885.1 813 890.5 816.5 895.8C786.7 931.7 756.9 967.5 727.1 1003.4C721.8 1002 716.4 1000.5 711.1 999.1C730.2 972.9 750.8 946 773.1 918.9C784.2 905.4 795.2 892.4 806.1 879.8Z" fill="#6D6C6C"></path>
										<path d="M706.6 926.1C736.1 931.5 765.7 936.8 795.2 942.2C829.3 1016.6 833.1 1040.6 826.7 1044.7C822.1 1047.6 812.5 1040.4 801.8 1032.3C775 1012 760.1 987.8 752 971.5C734.2 969.1 716.4 966.6 698.6 964.2C701.2 951.5 703.9 938.8 706.6 926.1Z" fill="#161616" stroke="#161616" stroke-width="2" stroke-miterlimit="10"></path>
										<path d="M645.8 760.4C592 776.2 559.2 809 538.1 830.6C509.6 859.9 474.6 895.8 484.7 922.3C489.9 935.8 505.7 944.1 579.3 956.9C634.9 966.6 683.1 971.8 720.7 974.7C725.4 957.5 730.1 940.4 734.7 923.2C696.9 911 659.2 898.9 621.4 886.7C644.6 881.8 669 876 694.4 868.9C713.7 863.5 732.1 857.9 749.6 852C765.8 819.3 769.3 784 752.4 764C728.8 736 675.9 751.5 645.8 760.4Z" fill="#EC615B"></path>
										<path opacity="0.29" d="M765.5 795C765 804.9 763.3 817.6 758.7 831.6C755.9 840.1 752.7 847.5 749.5 853.6C746.8 853.8 744.1 854.1 741.4 854.3C718.6 861.8 694.3 868.9 668.6 875.5C652 879.8 635.8 883.5 620.1 886.9C658 898.8 695.9 910.6 733.8 922.5C733 926.6 732 930.9 730.8 935.4C729.4 940.6 727.8 945.4 726.3 949.8C674 936.9 621.7 924 569.4 911.1C567 908.2 559.1 897.7 560.3 883.8C561.6 868.7 576 858 594.4 848.2C647.6 820 736.8 800.9 765.5 795Z" fill="#6D6C6C"></path>
										<path opacity="0.24" d="M593.2 889C602.7 888.3 612.3 887.6 621.8 886.8C659.1 899.2 696.4 911.7 733.7 924.1C732.1 930 730.5 936 728.9 941.9C683.7 924.2 638.4 906.6 593.2 889Z" fill="#6D6C6C"></path>
										<path d="M631.7 533.8C646.5 525.1 672.2 512.7 706.4 509.1C758.8 503.6 799 522.4 814.9 530.9C812 570.2 807.5 601.6 803.9 623.2C799.6 649 796.1 663.3 794.5 687C792.5 716.7 795 741.1 797.3 757.1C779.9 762.5 753.6 768.4 721.2 767.7C690 767 664.8 760.2 647.9 754.3C652.4 718.5 650.9 690.1 648.7 670.8C646.2 649.3 642.8 638.8 638.7 612.8C633.4 579.2 632 551.7 631.7 533.8Z" fill="white" stroke="#161616" stroke-width="2" stroke-miterlimit="10"></path>
										<path opacity="0.14" d="M739.9 521C742 528.6 744.4 539.1 746 551.7C747.1 560.2 751.6 598.7 737.6 649.5C729 680.8 719.9 713.9 690.1 741.1C680.5 749.9 671.2 755.8 664.4 759.5C679.5 763.4 699.2 767.1 722.5 767.3C753 767.6 778.2 762.1 795.7 756.7C794.7 749.2 793.4 737.9 792.9 724.3C792.5 712.4 792.5 693.4 800.7 639.9C807.2 597.8 811.8 579.7 814.1 541.6C814.4 537.2 814.5 533.5 814.7 531C807.4 526.7 797.9 522 786.2 518.2C775.4 514.7 765.7 512.9 757.7 512.1C751.9 515 745.9 518 739.9 521Z" fill="#6D6C6C"></path>
										<path opacity="0.17" d="M811.8 547.3C806.2 579 798.8 604.6 793 622.4C776 674.4 764.1 709.4 732 735C712.1 750.9 691.4 757.6 678.1 760.8C691.6 764.6 710.7 768.3 733.8 767.8C760.4 767.2 781.9 761.2 796 756.1C794.1 743.9 791.9 725.6 792.5 703.3C793.2 674.4 797.9 657 803.1 625.3C806 606.7 809.7 580.1 811.8 547.3Z" fill="#6D6C6C"></path>
										<path d="M631.1 533.3C618.1 542.3 601.4 556.5 586.8 577.6C553.9 625.4 554.8 676.1 556.1 694.8C562.1 710.9 572.4 732 590.4 752.7C600.9 764.8 616.3 782.2 642.6 793.4C651.4 797.2 659.1 799.3 664.7 800.5C669.5 796.2 674.2 791.9 679 787.6C663.6 769.6 648.1 750.7 632.6 731.2C620.7 716.3 609.3 701.5 598.3 686.9C598.7 674.6 601.3 652.3 614.7 629C622.3 615.8 631.2 606.2 638.3 599.7C635.9 577.6 633.5 555.5 631.1 533.3Z" fill="white" stroke="#161616" stroke-width="2" stroke-miterlimit="10"></path>
										<path opacity="0.13" d="M633 555.6C604.2 588.1 590.5 618.9 583.7 638.3C571.8 671.9 574.3 689.5 576.1 697.9C577.6 704.5 580.9 715.8 593 733C603.5 747.9 625.6 774.6 667.7 796.6C671.4 793 675.1 789.5 678.8 785.9C666.9 772.5 654.9 758.4 642.8 743.7C626.9 724.4 612.1 705.4 598.4 686.8C598.8 676.7 600.5 662.6 606.4 646.8C615.3 623.1 629.3 607.5 638 599.2C636.2 584.7 634.6 570.2 633 555.6Z" fill="#6D6C6C"></path>
										<path opacity="0.16" d="M598.3 688.1C600.1 699.8 603.8 714.7 611.6 730.8C628.8 765.8 655.7 785.1 669.4 793.5C672.1 791.1 674.7 788.8 677.4 786.4C651 753.6 624.7 720.8 598.3 688.1Z" fill="#6D6C6C"></path>
										<path d="M816.4 531.5C831 540.4 849.8 554.3 866.1 575C903.1 621.9 902 671.7 900.6 690.1C893.8 705.9 882.3 726.6 862.1 747C850.3 758.8 833 775.9 803.5 787C793.6 790.7 784.9 792.8 778.6 794C773.3 789.8 767.9 785.6 762.6 781.4C779.8 763.7 797.3 745.2 814.7 726C828 711.3 840.8 696.8 853.2 682.5C852.7 670.5 849.9 648.6 834.8 625.6C826.2 612.6 816.3 603.2 808.3 596.8C811.1 575 813.8 553.3 816.4 531.5Z" fill="white" stroke="#161616" stroke-width="2" stroke-miterlimit="10"></path>
										<path opacity="0.16" d="M815.7 555.8C828.4 569.1 845.7 590.4 859.2 620.1C865.6 634.2 876 657.1 876.2 678.7C876.9 744.4 783.9 789.6 778.5 792.2C773.2 788.8 768 785.5 762.7 782.1C792.8 749.1 822.8 716.1 852.9 683.1C852.3 672.3 849.8 652.2 837.8 630.8C827.9 613.2 815.6 601.8 807.5 595.5C810.3 582.3 813 569.1 815.7 555.8Z" fill="#6D6C6C"></path>
										<path opacity="0.12" d="M853.9 683.4C852.4 692.1 849.9 701.6 846 711.4C828.4 755.8 792.8 780.1 774.8 790.5C770.5 787.7 766.3 784.9 762 782.1C792.6 749.2 823.2 716.3 853.9 683.4Z" fill="#6D6C6C"></path>
										<path d="M839.4 690.8H593.5V846.5H839.4V690.8Z" fill="white" stroke="#161616" stroke-width="2" stroke-miterlimit="10"></path>
										<path d="M712.9 783.5C699.9 785.1 693.6 769.3 697 758.8C700.3 748.4 712.6 743.5 722.6 747.1C732.5 750.7 737.3 762.8 733.8 772.5C729.9 783.3 716.3 787.7 706.3 782.9C704.6 782.1 703 784.7 704.8 785.5C713.7 789.8 724.6 788.2 731.7 781.2C738.6 774.3 739.6 763.4 735.3 754.9C725.5 735.5 695.2 741.7 693.1 763C692 774.4 700.2 788.1 712.9 786.6C714.8 786.3 714.8 783.3 712.9 783.5Z" fill="#161616"></path>
										<path opacity="0.14" d="M685.8 690.4C719.1 702 741.8 731.7 742.5 763.6C743.4 803.6 709.8 841.2 664 845C722.3 845.5 780.6 846 839 846.5C838.8 794.3 838.7 742.1 838.5 690C787.6 690 736.7 690.2 685.8 690.4Z" fill="#6D6C6C"></path>
										<path opacity="0.26" d="M839 689.9C838.1 721.7 831.8 746.5 826.5 762.8C818.7 786.5 813.1 803.8 797 819.1C775.1 840.1 747.8 844 735.8 845C769.9 845.3 804 845.6 838.1 846C838.2 816.3 838.4 786.6 838.6 756.8C838.6 734.5 838.8 712.2 839 689.9Z" fill="#6D6C6C"></path>
										<path d="M709.2 453.1C703.7 459.8 709 468 708 489.9C707.5 500.8 705.7 509.6 704.3 515.5C709 517.7 717.3 520.8 728 521.1C741.2 521.4 751.2 517.3 756.1 514.9C753.9 510.5 751 503.7 749.2 494.9C744.7 472.5 752.7 462 746.1 453.7C738.4 444.1 716.6 444 709.2 453.1Z" fill="white" stroke="#161616" stroke-width="2" stroke-miterlimit="10"></path>
										<path d="M674.3 356.9C668 373.1 670.4 387.7 671.8 394C671.2 394.1 659.5 396.6 656.3 405.6C653.1 414.7 659.7 427.6 673.5 431.8C678.5 444 690.6 467.6 716.1 480.5C720.4 482.6 724.5 484.3 728.6 485.5C732.3 484.1 737.3 481.9 743 478.6C760.8 468.3 770.7 455.5 773 452.4C777.2 446.8 782.9 437.8 786.9 425.4C789.1 425.8 793 426 796.8 424.1C805.4 419.7 809.7 406 803.7 397.1C798.7 389.7 789 389.4 787.8 389.4C789.1 384.2 793.1 365.5 783 347C771.4 325.7 746.5 314.5 723.1 317.7C688.5 322.2 674.9 355.3 674.3 356.9Z" fill="white" stroke="#161616" stroke-width="2" stroke-miterlimit="10"></path>
										<path d="M707.9 479.6C714 483.2 720.7 488.8 728.2 488.2C734.4 487.7 741.3 484.1 746 480.3C747.5 479.1 745.4 477 743.9 478.2C739.5 481.7 732.4 485.4 726.6 485.3C720.5 485.2 714.3 480.1 709.3 477C707.8 476 706.3 478.6 707.9 479.6Z" fill="#161616"></path>
										<path d="M707.5 480.9C713.2 485.8 719.7 491.3 727.7 490.9C735.5 490.5 742.8 485.3 747.9 479.7C749.2 478.3 747.1 476.2 745.8 477.6C741.4 482.4 735.2 487.1 728.4 487.8C720.9 488.5 714.9 483.4 709.6 478.8C708.2 477.5 706.1 479.6 707.5 480.9Z" fill="#161616"></path>
										<path d="M711.3 481.5C711.3 481.4 711.3 481.4 711.3 481.5C710.7 480.6 709.6 480.4 708.9 481.1C708.9 481.1 708.9 481.1 708.8 481.2C708.2 481.7 708.2 482.8 708.8 483.3C719.8 493.8 740 497.2 747.9 481.1C748.7 479.4 746.2 477.8 745.3 479.6C738.3 494 720.6 490.5 711 481.2C711 481.9 711 482.6 711 483.3C711 483.3 711 483.3 711.1 483.2C710.3 483.1 709.5 483 708.7 482.9V483C709.7 484.6 712.3 483.1 711.3 481.5Z" fill="#161616"></path>
										<path d="M723.3 389C722 405.2 722.7 421.3 725.3 437.3C725.4 438.1 726.4 438.6 727.1 438.3C731 437 733.8 434.7 735.9 431.2C736.9 429.5 734.3 428 733.3 429.7C731.6 432.6 729.5 434.3 726.3 435.4C726.9 435.7 727.5 436.1 728.1 436.4C725.5 420.7 724.9 404.8 726.2 388.9C726.5 387 723.5 387 723.3 389Z" fill="#161616"></path>
										<path d="M705.5 404.9C705.7 405.8 707.2 411.4 705.5 412.3C703.8 413.2 699 409.6 697.5 404.9C696.3 400.9 697.3 395.9 699.2 395.5C701.2 395 704.3 400.5 705.5 404.9Z" fill="#161616" stroke="#161616" stroke-miterlimit="10"></path>
										<path d="M757.1 404.9C757.3 405.8 758.8 411.4 757.1 412.3C755.4 413.2 750.6 409.6 749.1 404.9C747.9 400.9 748.9 395.9 750.8 395.5C752.8 395 755.9 400.5 757.1 404.9Z" fill="#161616" stroke="#161616" stroke-miterlimit="10"></path>
										<path d="M719.7 450.5C731.9 455.7 743 448.4 752 440.5C753.5 439.2 751.3 437.1 749.9 438.4C742.1 445.2 732.1 452.5 721.3 447.9C719.4 447.2 717.9 449.8 719.7 450.5Z" fill="#161616"></path>
										<path d="M754.9 420.8C756.8 420.8 756.8 417.8 754.9 417.8C753 417.8 753 420.8 754.9 420.8Z" fill="#161616"></path>
										<path d="M766 415.2C766.1 415.1 766.2 415 766.3 414.9C766.9 414.3 766.9 413.4 766.3 412.8C765.7 412.2 764.8 412.2 764.2 412.8C764.1 412.9 764 413 763.9 413.1C763.3 413.7 763.3 414.6 763.9 415.2C764.5 415.8 765.4 415.8 766 415.2Z" fill="#161616"></path>
										<path d="M762.7 422.5C762.8 422.5 762.9 422.5 763 422.5C763.8 422.5 764.5 421.8 764.5 421C764.5 420.2 763.8 419.5 763 419.5C762.9 419.5 762.8 419.5 762.7 419.5C761.9 419.5 761.2 420.2 761.2 421C761.2 421.8 761.8 422.5 762.7 422.5Z" fill="#161616"></path>
										<path d="M693.7 387C697.5 383.8 703.1 383.7 706.9 386.8C708.4 388 710.5 385.9 709 384.7C703.8 380.5 696.6 380.5 691.5 384.9C690.1 386.1 692.3 388.2 693.7 387Z" fill="#161616"></path>
										<path d="M742.5 385.9C746.8 382.1 752.9 381.4 758 384.1C759.7 385 761.2 382.4 759.5 381.5C753.2 378.1 745.7 379 740.4 383.8C738.9 385.1 741 387.2 742.5 385.9Z" fill="#161616"></path>
										<path d="M694.5 420.5C696.4 420.5 696.4 417.5 694.5 417.5C692.6 417.5 692.6 420.5 694.5 420.5Z" fill="#161616"></path>
										<path d="M705.1 419.1C707 419.1 707 416.1 705.1 416.1C703.2 416.1 703.2 419.1 705.1 419.1Z" fill="#161616"></path>
										<path d="M699.8 421.4C699.7 421.5 699.6 421.6 699.5 421.7C698.9 422.3 698.9 423.2 699.5 423.8C700.1 424.4 701 424.4 701.6 423.8C701.7 423.7 701.8 423.6 701.9 423.5C702.5 422.9 702.5 422 701.9 421.4C701.3 420.8 700.4 420.8 699.8 421.4Z" fill="#161616"></path>
										<path d="M690.6 324.4C684.6 328.1 671.5 336.2 662.6 352.8C653.7 369.3 653.8 384.9 654.2 391.6C639.6 417.9 633 441.1 629.8 456.9C624.6 482 627.4 491.2 631 497.3C639.8 512.3 657.5 516 661.8 516.9C683.9 521.6 701.5 510.3 705.5 507.7C706.6 504.4 707.7 500.3 708.3 495.7C709.4 487.8 708.8 481 707.9 476.1C702.9 472 697.1 466.5 691.5 459.3C683 448.3 678.2 437.5 675.5 429.7C666.7 428.8 660.1 422.8 659.5 416.1C658.9 409.8 663.3 401.7 669.1 401.7C673.4 401.7 676.5 406.1 677.1 406.9C680.4 405 685 401.7 689.1 396.5C695 389 696 382.3 698.7 375.3C701.3 368.5 706.3 359.1 717.1 349.3C727.5 353 735.8 356.6 741.5 359.3C759.8 367.8 770.2 372.8 778.3 383.7C782.8 389.7 785.1 395.6 786.3 399.3C795.6 392.2 799.4 394.1 800.2 394.5C806.1 397.8 805.9 415.9 796.7 420.9C793.2 422.8 789.2 422.3 786.7 421.7C783.8 431.8 777.4 448.3 762.7 462.9C757.8 467.8 752.8 471.6 748.3 474.5C747.9 478.2 747.7 483.3 748.7 489.3C749.6 494.8 751.2 499.3 752.7 502.5C777 518.5 807.2 515.6 823.6 498.4C838.2 483 838.2 459.7 832.7 443.8C827.7 429.3 818 420.6 813.2 416.8C814.5 411.2 821.7 378 800.8 347.5C795.4 339.6 780.3 320.5 753.1 313.9C724.3 306.9 700.6 318.3 690.6 324.4Z" fill="#4A4DC4"></path>
										<path opacity="0.17" d="M754 365.5C765.3 379.7 768.3 392.8 769.3 399.6C773.4 428.2 756.8 451.4 748.4 463.3C741.5 473 734.4 479.9 729.3 484.4C733.7 496.2 738 507.9 742.4 519.7C746.9 518.2 751.4 516.7 755.9 515.2C753.8 511.5 751.6 506.9 749.9 501.3C746.8 491.3 746.6 482.5 746.9 476.5C752.4 472.6 758.8 467.3 765 460.3C777.4 446.3 783.6 431.9 786.8 422.2C788.8 422.6 793.1 423.1 797.2 420.8C805.8 415.9 804.4 403.7 804.3 402.9C804 400.3 803.4 396.1 800.4 394.4C796.9 392.5 791.1 394.3 786.5 399.7C785.1 394.8 781.1 383.5 770.3 374.5C764.3 369.5 758.2 366.9 754 365.5Z" fill="#6D6C6C"></path>
										<path opacity="0.19" d="M724.7 389.9C724.1 389.8 722.1 398.5 716 436.9C719.5 436.9 723 436.9 726.5 436.9C725.8 431.8 725 424.4 724.6 415.4C724.1 400 725.3 390 724.7 389.9Z" fill="#6D6C6C"></path>
										<path opacity="0.54" d="M812.6 417.8C810.9 418.8 817.3 427.4 816.4 440.2C816.1 444.2 815.5 453.3 808.3 459.4C807.8 459.8 797.1 468.6 786.5 463C780.2 459.7 776.4 452.7 776.6 445.1C773.6 450.7 768.7 458.1 761 465.3C756.2 469.8 751.5 473.1 747.4 475.6C747.1 480 747.1 486 748.7 492.8C749.6 496.5 750.7 499.7 751.8 502.3C754.9 504.3 759.5 507 765.5 509.1C769.3 510.4 790.2 517.7 810.6 507.6C814.2 505.8 822.2 501.7 828 492.9C828.7 491.8 831.3 487.9 833.2 481.3C840 458.7 830 438.3 829.1 436.6C823.6 425.5 814.7 416.6 812.6 417.8Z" fill="#341999"></path>
										<path opacity="0.35" d="M675.4 430.1C674.5 430.2 676.5 438.4 674.3 446.5C672.7 452.5 667.6 462.3 658.9 464.3C649.8 466.3 638.5 459.6 633.3 447.1C630.9 452.6 627.7 461.4 627.2 472.6C626.9 479.9 626.5 487.7 630.1 495.4C638.2 512.6 662 519.3 679.7 517.7C691.5 516.6 700.4 512 705.2 508.9C706.4 505.5 707.6 501.4 708.4 496.7C709.7 488.6 709.4 481.5 708.7 476.3C700.8 470.6 695.4 464.7 692 460.4C687.2 454.3 680.7 446.1 677.4 434.7C676.9 433.4 676 430 675.4 430.1Z" fill="#341999"></path>
									</svg>
								</div>
							</div>
						</div>
					</div>
					<?php endif; ?>


				



	<!-- LOGIN -->
	<div class="modal fade" id="connectWalletModal" tabindex="-1" aria-labelledby="connectWalletModalLabel" aria-hidden="true" data-bs-backdrop="static" data-bs-keyboard="false" style="backdrop-filter: blur(5px);">
		<div class="modal-dialog modal-dialog-centered">
			<div class="modal-content overflow-hidden">
				<div class="modal-header pb-0 border-0">
					<h1 class="modal-title h4" id="connectWalletModalLabel">Connect your account</h1>
					<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
				</div>
				<div class="modal-body undefined">
					<div class="list-group list-group-flush gap-2">
						<form method="POST" action="<?= PROOT; ?>auth/login">
							<div class="list-group-item border rounded p-4 bg-body-secondary-hover">
								<div class="mb-2">
									<input type="email" autocomplete="off" name="admin_email" class="form-control form-control-lg" placeholder="Email">
								</div>
								<div class="mb-2">
									<input type="password" name="admin_password" class="form-control form-control-lg" placeholder="******">
								</div>
								<div class="">
									<input type="submit" name="submit_form" class="form-control form-control-lg" value="Connect">
								</div>
							</div>
						</form>
					</div>
					<div class="text-xs text-muted mt-6">Missing password? <a href="auth/recover-password" class="fw-bold">Recover here.</a></div>
					<div class="text-xs text-muted mt-6">By connecting, know that we save all actions into logs for future references. You agree to J-Spence <a href="#" class="fw-bold">Terms of Service</a></div>
				</div>
			</div>
		</div>
	</div>

<?php include ("includes/footer.inc.php"); ?>

<script type="text/javascript" src="<?= PROOT; ?>dist/js/Chart.min.js"></script>
<script type="text/javascript">
    /* globals Chart:false, feather:false */

	(function () {
	    'use strict'

	      // Graphs
	    var ctx = document.getElementById('myChart')
	      // eslint-disable-next-line no-unused-vars
	    var myChart = new Chart(ctx, {
	        type: 'line',
	        data: {
	            labels: [
	                <?php 
	                    for ($i = 1; $i <= 12; $i++) {
	                        $dt = dateTime::createFromFormat('!m',$i);
	                        $m = $dt->format("F");
	                        echo json_encode($m).',';
	                    }
	                ?>
	            ],
	            datasets: [{
	                label: '<?= $thisYr; ?>, Amount ₵',
	                data: [
	                    <?php 
	                        for ($i = 1; $i <= 12; $i++) {
	                            $mn = (array_key_exists($i, $current)) ? $current[$i] : 0;
	                            echo json_encode($mn).',';
	                        }
	                    ?>
	                ],
	                lineTension: 0,
	                backgroundColor: 'rgba(225, 0.1, 0.3, 0.1)',
	                borderColor: 'tomato',
	                borderWidth: 3,
	                pointBackgroundColor: 'red'
	            },{
	                label: '<?= $lastYr; ?>, Amount ₵',
	                data : [
	                    <?php 
	                        for ($i = 1; $i <= 12; $i++) {
	                            $mn = (array_key_exists($i, $last)) ? $last[$i] : 0;
	                            echo json_encode($mn).',';
	                        }
	                    ?>
	                ],
	                backgroundColor: 'rgba(0, 225, 0, 0.1)',
	                borderColor: 'gold',
	                pointBackgroundColor: 'brown',
	                borderWidth: 3
	            }]
	        },
	        options: {
	            responsive: true,
	            scales: {
	                yAxes: [{
	                    ticks: {
	                        beginAtZero: false
	                    }
	                }]
	            },
	            legend: {
	                display: true,
	                position: 'top',
	            },
	            title: {
	                display: true,
	                text: 'Sales By Month - J-Spence LTD.'
	            }
	        }
	    })
	})()
</script>
